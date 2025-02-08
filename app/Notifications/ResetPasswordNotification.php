<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public readonly string $token)
    {}

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
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Richiesta cambio password - Apposto")
            ->greeting("Ciao" . ' '  . $notifiable->name . ',')
            ->line("Stai ricevendo questa email perché abbiamo ricevuto una richiesta di reimpostazione della password per il tuo account.")
            ->action(Lang::get('Reset Password'), $this->resetUrl($notifiable))
            ->line(Lang::get('Questo link per la reimpostazione della password scadrà tra :count minuti.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]))
            ->line(Lang::get('Se non hai richiesto la reimpostazione della password, non sono necessarie ulteriori azioni.'))
            ->salutation("Buon lavoro");
            
        }

    protected function resetUrl(mixed $notifiable): string
    {
        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
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
