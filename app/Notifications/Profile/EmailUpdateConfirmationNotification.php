<?php

namespace App\Notifications\Profile;

use App\Enums\Frontend\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class EmailUpdateConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private string $code;

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
        Log::info('Sending email update confirmation', [
            'notification_class' => get_class($this),
            'user_id' => $notifiable->id ?? null,
            'email' => $notifiable->email,
            'template' => 'verification-account',
            'subject' => __('profile.email_update.confirmation_title')
        ]);

        return (new MailMessage)
            ->subject(__('profile.email_update.confirmation_title'))
            ->view('emails.verification-account', [
                'code' => $this->code,
                'user' => $notifiable,
                'loginUrl' => config('app.url') . '/login',
                'telegramUrl' => config('app.telegram_url', 'https://t.me/spyhouse'),
                'supportEmail' => config('mail.support_email', 'support@spy.house'),
                'unsubscribeUrl' => config('app.url') . '/unsubscribe'
            ]);
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => __('profile.email_update.confirmation_title'),
            'message' => __('profile.email_update.confirmation_message'),
            'type' => NotificationType::EMAIL_VERIFIED->value,
            'icon' => 'mail',
            'data' => [
                'code' => $this->code,
                'expires_in' => 15, // минут
            ],
        ];
    }
}
