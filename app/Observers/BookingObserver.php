<?php

namespace App\Observers;

use App\Models\Booking;
use App\Models\Presence;
use Illuminate\Support\Facades\Log;

class BookingObserver
{
    
    /**
     * Handle the Booking "created" event.
     */
    public function created(Booking $booking)
    {
        // Solo per prenotazioni confermate (status = 0)
        if ($booking->status === 0) {
            Presence::updateOrCreate(
                [
                    'user_id' => $booking->user_id,
                    'date'    => $booking->start_date,
                ],
                [
                    'status' => 'presente',
                    'booking_id' => $booking->id,
                ]
            );
        }
    }

    public function updated(Booking $booking)
    {
        // Se la data di inizio è cambiata o lo status è cambiato
        $wasConfirmed = $booking->getOriginal('status') == 0;
        $isNowConfirmed = $booking->status == 0;

        $oldDate = $booking->getOriginal('start_date');
        $newDate = $booking->start_date;

        // Caso 1: status diventa 0 (confermato) ⇒ crea/aggiorna presenza
        if ($isNowConfirmed) {
            Presence::updateOrCreate(
                ['user_id' => $booking->user_id, 'date' => $newDate],
                ['status' => 'presente']
            );
        }

        // Caso 2: era confermato ma ora non lo è più ⇒ elimina la presenza
        if ($wasConfirmed && !$isNowConfirmed) {
            Presence::where('user_id', $booking->user_id)
                ->where('date', $oldDate)
                ->where('status', 'presente')
                ->delete();
        }

        // Caso 3: la data è cambiata ⇒ rimuovi la presenza dalla vecchia data
        if ($wasConfirmed && $oldDate !== $newDate) {
            Presence::where('user_id', $booking->user_id)
                ->where('date', $oldDate)
                ->where('status', 'presente')
                ->delete();

            // (facoltativo) aggiorna la nuova data se non lo hai già fatto sopra
            if ($isNowConfirmed) {
                Presence::updateOrCreate(
                    ['user_id' => $booking->user_id, 'date' => $newDate],
                    ['status' => 'presente']
                );
            }
        }
    }


    /**
     * Handle the Booking "deleted" event.
     */
    public function deleted(Booking $booking): void
    {
          // Rimuove la presenza solo se è marcata come "presente"
        Presence::where('user_id', $booking->user_id)
            ->where('date', $booking->start_date)
            ->where('status', 'presente')
            ->delete();
    }

    /**
     * Handle the Booking "restored" event.
     */
    public function restored(Booking $booking): void
    {
        //
    }

    /**
     * Handle the Booking "force deleted" event.
     */
    public function forceDeleted(Booking $booking): void
    {
        //
    }
}
