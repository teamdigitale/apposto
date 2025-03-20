<?php

namespace App\Notifications;

use App\Models\Desk;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

use function Illuminate\Log\log;

class NewBooking extends Notification
{
    use Queueable;

    protected $booking;

    /**
     * Create a new notification instance.
     */
    public function __construct($booking)
    {
        $this->booking = $booking;
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

        $desk = Desk::where('id', '=', $this->booking->desk_id)->first();
       
        return (new MailMessage)
                    ->subject('Conferma Prenotazione')
                    ->greeting('Ciao ' . $notifiable->name)
                    ->line('La tua prenotazione è stata convalidata!')
                    ->line("Data/e ". Carbon::parse($this->booking->from_date)->format('d-m-Y H:i'). " # ". Carbon::parse($this->booking->to_date)->format('d-m-Y H:i'))
                    ->line('La tua prenotazione è stata convalidata!')
                    ->line("Scrivania: " . $desk->identifier . " - Piano: ". $desk->plan->description . " Sede: ". $desk->plan->workplace->name)
                    ->action('Vedi Prenotazione', url('/my-bookings' ))
                    ->line("Grazie per aver utilizzato l'applicativo di prenotazione postazioni !")
                    ->salutation("Buon lavoro");
                    
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
