<?php

namespace App\Http\Controllers;

use App\Helpers\Holidays;
use App\Models\Booking;
use App\Models\Desk;
use App\Models\Plan;
use App\Models\Presence;
use App\Models\Workplace;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Resend\Laravel\Facades\Resend;

class BookingController extends Controller
{
    public function stepOne(Request $request)
    {
        $request->session()->forget('booking');
        
        $plan_arr = Auth::user()->team->plans->pluck('workplace_id');
        $workplaces = Workplace::whereIn('id',$plan_arr)->get();
        return view('booking.step-one', compact('workplaces'));
    }

    public function checkWorkstationAvailability(Request $request)
    {
        if(Auth::user()->team->allow_multi_day)
        {
            $validated = $request->validate([
                'end_date' => 'required|date',
                'end_time' => 'required',
                'start_date' => 'required|date',
                'start_time' => 'required',
            ]);
        }
        else{
            $validated = $request->validate([
                'start_date' => 'required|date',
                'start_time' => 'required',
            ]);
        }

        if(!Auth::user()->team->allow_multi_day)
        {
            $request->merge(['end_date' => $request['start_date']]);
        }
            
        $user = Auth::user();
    
        if (!$user->default_workstation_id) {
            return response()->json([
                'available' => false,
                'message' => 'Nessuna postazione preferita configurata'
            ]);
        }

        $fromDateTime =  Carbon::parse("{$request->start_date} {$request->start_time}")->format('Y-m-d H:i:s');
        $toDateTime =  Carbon::parse("{$request->end_date} {$request->end_time}")->format('Y-m-d H:i:s');
    
        $existingBooking = Booking::where('desk_id', $user->default_workstation_id)
                ->join('users', 'bookings.user_id', '=', 'users.id')
                ->where('status', 0)
                ->where(function ($query) use ($fromDateTime, $toDateTime) {
                    $query->where(function ($q) use ($fromDateTime, $toDateTime) {
                        $q->where('from_date', '<', $toDateTime)
                        ->where('to_date', '>', $fromDateTime);
                    });
                })
                ->orderByDesc('users.priority')
                ->select('bookings.*', 'users.name as user_name', 'users.priority as user_priority')
                ->first();
    
        $isOccupied = !is_null($existingBooking);
        $canOverride = false;
    
        if ($isOccupied && $existingBooking->user_priority !== null) {
            $canOverride = $user->priority > $existingBooking->user_priority;
        }
        if(!$isOccupied){
            $request->session()->put('booking', $validated);
        }
    
        return response()->json([
            'available' => !$isOccupied,
            'can_override' => $canOverride,
            'occupied_by' => $existingBooking->user_name ?? null
        ]);
    }
    
    public function stepTwo(Request $request)
    {
        if(!Auth::user()->team->allow_multi_day)
        {
            $request->merge(['end_date' => $request['start_date']]);
        }

        if($request['end_date']== $request['start_date']){
            $validated = $request->validate([
                'workplace_id' => 'required|exists:workplaces,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'start_time' => 'required',
                'end_time' => 'required|after_or_equal:start_time',
            ]);
        }
        else
        {
            $validated = $request->validate([
                'workplace_id' => 'required|exists:workplaces,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'start_time' => 'required',
                'end_time' => 'required',
            ]);
        }

        $request->session()->put('booking', $validated);
        $workplace = Workplace::findOrFail($request->workplace_id);

        return view('booking.step-two', compact('workplace', 'request'));
    }

    
    public function getDesks(Request $request, Plan $plan)
    {
        $booking = $request->session()->get('booking');

        $startDate = Carbon::parse($booking['start_date'] ?? now())->format('Y-m-d');
        $endDate = Carbon::parse($booking['end_date'] ?? now())->format('Y-m-d');
        $startTime = $booking['start_time'] ?? '00:00:00';
        $endTime = $booking['end_time'] ?? '23:59:59';

        $startFullDateTime = Carbon::parse("$startDate $startTime")->format('Y-m-d H:i:s');
        $endFullDateTime = Carbon::parse("$endDate $endTime")->format('Y-m-d H:i:s');
        $currentUser = Auth::user();

        $desks = $plan->desks->map(function ($desk) use ($startFullDateTime, $endFullDateTime, $currentUser) {
            // Troviamo la prenotazione attiva sulla scrivania con priorità più alta
            $existingBooking = $desk->bookings()
                ->join('users', 'bookings.user_id', '=', 'users.id') // Uniamo con la tabella utenti
                ->where('status', 0) // Solo prenotazioni confermate
                ->where(function ($query) use ($startFullDateTime, $endFullDateTime) {
                    $query->whereBetween('from_date', [$startFullDateTime, $endFullDateTime])
                          ->orWhereBetween('to_date', [$startFullDateTime, $endFullDateTime])
                          ->orWhere(function ($q) use ($startFullDateTime, $endFullDateTime) {
                              $q->where('from_date', '<=', $startFullDateTime)
                                ->where('to_date', '>=', $endFullDateTime);
                          });
                })
                ->orderByDesc('users.priority') // Priorità più alta per prima
                ->select('bookings.*', 'users.name as user_name', 'users.priority as user_priority')
                ->first(); // Prendiamo la prenotazione con priorità più alta
        
            $isOccupied = !is_null($existingBooking);
            $canOverride = false;
            $occupiedBy = $existingBooking->user_name ?? null;
        
            if ($isOccupied && $existingBooking->user_priority !== null) {
                // Controlliamo se l'utente attuale ha una priorità più alta
                if ($currentUser->priority > $existingBooking->user_priority) {
                    $canOverride = true;
                }
            }
        
            return [
                'id' => $desk->id,
                'identifier' => $desk->identifier,
                'is_available' => !$isOccupied, // true se libera, false se occupata
                'is_occupied' => $isOccupied,
                'occupied_by' => $occupiedBy,
                'can_override' => $canOverride, // true se può rubare la postazione
            ];
        });

        return response()->json([
            'plan' => $plan,
            'desks' => $desks,
        ]);
        
    }

