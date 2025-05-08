<?php

namespace App\Notifications\Profile;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $oldEmail;
    private $newEmail;

    public function __construct(string $oldEmail, string $newEmail)
    {
        $this->oldEmail = $oldEmail;
        $this->newEmail = $newEmail;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('profile.email_updated'))
            ->line(__('profile.email_updated_message', [
                'old_email' => $this->oldEmail,
                'new_email' => $this->newEmail
            ]));
    }
}
