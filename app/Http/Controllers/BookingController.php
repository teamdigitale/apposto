<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Exceptions\BookingConflictException;
use App\Models\Booking;
use App\Models\Desk;
use App\Models\Plan;
use App\Models\Workplace;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    private function user()
    {
        return auth()->user();
    }

    public function stepOne(Request $request)
    {
        $request->session()->forget('booking');
        $user = $this->user();

        if (! $user->team) {
            return redirect()->route('dashboard')
                ->with('error', 'Il tuo account non è ancora associato a nessun team. Contatta un amministratore.');
        }

        $planWorkplaceIds = $user->team->plans->pluck('workplace_id')->unique();

        $workplaces = Cache::remember("workplaces_user_{$user->id}", 60, function () use ($planWorkplaceIds) {
            return Workplace::whereIn('id', $planWorkplaceIds)->get();
        });

        return view('booking.step-one', compact('workplaces'));
    }

    public function checkWorkstationAvailability(Request $request)
    {
        $user = $this->user();
        $isMultiDay = $user->team->allow_multi_day;

        $rules = [
            'start_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
        ];

        if ($isMultiDay) {
            $rules['end_date'] = 'required|date';
        }

        $validated = $request->validate($rules);

        if (! $isMultiDay) {
            $validated['end_date'] = $request->input('start_date');
        }

        if (! $user->default_workstation_id) {
            return response()->json(['available' => false, 'message' => 'Nessuna postazione preferita configurata']);
        }

        $desk = Desk::find($user->default_workstation_id);
        if (! $desk) {
            return response()->json(['available' => false, 'message' => 'Nessuna postazione preferita configurata']);
        }

        $from = Carbon::parse("{$validated['start_date']} {$request->start_time}");
        $to = Carbon::parse("{$validated['end_date']} {$request->end_time}");

        $exclusiveBooking = $this->bookings->findExclusiveAreaBooking(
            $desk->plan_id,
            $from,
            $to,
            $user->team_id
        );

        if ($exclusiveBooking) {
            return response()->json([
                'available' => false,
                'is_exclusive' => true,
                'message' => "L'area è prenotata in modo esclusivo da {$this->bookings->teamLabel($exclusiveBooking)} fino al ".
                    Carbon::parse($exclusiveBooking->to_date)->format('d/m/Y H:i'),
            ]);
        }

        $existing = $this->bookings->findHighestPriorityOccupant($user->default_workstation_id, $from, $to);

        $isOccupied = ! is_null($existing);
        $canOverride = $isOccupied && $user->priority > ($existing->user_priority ?? 0);

        if (! $isOccupied || $canOverride) {
            $validated['desk_id'] = $desk->id;
            $request->session()->put('booking', $validated);
        }

        return response()->json([
            'available' => ! $isOccupied,
            'can_override' => $canOverride,
            'occupied_by' => $existing->user_name ?? null,
            'message' => $existing && ! $canOverride
                ? 'Postazione occupata'.($existing->user_name ? " da {$existing->user_name}" : '')
                : null,
        ]);
    }

    public function stepTwo(Request $request)
    {
        $user = $this->user();

        if (! $user->team->allow_multi_day) {
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
        $canBookExclusive = $user->team->can_book_exclusive;

        return view('booking.step-two', compact('workplace', 'request', 'canBookExclusive'));
    }

    public function stepThree(Request $request)
    {
        $validated = $request->validate([
            'desk_id' => 'required|exists:desks,id',
            'is_exclusive' => 'nullable|boolean',
        ]);

        $bookingData = $request->session()->get('booking', []);
        $bookingData['desk_id'] = $validated['desk_id'];
        $bookingData['is_exclusive'] = $validated['is_exclusive'] ?? false;
        $request->session()->put('booking', $bookingData);

        $desk = Desk::with('plan.workplace')->findOrFail($request->desk_id);
        $canBookExclusive = $this->user()->team->can_book_exclusive;

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
        $user = $this->user();

        $startDate = Carbon::parse($booking['start_date'] ?? now());
        $endDate = Carbon::parse($booking['end_date'] ?? now());
        $startTime = $booking['start_time'] ?? '00:00:00';
        $endTime = $booking['end_time'] ?? '23:59:59';

        $from = $startDate->copy()->setTimeFromTimeString($startTime);
        $to = $endDate->copy()->setTimeFromTimeString($endTime);

        $result = $this->bookings->desksAvailability($plan, $from, $to, $user);

        if ($result['exclusive']) {
            $exclusive = $result['booking'];

            return response()->json([
                'plan' => $plan,
                'desks' => [],
                'is_exclusive' => true,
                'message' => "L'intera area è prenotata in modo esclusivo da ".$this->bookings->teamLabel($exclusive),
                'exclusive_until' => Carbon::parse($exclusive->to_date)->format('d/m/Y H:i'),
            ]);
        }

        return response()->json([
            'plan' => $plan,
            'desks' => $result['desks'],
            'is_exclusive' => false,
        ]);
    }

    public function getOtherDesk(Request $request, Desk $desk)
    {
        $booking = $request->session()->get('booking');
        $start = Carbon::parse(($booking['start_date'] ?? now()).' '.($booking['start_time'] ?? '00:00:00'));
        $end = Carbon::parse(($booking['end_date'] ?? now()).' '.($booking['end_time'] ?? '23:59:59'));

        try {
            $this->bookings->overrideDesk($this->user(), $desk, $start, $end);
        } catch (BookingConflictException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        return response()->json(['success' => 'Continua e prendi la prenotazione'], 200);
    }

    public function multiCancel(Request $request)
    {
        $bookingIds = $request->input('booking_ids', []);

        if (! $bookingIds) {
            return back()->with('error', 'Nessuna prenotazione selezionata.');
        }

        $cancelled = $this->bookings->cancelOwned($this->user(), $bookingIds);

        if ($cancelled === 0) {
            return back()->with('error', 'Nessuna prenotazione di tua proprietà selezionata.');
        }

        return back()->with('success', 'Prenotazioni cancellate con successo.');
    }

    public function complete(Request $request)
    {
        $bookingData = $request->session()->get('booking', []);
        if ($request->filled('desk_id')) {
            $bookingData['desk_id'] = $request->input('desk_id');
        }
        $isExclusive = (bool) ($bookingData['is_exclusive'] ?? false);

        try {
            $this->bookings->complete($this->user(), $bookingData);
        } catch (BookingConflictException $e) {
            return back()->with('error', $e->getMessage());
        }

        $request->session()->forget('booking');

        $message = $isExclusive
            ? 'Prenotazione esclusiva completata! L\'intera area è riservata al tuo team.'
            : 'Prenotazione completata!';

        return redirect()->route('booking.my')->with('success', $message);
    }

    public function checkAvailability(Request $request)
    {
        $request->validate([
            'desk_identifier' => 'required|string',
            'date' => 'required|date',
        ]);

        $desk = Desk::where('identifier', $request->desk_identifier)->firstOrFail();
        $result = $this->bookings->availableHoursForDesk($desk, $request->date, $this->user());

        if ($result['exclusive']) {
            return response()->json([
                'success' => false,
                'desk' => $desk,
                'isOccupied' => true,
                'is_exclusive' => true,
                'message' => $result['message'],
                'availableHours' => [],
            ]);
        }

        return response()->json([
            'success' => true,
            'desk' => $desk,
            'isOccupied' => $result['isOccupied'],
            'is_exclusive' => false,
            'availableHours' => $result['availableHours'],
        ]);
    }

    public function myBookings()
    {
        $bookings = Booking::with('desk.plan.workplace')
            ->where('user_id', $this->user()->id)
            ->where('status', BookingStatus::Confirmed)
            ->orderBy('from_date')
            ->get();

        return view('booking.my-bookings', compact('bookings'));
    }

    public function history()
    {
        $bookings = Booking::with('desk')
            ->where('user_id', $this->user()->id)
            ->where(function ($query) {
                $query->where('to_date', '<', now())
                    ->orWhereIn('status', [
                        BookingStatus::Cancelled->value,
                        BookingStatus::Overridden->value,
                        BookingStatus::Completed->value,
                    ]);
            })
            ->orderByDesc('to_date')
            ->paginate(10);

        return view('booking.history', compact('bookings'));
    }

    public function cancel(Booking $booking)
    {
        $this->authorize('cancel', $booking);
        $this->bookings->cancel($booking);

        return redirect()->route('bookings.current')->with('success', 'Prenotazione cancellata con successo.');
    }

    public function current()
    {
        $bookings = Booking::with('desk')
            ->where('user_id', $this->user()->id)
            ->where('to_date', '>=', now())
            ->where('status', BookingStatus::Confirmed)
            ->orderBy('from_date')
            ->paginate(10);

        return view('booking.current', compact('bookings'));
    }
}
