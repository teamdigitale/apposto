<?php

namespace App\Http\Controllers;

use App\Helpers\Holidays;
use App\Models\{Booking, Desk, Plan, Presence, Workplace};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Cache};

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

        return view('booking.step-two', compact('workplace', 'request'));
    }

    public function stepThree(Request $request)
    {
        $request->validate([
            'desk_id' => 'required|exists:desks,id',
        ]);

        $desk = Desk::findOrFail($request->desk_id);
        return view('booking.step-three', compact('desk', 'request'));
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
        ]);
    }

    public function getOtherDesk(Request $request, Desk $desk)
    {
        $booking = $request->session()->get('booking');

        $start = Carbon::parse(($booking['start_date'] ?? now()) . ' ' . ($booking['start_time'] ?? '00:00:00'));
        $end = Carbon::parse(($booking['end_date'] ?? now()) . ' ' . ($booking['end_time'] ?? '23:59:59'));

        $existingBooking = $desk->bookings()
                ->where('status', 0)
                ->where(function ($query) use ($start, $end) {
                        $query->where('from_date', '<', $end)
                            ->where('to_date', '>', $start);
                    })
                ->with('user:id,name,priority')
                ->first();

        if ($existingBooking && $existingBooking->user && $this->user->priority > $existingBooking->user->priority) {
            $existingBooking->update(['status' => 2]); // contrassegna come "rubata"

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
       // dd($bookingData);
        $startDate = Carbon::parse($bookingData['start_date']);
        $endDate = Carbon::parse($bookingData['end_date']);

        $booking_save = [
            'desk_id' => $request->desk_id,
            'start_date' => $bookingData['start_date'],
            'end_date' => $bookingData['end_date'],
            'start_time' => $bookingData['start_time'],
            'end_time' => $bookingData['end_time'],
            'from_date'  => Carbon::createFromTimestamp(strtotime($bookingData['start_date'] . $bookingData['start_time'] . ":00"))->setTimezone($this->timezone),
            'to_date'  => Carbon::createFromTimestamp(strtotime($bookingData['end_date'] . $bookingData['end_time'] . ":00"))->setTimezone($this->timezone),
            'user_id' => $this->user->id,
            'status'    => 0
        ];
        if($startDate == $endDate){
            if(!Holidays::isHoliday($startDate)){
                $booking = Booking::create($booking_save);
            }
        }
        else
        {
            $dates = collect();
            
            while ($startDate->lte($endDate)) {
                $current = $startDate->copy();
                $start_time = $current->equalTo($bookingData['start_date']) ? $bookingData['start_time'] : '07:30';
                $end_time = $current->equalTo($bookingData['end_date']) ? $bookingData['end_time'] : '21:00';
        
                if ($current->isWeekday() && !Holidays::isHoliday($current)) {
                    $dates->push([
                        'desk_id' => $request->desk_id,
                        'start_date' => $current->toDateString(),
                        'end_date' => $current->toDateString(),
                        'start_time' => $start_time,
                        'end_time' => $end_time,
                        'from_date' => Carbon::parse("{$current->toDateString()} {$start_time}"),
                        'to_date' => Carbon::parse("{$current->toDateString()} {$end_time}"),
                        'user_id' => $this->user->id,
                        'status' => 0,
                    ]);
                }
                $startDate->addDay();
            }

            $dates->each(function ($data) {
                Booking::create($data);
            });
        }
        
        $this->user->notify(new \App\Notifications\NewBooking($booking_save));
        

        $request->session()->forget('booking');
        return redirect()->route('booking.my')->with('success', 'Prenotazione completata!');
    }

    public function checkAvailability(Request $request)
    {
        $request->validate([
            'desk_identifier' => 'required|string',
            'date' => 'required|date',
        ]);

        $desk = Desk::where('identifier', $request->desk_identifier)->firstOrFail();

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
}
