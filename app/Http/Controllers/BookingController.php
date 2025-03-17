<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Desk;
use App\Models\Plan;
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


    public function stepTwo(Request $request)
    {
        if(!Auth::user()->team->allow_multi_day)
        {
            $request->merge(['end_date' => $request['start_date']]);
        }

        $validated = $request->validate([
            'workplace_id' => 'required|exists:workplaces,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);
    
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

    public function complete(Request $request)
    {
       
        $bookingData = $request->session()->get('booking');
       // dd( Carbon::createFromTimestamp(strtotime($bookingData['start_date'] . $bookingData['start_time'] . ":00")) );
        
        $booking = Booking::create([
            'desk_id' => $request->desk_id,
            'start_date' => $bookingData['start_date'],
            'end_date' => $bookingData['end_date'],
            'start_time' => $bookingData['start_time'],
            'end_time' => $bookingData['end_time'],
            'from_date'  => Carbon::createFromTimestamp(strtotime($bookingData['start_date'] . $bookingData['start_time'] . ":00")),
            'to_date'  => Carbon::createFromTimestamp(strtotime($bookingData['end_date'] . $bookingData['end_time'] . ":00")),
            'user_id' => Auth::id(),
            'status'    => 0
        ]);

       // dd($booking);


        Auth::user()->notify(new \App\Notifications\NewBooking($booking));
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

        // Controlla se esiste una prenotazione attiva per la scrivania in quella data
        $isOccupied = Booking::where('desk_id', $desk->id)
            ->where('status', 0)
            ->whereDate('from_date', '<=', $request->date)
            ->whereDate('to_date', '>=', $request->date)
            ->exists();

        return response()->json([
            'success' => true,
            'desk' => $desk,
            'isOccupied' => $isOccupied,
        ]);
    }

}
