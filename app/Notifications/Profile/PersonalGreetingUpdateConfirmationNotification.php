<?php

namespace App\Notifications\Profile;

use App\Enums\Frontend\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

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
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        Log::info('Sending personal greeting update confirmation', [
            'notification_class' => get_class($this),
            'user_id' => $notifiable->id ?? null,
            'email' => $notifiable->email,
            'subject' => Lang::get('profile.personal_greeting_update.confirmation_title')
        ]);

        return (new MailMessage)
            ->subject(Lang::get('profile.personal_greeting_update.confirmation_title'))
            ->line(Lang::get('profile.personal_greeting_update.confirmation_message'))
            ->line(Lang::get('profile.personal_greeting_update.verification_code_label').': '.$this->code)
            ->line(Lang::get('profile.personal_greeting_update.verification_expires', ['minutes' => 15]));
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => Lang::get('profile.personal_greeting_update.confirmation_title'),
            'message' => Lang::get('profile.personal_greeting_update.confirmation_message'),
            'type' => NotificationType::PROFILE_UPDATED->value,
            'icon' => 'user',
            'data' => [
                'verification_code' => $this->code,
                'expires_in' => 15,
            ],
        ];
    }
}
