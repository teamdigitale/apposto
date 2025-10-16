<?php

namespace App\Http\Controllers;

use App\Helpers\Holidays;
use App\Models\{Booking, Desk, Plan, Presence, Workplace};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Cache, DB};

class BookingController extends Controller
{
    protected $user;
    protected $timezone;

    public function __construct()
    {
        $this->user = Auth::user();
        $this->timezone = config('app.timezone', 'Europe/Rome');
    }

    public function stepOne(Request $request)
    {
        $request->session()->forget('booking');

        $planWorkplaceIds = $this->user->team->plans->pluck('workplace_id')->unique();

        $workplaces = Cache::remember("workplaces_user_{$this->user->id}", 60, function () use ($planWorkplaceIds) {
            return Workplace::whereIn('id', $planWorkplaceIds)->get();
        });

        return view('booking.step-one', compact('workplaces'));
    }

    public function checkWorkstationAvailability(Request $request)
    {
        $isMultiDay = $this->user->team->allow_multi_day;

        $rules = [
            'start_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required'
        ];

        if ($isMultiDay) {
            $rules['end_date'] = 'required|date';
        }

        $validated = $request->validate($rules);

        if (!$isMultiDay) {
            $validated['end_date'] = $request->input('start_date');
        }

        if (!$this->user->default_workstation_id) {
            return response()->json(['available' => false, 'message' => 'Nessuna postazione preferita configurata']);
        }

        $from = Carbon::parse("{$request->start_date} {$request->start_time}")->format('Y-m-d H:i:s');
        $to = Carbon::parse("{$request->end_date} {$request->end_time}")->format('Y-m-d H:i:s');

        // Verifica se l'area è prenotata in modo esclusivo
        $exclusiveBooking = $this->checkExclusiveAreaBooking(
            $this->user->default_workstation_id, 
            $from, 
            $to
        );

        if ($exclusiveBooking) {
            return response()->json([
                'available' => false,
                'is_exclusive' => true,
                'message' => "L'area è prenotata in modo esclusivo da {$exclusiveBooking->team_name} fino al " . 
                            Carbon::parse($exclusiveBooking->to_date)->format('d/m/Y H:i')
            ]);
        }

        $existing = Booking::where('desk_id', $this->user->default_workstation_id)
            ->join('users', 'bookings.user_id', '=', 'users.id')
            ->where('status', 0)
            ->where(function ($query) use ($from, $to) {
                $query->where('from_date', '<', $to)
                      ->where('to_date', '>', $from);
            })
            ->orderByDesc('users.priority')
            ->select('bookings.*', 'users.name as user_name', 'users.priority as user_priority')
            ->first();

        $isOccupied = !is_null($existing);
        $canOverride = $isOccupied && $this->user->priority > ($existing->user_priority ?? 0);

        if (!$isOccupied) {
            $request->session()->put('booking', $validated);
        }

        return response()->json([
            'available' => !$isOccupied,
            'can_override' => $canOverride,
            'occupied_by' => $existing->user_name ?? null
        ]);
    }

    public function stepTwo(Request $request)
    {
        if (!$this->user->team->allow_multi_day) {
            $request->merge(['end_date' => $request['start_date']]);
        }

        $rules = [
            'workplace_id' => 'required|exists:workplaces,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required',
            'end_time' => 'required',
        ];

        if ($request['end_date'] === $request['start_date']) {
            $rules['end_time'] .= '|after_or_equal:start_time';
        }

        $validated = $request->validate($rules);

        $request->session()->put('booking', $validated);
        $workplace = Workplace::findOrFail($request->workplace_id);

        // Verifica se il team può prenotare in modo esclusivo
        $canBookExclusive = $this->user->team->can_book_exclusive;

        return view('booking.step-two', compact('workplace', 'request', 'canBookExclusive'));
    }

    public function stepThree(Request $request)
    {
        $validated = $request->validate([
            'desk_id' => 'required|exists:desks,id',
            'is_exclusive' => 'nullable|boolean'
        ]);

        // Aggiorna la sessione con desk_id e is_exclusive
        $bookingData = $request->session()->get('booking', []);
        $bookingData['desk_id'] = $validated['desk_id'];
        $bookingData['is_exclusive'] = $validated['is_exclusive'] ?? false;
        $request->session()->put('booking', $bookingData);

        $desk = Desk::findOrFail($request->desk_id);
        $canBookExclusive = $this->user->team->can_book_exclusive;
        
        return view('booking.step-three', compact('desk', 'request', 'canBookExclusive'));
    }

