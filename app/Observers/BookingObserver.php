<?php

namespace App\Observers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Presence;

class BookingObserver
{
    public function created(Booking $booking): void
    {
        if ((int) $booking->status === BookingStatus::Confirmed->value) {
            Presence::updateOrCreate(
                [
                    'user_id' => $booking->user_id,
                    'date' => $booking->start_date,
                ],
                [
                    'status' => 'presente',
                    'booking_id' => $booking->id,
                ]
            );
        }
    }

    public function updated(Booking $booking): void
    {
        $wasConfirmed = (int) $booking->getOriginal('status') === BookingStatus::Confirmed->value;
        $isNowConfirmed = (int) $booking->status === BookingStatus::Confirmed->value;
        $oldDate = $booking->getOriginal('start_date');
        $newDate = $booking->start_date;

        if ($isNowConfirmed) {
            Presence::updateOrCreate(
                ['user_id' => $booking->user_id, 'date' => $newDate],
                ['status' => 'presente', 'booking_id' => $booking->id]
            );
        }

        if ($wasConfirmed && ! $isNowConfirmed) {
            Presence::where('user_id', $booking->user_id)
                ->where('date', $oldDate)
                ->where('status', 'presente')
                ->delete();
        }

        if ($wasConfirmed && $oldDate !== $newDate) {
            Presence::where('user_id', $booking->user_id)
                ->where('date', $oldDate)
                ->where('status', 'presente')
                ->delete();

            if ($isNowConfirmed) {
                Presence::updateOrCreate(
                    ['user_id' => $booking->user_id, 'date' => $newDate],
                    ['status' => 'presente', 'booking_id' => $booking->id]
                );
            }
        }
    }

    public function deleted(Booking $booking): void
    {
        Presence::where('user_id', $booking->user_id)
            ->where('date', $booking->start_date)
            ->where('status', 'presente')
            ->delete();
    }
}
