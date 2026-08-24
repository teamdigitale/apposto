<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Exceptions\BookingConflictException;
use App\Helpers\Holidays;
use App\Models\Booking;
use App\Models\Desk;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\NewBooking;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function overlappingQuery(CarbonInterface|string $from, CarbonInterface|string $to): Builder
    {
        return Booking::query()
            ->where('status', BookingStatus::Confirmed)
            ->where('from_date', '<', $to)
            ->where('to_date', '>', $from);
    }

    public function findExclusiveAreaBooking(
        int $planId,
        CarbonInterface|string $from,
        CarbonInterface|string $to,
        ?int $exceptTeamId = null
    ): ?Booking {
        $query = $this->overlappingQuery($from, $to)
            ->where('plan_id', $planId)
            ->where('is_exclusive', true)
            ->with(['user.team']);

        if ($exceptTeamId !== null) {
            $query->whereHas('user', fn ($q) => $q->where('team_id', '!=', $exceptTeamId));
        }

        return $query->first();
    }

    public function desksAvailability(Plan $plan, CarbonInterface|string $from, CarbonInterface|string $to, User $user): array
    {
        $exclusive = $this->findExclusiveAreaBooking($plan->id, $from, $to, $user->team_id);

        if ($exclusive) {
            return [
                'exclusive' => true,
                'booking' => $exclusive,
                'desks' => collect(),
            ];
        }

        $deskIds = $plan->desks->pluck('id');

        $bookingsByDesk = $this->overlappingQuery($from, $to)
            ->whereIn('desk_id', $deskIds)
            ->with('user:id,name,priority,team_id')
            ->get()
            ->groupBy('desk_id');

        $desks = $plan->desks->map(function (Desk $desk) use ($bookingsByDesk, $user) {
            $existing = ($bookingsByDesk->get($desk->id) ?? collect())
                ->sortByDesc(fn (Booking $booking) => $booking->user->priority ?? 0)
                ->first();

            $isOccupied = $existing !== null;

            return [
                'id' => $desk->id,
                'identifier' => $desk->identifier,
                'is_available' => ! $isOccupied,
                'is_occupied' => $isOccupied,
                'occupied_by' => $existing?->user?->name,
                'can_override' => $isOccupied && $user->priority > ($existing->user->priority ?? 0),
            ];
        });

        return [
            'exclusive' => false,
            'booking' => null,
            'desks' => $desks,
        ];
    }

    public function findHighestPriorityOccupant(int $deskId, CarbonInterface|string $from, CarbonInterface|string $to): ?Booking
    {
        return $this->overlappingQuery($from, $to)
            ->where('desk_id', $deskId)
            ->join('users', 'bookings.user_id', '=', 'users.id')
            ->orderByDesc('users.priority')
            ->select('bookings.*', 'users.name as user_name', 'users.priority as user_priority')
            ->first();
    }

    public function overrideDesk(User $user, Desk $desk, CarbonInterface|string $from, CarbonInterface|string $to): void
    {
        DB::transaction(function () use ($user, $desk, $from, $to) {
            Desk::whereKey($desk->id)->lockForUpdate()->firstOrFail();

            $exclusive = $this->findExclusiveAreaBooking($desk->plan_id, $from, $to, $user->team_id);
            if ($exclusive) {
                throw new BookingConflictException(
                    "L'area è prenotata in modo esclusivo da ".$this->teamLabel($exclusive)
                );
            }

            $existing = $this->overlappingQuery($from, $to)
                ->where('desk_id', $desk->id)
                ->with('user:id,name,priority')
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->user && $user->priority > $existing->user->priority) {
                $existing->update(['status' => BookingStatus::Overridden->value]);

                return;
            }

            throw new BookingConflictException('Non puoi prendere questa postazione.');
        });
    }

    public function complete(User $user, array $bookingData): void
    {
        if (empty($bookingData['desk_id']) || empty($bookingData['start_date']) || empty($bookingData['end_date'])) {
            throw new BookingConflictException('Dati prenotazione incompleti. Ripeti il flusso.');
        }

        $isExclusive = (bool) ($bookingData['is_exclusive'] ?? false);

        if ($isExclusive && ! $user->team?->can_book_exclusive) {
            throw new BookingConflictException('Il tuo team non ha i permessi per effettuare prenotazioni esclusive.');
        }

        DB::transaction(function () use ($user, $bookingData, $isExclusive) {
            $desk = Desk::whereKey($bookingData['desk_id'])->lockForUpdate()->firstOrFail();
            $planId = $desk->plan_id;

            $rangeFrom = Carbon::parse("{$bookingData['start_date']} {$bookingData['start_time']}");
            $rangeTo = Carbon::parse("{$bookingData['end_date']} {$bookingData['end_time']}");

            if ($isExclusive) {
                $conflicts = $this->overlappingQuery($rangeFrom, $rangeTo)
                    ->where(function ($query) use ($planId) {
                        $query->where('plan_id', $planId)
                            ->orWhereHas('desk', fn ($q) => $q->where('plan_id', $planId));
                    })
                    ->whereHas('user', fn ($q) => $q->where('team_id', '!=', $user->team_id))
                    ->lockForUpdate()
                    ->exists();

                if ($conflicts) {
                    throw new BookingConflictException(
                        'Impossibile creare una prenotazione esclusiva: ci sono già altre prenotazioni nell\'area nel periodo selezionato.'
                    );
                }
            }

            $dates = $this->expandWorkdays($user, $bookingData, $desk, $planId, $isExclusive);

            if ($dates->isEmpty()) {
                throw new BookingConflictException('Nessun giorno lavorativo nel periodo selezionato.');
            }

            foreach ($dates as $data) {
                $overlap = $this->overlappingQuery($data['from_date'], $data['to_date'])
                    ->where('desk_id', $desk->id)
                    ->with('user:id,priority')
                    ->lockForUpdate()
                    ->first();

                if ($overlap) {
                    if ($user->priority > ($overlap->user->priority ?? 0)) {
                        $overlap->update(['status' => BookingStatus::Overridden->value]);
                    } else {
                        throw new BookingConflictException('La postazione non è più disponibile per uno dei giorni selezionati.');
                    }
                }

                Booking::create($data);
            }

            $user->notify(new NewBooking($this->summaryPayload($bookingData, $desk, $planId, $isExclusive, $user)));
        });
    }

    public function cancelOwned(User $user, array $bookingIds): int
    {
        $bookings = Booking::whereIn('id', $bookingIds)
            ->where('user_id', $user->id)
            ->get();

        foreach ($bookings as $booking) {
            $this->cancel($booking);
        }

        return $bookings->count();
    }

    public function cancel(Booking $booking): void
    {
        $booking->update(['status' => BookingStatus::Cancelled->value]);
    }

    public function teamLabel(Booking $booking): string
    {
        return $booking->user?->team?->label ?? 'Un altro team';
    }

    public function availableHoursForDesk(Desk $desk, string $date, User $user): array
    {
        $exclusive = Booking::query()
            ->where('plan_id', $desk->plan_id)
            ->where('is_exclusive', true)
            ->where('status', BookingStatus::Confirmed)
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->with(['user.team'])
            ->whereHas('user', fn ($q) => $q->where('team_id', '!=', $user->team_id))
            ->first();

        if ($exclusive) {
            return [
                'exclusive' => true,
                'message' => "L'intera area è prenotata in modo esclusivo da ".$this->teamLabel($exclusive),
                'availableHours' => [],
                'isOccupied' => true,
            ];
        }

        $workingHours = collect(range(27000, 75600, 1800))->map(fn ($t) => gmdate('H:i', $t))->all();

        $bookedSlots = Booking::query()
            ->where('desk_id', $desk->id)
            ->where('status', BookingStatus::Confirmed)
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->get()
            ->flatMap(function (Booking $booking) {
                $slots = [];
                $start = Carbon::parse($booking->from_date);
                $end = Carbon::parse($booking->to_date);
                while ($start < $end) {
                    $slots[] = $start->format('H:i');
                    $start->addMinutes(30);
                }

                return $slots;
            })->all();

        return [
            'exclusive' => false,
            'message' => null,
            'availableHours' => array_values(array_diff($workingHours, $bookedSlots)),
            'isOccupied' => $bookedSlots !== [],
        ];
    }

    private function expandWorkdays(User $user, array $bookingData, Desk $desk, int $planId, bool $isExclusive): Collection
    {
        $startDate = Carbon::parse($bookingData['start_date'])->startOfDay();
        $endDate = Carbon::parse($bookingData['end_date'])->startOfDay();
        $dates = collect();
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $startTime = $currentDate->equalTo($startDate) ? $bookingData['start_time'] : '07:30';
            $endTime = $currentDate->equalTo($endDate) ? $bookingData['end_time'] : '21:00';

            if ($currentDate->isWeekday() && ! Holidays::isHoliday($currentDate)) {
                $dates->push($this->bookingAttributes(
                    $user,
                    $desk->id,
                    $planId,
                    $currentDate->toDateString(),
                    $startTime,
                    $endTime,
                    $isExclusive
                ));
            }

            $currentDate->addDay();
        }

        return $dates;
    }

    private function bookingAttributes(
        User $user,
        int $deskId,
        int $planId,
        string $date,
        string $startTime,
        string $endTime,
        bool $isExclusive
    ): array {
        $from = Carbon::parse("{$date} {$startTime}");
        $to = Carbon::parse("{$date} {$endTime}");

        return [
            'desk_id' => $deskId,
            'plan_id' => $planId,
            'start_date' => $date,
            'end_date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'from_date' => $from,
            'to_date' => $to,
            'user_id' => $user->id,
            'status' => BookingStatus::Confirmed->value,
            'is_exclusive' => $isExclusive,
        ];
    }

    private function summaryPayload(array $bookingData, Desk $desk, int $planId, bool $isExclusive, User $user): array
    {
        return [
            'desk_id' => $desk->id,
            'plan_id' => $planId,
            'start_date' => $bookingData['start_date'],
            'end_date' => $bookingData['end_date'],
            'start_time' => $bookingData['start_time'],
            'end_time' => $bookingData['end_time'],
            'from_date' => Carbon::parse("{$bookingData['start_date']} {$bookingData['start_time']}"),
            'to_date' => Carbon::parse("{$bookingData['end_date']} {$bookingData['end_time']}"),
            'user_id' => $user->id,
            'status' => BookingStatus::Confirmed->value,
            'is_exclusive' => $isExclusive,
        ];
    }
}