    public function getPlans($workplace_id)
    {
        return Cache::remember("plans_for_workplace_{$workplace_id}", 60, function () use ($workplace_id) {
            return Plan::where('workplace_id', $workplace_id)->with('desks')->get();
        });
    }

    public function getDesks(Request $request, Plan $plan)
    {
        $booking = $request->session()->get('booking');

        $startDate = Carbon::parse($booking['start_date'] ?? now());
        $endDate = Carbon::parse($booking['end_date'] ?? now());
        $startTime = $booking['start_time'] ?? '00:00:00';
        $endTime = $booking['end_time'] ?? '23:59:59';

        $startFullDateTime = $startDate->copy()->setTimeFromTimeString($startTime)->format('Y-m-d H:i:s');
        $endFullDateTime = $endDate->copy()->setTimeFromTimeString($endTime)->format('Y-m-d H:i:s');

        // Verifica prenotazione esclusiva dell'intera area/piano
        $exclusiveBooking = Booking::where('plan_id', $plan->id)
            ->where('is_exclusive', true)
            ->where('status', 0)
            ->where(function ($query) use ($startFullDateTime, $endFullDateTime) {
                $query->where('from_date', '<', $endFullDateTime)
                      ->where('to_date', '>', $startFullDateTime);
            })
            ->with(['user.team'])
            ->whereHas('user', function($query) {
                $query->where('team_id', '!=', $this->user->team_id);
            })
            ->first();

        if ($exclusiveBooking) {
            $teamName = $exclusiveBooking->user->team->name ?? 'Un altro team';
            
            return response()->json([
                'plan' => $plan,
                'desks' => [],
                'is_exclusive' => true,
                'message' => "L'intera area è prenotata in modo esclusivo da {$teamName}",
                'exclusive_until' => Carbon::parse($exclusiveBooking->to_date)->format('d/m/Y H:i')
            ]);
        }

        $desks = $plan->desks->map(function ($desk) use ($startFullDateTime, $endFullDateTime) {
            $existing = $desk->bookings()
                ->join('users', 'bookings.user_id', '=', 'users.id')
                ->where('status', 0)
                ->where(function ($query) use ($startFullDateTime, $endFullDateTime) {
                    $query->where('from_date', '<', $endFullDateTime)
                          ->where('to_date', '>', $startFullDateTime);
                })
                ->orderByDesc('users.priority')
                ->select('bookings.*', 'users.name as user_name', 'users.priority as user_priority')
                ->first();

            $isOccupied = !is_null($existing);
            $canOverride = $isOccupied && $this->user->priority > ($existing->user_priority ?? 0);

            return [
                'id' => $desk->id,
                'identifier' => $desk->identifier,
                'is_available' => !$isOccupied,
                'is_occupied' => $isOccupied,
                'occupied_by' => $existing->user_name ?? null,
                'can_override' => $canOverride,
            ];
        });

        return response()->json([
            'plan' => $plan,
            'desks' => $desks,
            'is_exclusive' => false
        ]);
    }

    public function getOtherDesk(Request $request, Desk $desk)
    {
        $booking = $request->session()->get('booking');

        $start = Carbon::parse(($booking['start_date'] ?? now()) . ' ' . ($booking['start_time'] ?? '00:00:00'));
        $end = Carbon::parse(($booking['end_date'] ?? now()) . ' ' . ($booking['end_time'] ?? '23:59:59'));

        // Verifica prenotazione esclusiva dell'area
        $exclusiveBooking = $this->checkExclusiveAreaBooking($desk->id, $start, $end);
        
        if ($exclusiveBooking) {
            return response()->json([
                'message' => "L'area è prenotata in modo esclusivo da {$exclusiveBooking->team_name}"
            ], 403);
        }

        $existingBooking = $desk->bookings()
                ->where('status', 0)
                ->where(function ($query) use ($start, $end) {
                        $query->where('from_date', '<', $end)
                            ->where('to_date', '>', $start);
                    })
                ->with('user:id,name,priority')
                ->first();

        if ($existingBooking && $existingBooking->user && $this->user->priority > $existingBooking->user->priority) {
            $existingBooking->update(['status' => 2]);

            return response()->json(['success' => 'Continua e prendi la prenotazione'], 200);
        }

        return response()->json(['message' => 'Non puoi prendere questa postazione.'], 403);
    }

