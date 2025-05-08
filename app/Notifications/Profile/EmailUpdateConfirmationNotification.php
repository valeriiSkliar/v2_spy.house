<?php

namespace App\Notifications\Profile;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailUpdateConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $code;

    public function __construct(string $code)
    {
        $this->code = $code;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('profile.email_update_confirmation'))
            ->line(__('profile.email_update_confirmation_message'))
            ->line($this->code)
            ->line(__('profile.email_update_confirmation_expires'));
    }
}