    public function getOtherDesk (Request $request, Desk $desk){

        $currentUser = Auth::user();

        $booking = $request->session()->get('booking');

        $startDate = Carbon::parse($booking['start_date'] ?? now())->format('Y-m-d');
        $endDate = Carbon::parse($booking['end_date'] ?? now())->format('Y-m-d');
        $startTime = $booking['start_time'] ?? '00:00:00';
        $endTime = $booking['end_time'] ?? '23:59:59';
        
        $startFullDateTime = Carbon::parse("$startDate $startTime")->format('Y-m-d H:i:s');
        $endFullDateTime = Carbon::parse("$endDate $endTime")->format('Y-m-d H:i:s');
        $currentUser = Auth::user();
    
        $existingBooking = $desk->bookings()
            ->where('status', 0)
            ->where(function ($query) use ($startFullDateTime, $endFullDateTime) {
                $query->where(function ($q) use ($startFullDateTime, $endFullDateTime) {
                    $q->where('from_date', '<=', $endFullDateTime)
                      ->where('to_date', '>=', $startFullDateTime);
                });
            })
            ->with('user')
            ->first();
         
    
        if ($existingBooking && $existingBooking->user->priority < $currentUser->priority) {
            // Segniamo la vecchia prenotazione come "rubata"
           $existingBooking->update(['status' => 2]);
    
            return response()->json(['success' => 'Continua e prendi la prenotazione'], 200);
        }
    
        return response()->json(['message' => 'Non puoi prendere questa postazione.'], 403);
    }

    public function getPlans($workplace_id)
    {
        $plans = Plan::where('workplace_id', $workplace_id)->with('desks')->get();
        return response()->json($plans);
    }

    public function stepThree(Request $request)
    {
        $request->validate([
            'desk_id' => 'required|exists:desks,id',
        ]);

        
        $desk = Desk::findOrFail($request->desk_id);
        return view('booking.step-three', compact('desk', 'request'));

    }

    public function multiCancel(Request $request)
    {
        $bookingIds = $request->input('booking_ids');

        if (!$bookingIds) {
            return back()->with('error', 'Nessuna prenotazione selezionata.');
        }
        
        $bookings = Booking::whereIn('id', $bookingIds);

        foreach ($bookings->get() as $booking) {
            Presence::where('booking_id', $booking->id)->delete();
        }

        $bookings->update(['status'=>1]);

        return back()->with('success', 'Prenotazioni cancellate con successo.');
    }

