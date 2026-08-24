<?php

namespace App\Notifications;

use App\Models\Desk;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBooking extends Notification implements ShouldQueue
{
    use Queueable;

    protected $booking;

    /**
     * Create a new notification instance.
     */
    public function __construct($booking)
    {
        $this->booking = $booking;
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {

        $desk = Desk::with('plan.workplace')->find($this->booking['desk_id']);

        $deskLine = $desk
            ? 'Scrivania: '.$desk->identifier.' - Piano: '.($desk->plan->description ?? '').' Sede: '.($desk->plan->workplace->name ?? '')
            : 'Scrivania non disponibile';

        return (new MailMessage)
            ->subject('Conferma Prenotazione')
            ->greeting('Ciao '.$notifiable->name)
            ->line('La tua prenotazione è stata convalidata!')
            ->line('Data/e '.Carbon::parse($this->booking['start_date'])->format('d-m-Y').' - '.$this->booking['start_time'].' # '.Carbon::parse($this->booking['end_date'])->format('d-m-Y').' - '.$this->booking['end_time'])
            ->line('La tua prenotazione è stata convalidata!')
            ->line($deskLine)
            ->action('Vedi Prenotazione', url('/my-bookings'))
            ->line("Grazie per aver utilizzato l'applicativo di prenotazione postazioni !")
            ->salutation('Buon lavoro');

    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
