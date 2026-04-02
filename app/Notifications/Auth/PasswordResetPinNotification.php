<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetPinNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $pin)
    {
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
        return (new MailMessage)
            ->subject('Código PIN para redefinição de senha')
            ->greeting('Olá!')
            ->line('Recebemos uma solicitação para redefinir sua senha.')
            ->line('Use o PIN abaixo no aplicativo para concluir a redefinição:')
            ->line('PIN: '.$this->pin)
            ->line('Este PIN expira em '.config('auth.passwords.users.expire', 60).' minutos.')
            ->line('Se você não solicitou, ignore este e-mail.');
    }
}
