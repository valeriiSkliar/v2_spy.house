<?php

namespace App\Notifications\Profile;

use App\Enums\Frontend\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class EmailUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private string $oldEmail;

    private string $newEmail;

    public function __construct(string $oldEmail, string $newEmail)
    {
        $this->oldEmail = $oldEmail;
        $this->newEmail = $newEmail;
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
        Log::info('Sending email updated notification', [
            'notification_class' => get_class($this),
            'user_id' => $notifiable->id ?? null,
            'old_email' => $this->oldEmail,
            'new_email' => $this->newEmail,
            'subject' => __('emails.email_updated.subject'),
        ]);

        return (new MailMessage)
            ->subject(__('emails.email_updated.subject'))
            ->line(__('emails.email_updated.message', [
                'old_email' => $this->oldEmail,
                'new_email' => $this->newEmail,
            ]));
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('emails.email_updated.subject'),
            'message' => __('emails.email_updated.message', [
                'old_email' => $this->oldEmail,
                'new_email' => $this->newEmail,
            ]),
            'type' => NotificationType::EMAIL_UPDATED->value,
            'icon' => 'mail',
            'data' => [
                'old_email' => $this->oldEmail,
                'new_email' => $this->newEmail,
            ],
        ];
    }
}