    public function complete(Request $request)
    {
       
        $bookingData = $request->session()->get('booking');
        $timezone = env('APP_TIMEZONE', 'Europe/Rome');

        $booking_save = [
            'desk_id' => $request->desk_id,
            'start_date' => $bookingData['start_date'],
            'end_date' => $bookingData['end_date'],
            'start_time' => $bookingData['start_time'],
            'end_time' => $bookingData['end_time'],
            'from_date'  => Carbon::createFromTimestamp(strtotime($bookingData['start_date'] . $bookingData['start_time'] . ":00"))->setTimezone($timezone),
            'to_date'  => Carbon::createFromTimestamp(strtotime($bookingData['end_date'] . $bookingData['end_time'] . ":00"))->setTimezone($timezone),
            'user_id' => Auth::id(),
            'status'    => 0
        ];

        if($bookingData['start_date'] == $bookingData['end_date']){
            if(!Holidays::isHoliday($bookingData['start_date'])){
                $booking = Booking::create($booking_save);
            }
        }
        else
        {
            $dates = [];

            $startDate = Carbon::parse($bookingData['start_date']);
            $endDate = Carbon::parse($bookingData['end_date']);
            
            $diff = $startDate->diffInDays($endDate);
            
            $i=0;
            // Itera tra le date e salva solo i giorni lavorativi
            while ($startDate->lte($endDate)) {
                $currentDate = $startDate->copy();
            
                if (
                    $currentDate->isWeekday() && 
                    !Holidays::isHoliday($currentDate)
                ) {
                    $start_time = ($i == 0) ? $bookingData['start_time'] : "07:30";
                    $end_time = ($i == $diff) ? $bookingData['end_time'] : "21:00";
                    $start_date = $currentDate->toDateString();
            
                    $dates[] = [
                        'desk_id' => $request->desk_id,
                        'start_date' => $start_date,
                        'end_date' => $start_date,
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'from_date'  => Carbon::createFromTimestamp(strtotime($start_date . $start_time . ":00"))->setTimezone($timezone),
                        'to_date'    => Carbon::createFromTimestamp(strtotime($start_date . $end_time . ":00"))->setTimezone($timezone),
                        'user_id'    => Auth::id(),
                        'status'     => 0
                    ];
                }
            
                $i++;
                $startDate->addDay(); // Passa al giorno successivo
            }
    
            foreach ($dates as $data) {
                if ($data['status'] === 0) {
                    Booking::create($data);
            
                   /* Presence::updateOrCreate(
                        [
                            'user_id' => $data['user_id'],
                            'date' => $data['start_date'],
                        ],
                        [
                            'status' => 'presente',
                            'booking_id' => $booking->id,
                        ]
                    );*/
                }
            }
            /*Booking::insert($dates);

            // Crea manualmente le presenze
            foreach ($dates as $data) {
                if ($data['status'] === 0) {
                    Presence::updateOrCreate(
                        ['user_id' => $data['user_id'], 'date' => $data['start_date']],
                        ['status' => 'presente']
                    );
                }
            }*/
        }
        
        Auth::user()->notify(new \App\Notifications\NewBooking($booking_save));
                //->toMail($booking->user);

        $request->session()->forget('booking');


        return redirect()->route('booking.my')->with('success', 'Prenotazione completata!');
    }

    public function myBookings()
    {
        $bookings = Booking::where('user_id', Auth::id())->where('status', 0)->with('desk')->get();

        return view('booking.my-bookings', compact('bookings'));
    }


    public function history()
    {
        $bookings = Booking::where('user_id', Auth::id())
        ->where(function ($query) {
            $query->where('to_date', '<', now())
                  ->orwhereIn('status', [1, 2, 3]);
        })
            ->orderByDesc('to_date')
            ->paginate(10);

        return view('booking.history', compact('bookings'));
    }

    public function cancel($id)
    {
        $booking = Booking::where('id', $id)
            ->where('user_id', Auth::id()) // Assicuriamoci che l'utente possa cancellare solo le sue prenotazioni
            ->first();

        if (!$booking) {
            return redirect()->back()->with('error', 'Prenotazione non trovata.');
        }

        $booking->status = 1; // Segniamo la prenotazione come cancellata
        $booking->save();
    //>>     Presence::where('booking_id', $booking->id)->delete();

        return redirect()->route('bookings.current')->with('success', 'Prenotazione cancellata con successo.');
    }

    /**
     * Mostra le prenotazioni attuali e future.
     */
    public function current()
    {
        $bookings = Booking::where('user_id', Auth::id())
            ->where('to_date', '>=', now()) // Prenotazioni future o attive
            ->where('status', 0) // Solo attive
            ->orderBy('from_date')
            ->paginate(10);

        return view('booking.current', compact('bookings'));
    }

    public function checkAvailability(Request $request)
    {
        $request->validate([
            'desk_identifier' => 'required|string',
            'date' => 'required|date',
        ]);
    
        $desk = Desk::where('identifier', $request->desk_identifier)->first();
    
        if (!$desk) {
            return response()->json(['success' => false, 'message' => 'Scrivania non trovata.']);
        }
    
        // Genera tutte le fasce orarie di 30 minuti tra 07:30 e 21:00
        $workingHours = [];
        $startTime = Carbon::createFromTime(7, 30);
        $endTime = Carbon::createFromTime(21, 0);
    
        while ($startTime < $endTime) {
            $workingHours[] = $startTime->format('H:i');
            $startTime->addMinutes(30);
        }
    
        $availableHours = $workingHours; // Inizialmente tutti gli orari sono disponibili
    
        // Recupera tutte le prenotazioni per quella scrivania e data
        $bookings = Booking::where('desk_id', $desk->id)
            ->where('status', 0)
            ->whereDate('from_date', '<=', $request->date)
            ->whereDate('to_date', '>=', $request->date)
            ->get();
    
        // Costruisce una lista di slot occupati
        $occupiedHours = [];
    
        foreach ($bookings as $booking) {
            $startHour = Carbon::parse($booking->from_date);
            $endHour = Carbon::parse($booking->to_date);
    
            while ($startHour < $endHour) {
                $occupiedHours[] = $startHour->format('H:i');
                $startHour->addMinutes(30);
            }
        }
    
        // Filtra gli orari disponibili rimuovendo quelli già occupati
        $availableHours = array_diff($workingHours, $occupiedHours);
    
        return response()->json([
            'success' => true,
            'desk' => $desk,
            'isOccupied' => !empty($occupiedHours),
            'availableHours' => array_values($availableHours),
        ]);
    }

}