    public function multiCancel(Request $request)
    {
        $bookingIds = $request->input('booking_ids', []);

        if (!$bookingIds) {
            return back()->with('error', 'Nessuna prenotazione selezionata.');
        }

        Booking::whereIn('id', $bookingIds)->each(function ($booking) {
            Presence::where('booking_id', $booking->id)->delete();
            $booking->update(['status' => 1]);
        });

        return back()->with('success', 'Prenotazioni cancellate con successo.');
    }

    public function complete(Request $request)
    {
        $bookingData = $request->session()->get('booking');
        $startDate = Carbon::parse($bookingData['start_date']);
        $endDate = Carbon::parse($bookingData['end_date']);
        
        // Recupera is_exclusive dalla sessione (salvato in stepThree)
        $isExclusive = $bookingData['is_exclusive'] ?? false;
        
        // Verifica permessi per prenotazione esclusiva
        if ($isExclusive && !$this->user->team->can_book_exclusive) {
            return back()->with('error', 'Il tuo team non ha i permessi per effettuare prenotazioni esclusive.');
        }

        // Ottieni il plan_id dalla desk (usa il desk_id dalla sessione)
        $desk = Desk::findOrFail($bookingData['desk_id']);
        $planId = $desk->plan_id;

        // Se è una prenotazione esclusiva, verifica che non ci siano altre prenotazioni
        if ($isExclusive) {
            $from = Carbon::parse("{$bookingData['start_date']} {$bookingData['start_time']}");
            $to = Carbon::parse("{$bookingData['end_date']} {$bookingData['end_time']}");
            
            $conflictingBookings = Booking::whereHas('desk', function($query) use ($planId) {
                    $query->where('plan_id', $planId);
                })
                ->where('status', 0)
                ->where(function ($query) use ($from, $to) {
                    $query->where('from_date', '<', $to)
                          ->where('to_date', '>', $from);
                })
                ->whereHas('user', function($query) {
                    $query->where('team_id', '!=', $this->user->team_id);
                })
                ->exists();

            if ($conflictingBookings) {
                return back()->with('error', 'Impossibile creare una prenotazione esclusiva: ci sono già altre prenotazioni nell\'area nel periodo selezionato.');
            }
        }

        $booking_save = [
            'desk_id' => $bookingData['desk_id'],
            'plan_id' => $planId,
            'start_date' => $bookingData['start_date'],
            'end_date' => $bookingData['end_date'],
            'start_time' => $bookingData['start_time'],
            'end_time' => $bookingData['end_time'],
            'from_date' => Carbon::createFromTimestamp(strtotime($bookingData['start_date'] . $bookingData['start_time'] . ":00"))->setTimezone($this->timezone),
            'to_date' => Carbon::createFromTimestamp(strtotime($bookingData['end_date'] . $bookingData['end_time'] . ":00"))->setTimezone($this->timezone),
            'user_id' => $this->user->id,
            'status' => 0,
            'is_exclusive' => $isExclusive
        ];

        $dates = collect();
        
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $start_time = $currentDate->equalTo($bookingData['start_date']) ? $bookingData['start_time'] : '07:30';
            $end_time = $currentDate->equalTo($bookingData['end_date']) ? $bookingData['end_time'] : '21:00';
    
            if ($currentDate->isWeekday() && !Holidays::isHoliday($currentDate)) {
                $dates->push([
                    'desk_id' => $bookingData['desk_id'],
                    'plan_id' => $planId,
                    'start_date' => $currentDate->toDateString(),
                    'end_date' => $currentDate->toDateString(),
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'from_date' => Carbon::parse("{$currentDate->toDateString()} {$start_time}"),
                    'to_date' => Carbon::parse("{$currentDate->toDateString()} {$end_time}"),
                    'user_id' => $this->user->id,
                    'status' => 0,
                    'is_exclusive' => $isExclusive
                ]);
            }
            $currentDate->addDay();
        }

        DB::transaction(function () use ($dates, $booking_save) {
            $dates->each(function ($data) {
                Booking::create($data);
            });
            
            $this->user->notify(new \App\Notifications\NewBooking($booking_save));
        });

