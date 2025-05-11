<?php

namespace App\Notifications\Profile;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class PersonalGreetingUpdateConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private string $code;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $code)
    {
        $this->code = $code;
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
            ->subject(Lang::get('profile.personal_greeting_update.confirmation_title'))
            ->line(Lang::get('profile.personal_greeting_update.confirmation_message'))
            ->line(Lang::get('profile.personal_greeting_update.verification_code_label') . ': ' . $this->code)
            ->line(Lang::get('profile.personal_greeting_update.verification_expires', ['minutes' => 15]));
        // Optionally, add a line about "if you didn't request this"
    }
}