        $message = $isExclusive 
            ? 'Prenotazione esclusiva completata! L\'intera area è riservata al tuo team.' 
            : 'Prenotazione completata!';

        $request->session()->forget('booking');
        return redirect()->route('booking.my')->with('success', $message);
    }

    public function checkAvailability(Request $request)
    {
        $request->validate([
            'desk_identifier' => 'required|string',
            'date' => 'required|date',
        ]);

        $desk = Desk::where('identifier', $request->desk_identifier)->firstOrFail();

        // Verifica prenotazioni esclusive
        $exclusiveBooking = Booking::where('plan_id', $desk->plan_id)
            ->where('is_exclusive', true)
            ->where('status', 0)
            ->whereDate('from_date', '<=', $request->date)
            ->whereDate('to_date', '>=', $request->date)
            ->with(['user.team'])
            ->whereHas('user', function($query) {
                $query->where('team_id', '!=', $this->user->team_id);
            })
            ->first();

        if ($exclusiveBooking) {
            $teamName = $exclusiveBooking->user->team->name ?? 'Un altro team';
            
            return response()->json([
                'success' => false,
                'desk' => $desk,
                'isOccupied' => true,
                'is_exclusive' => true,
                'message' => "L'intera area è prenotata in modo esclusivo da {$teamName}",
                'availableHours' => []
            ]);
        }

        $workingHours = collect(range(27000, 75600, 1800))->map(function ($t) {
            return gmdate('H:i', $t);
        })->toArray();

        $bookedSlots = Booking::where('desk_id', $desk->id)
            ->where('status', 0)
            ->whereDate('from_date', '<=', $request->date)
            ->whereDate('to_date', '>=', $request->date)
            ->get()
            ->flatMap(function ($b) {
                $slots = [];
                $start = Carbon::parse($b->from_date);
                $end = Carbon::parse($b->to_date);
                while ($start < $end) {
                    $slots[] = $start->format('H:i');
                    $start->addMinutes(30);
                }
                return $slots;
            })->toArray();

        $availableHours = array_values(array_diff($workingHours, $bookedSlots));

        return response()->json([
            'success' => true,
            'desk' => $desk,
            'isOccupied' => !empty($bookedSlots),
            'is_exclusive' => false,
            'availableHours' => $availableHours,
        ]);
    }

    public function myBookings()
    {
        $bookings = Booking::with('desk')
            ->where('user_id', $this->user->id)
            ->where('status', 0)
            ->orderby('from_date')
            ->get();

        return view('booking.my-bookings', compact('bookings'));
    }

    public function history()
    {
        $bookings = Booking::where('user_id', $this->user->id)
            ->where(function ($query) {
                $query->where('to_date', '<', now())
                      ->orWhereIn('status', [1, 2, 3]);
            })
            ->orderByDesc('to_date')
            ->paginate(10);

        return view('booking.history', compact('bookings'));
    }

    public function cancel($id)
    {
        $booking = Booking::where('id', $id)
            ->where('user_id', $this->user->id)
            ->firstOrFail();

        $booking->update(['status' => 1]);

        return redirect()->route('bookings.current')->with('success', 'Prenotazione cancellata con successo.');
    }

    public function current()
    {
        $bookings = Booking::with('desk')
            ->where('user_id', $this->user->id)
            ->where('to_date', '>=', now())
            ->where('status', 0)
            ->orderBy('from_date')
            ->paginate(10);

        return view('booking.current', compact('bookings'));
    }

    /**
     * Verifica se esiste una prenotazione esclusiva dell'area per il periodo specificato
     */
    protected function checkExclusiveAreaBooking($deskId, $from, $to)
    {
        $desk = Desk::find($deskId);
        if (!$desk) return null;

        $booking = Booking::where('plan_id', $desk->plan_id)
            ->where('is_exclusive', true)
            ->where('status', 0)
            ->where(function ($query) use ($from, $to) {
                $query->where('from_date', '<', $to)
                      ->where('to_date', '>', $from);
            })
            ->with(['user.team'])
            ->whereHas('user', function($query) {
                $query->where('team_id', '!=', $this->user->team_id);
            })
            ->first();

        if ($booking) {
            // Aggiungi il nome del team come attributo accessibile
            $booking->team_name = $booking->user->team->name ?? 'Team sconosciuto';
        }

        return $booking;
    }
}