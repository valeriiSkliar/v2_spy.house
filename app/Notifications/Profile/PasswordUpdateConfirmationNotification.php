<?php

namespace App\Notifications\Profile;

use App\Enums\Frontend\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class PasswordUpdateConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private int $verificationCode;

    public function __construct(int $verificationCode)
    {
        $this->verificationCode = $verificationCode;
    }

    /**
     * Get the notification's delivery channels.
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
        Log::info('Sending password update confirmation', [
            'notification_class' => get_class($this),
            'user_id' => $notifiable->id ?? null,
            'email' => $notifiable->email,
            'template' => 'verification-account',
            'subject' => __('emails.password_update_confirmation.subject'),
        ]);

        return (new MailMessage)
            ->subject(__('emails.password_update_confirmation.subject'))
            ->view('emails.verification-account', [
                'code' => $this->verificationCode,
                'verification_code' => $this->verificationCode,
                'expires_in' => 15,
                'user' => $notifiable,
                'emailType' => 'password_update_confirmation',
                'loginUrl' => config('app.url').'/login',
                'telegramUrl' => config('app.telegram_url', 'https://t.me/spyhouse'),
                'supportEmail' => config('mail.support_email', 'support@spy.house'),
                'unsubscribeUrl' => $notifiable->unsubscribe_hash
                    ? route('unsubscribe.show', $notifiable->unsubscribe_hash)
                    : config('app.url').'/unsubscribe',
            ]);
    }

    // /**
    //  * Get the array representation of the notification for database storage.
    //  */
    // public function toDatabase(object $notifiable): array
    // {
    //     return [
    //         'title' => __('emails.password_update_confirmation.title'),
    //         'message' => __('emails.password_update_confirmation.message', ['code' => $this->verificationCode]),
    //         'type' => NotificationType::PASSWORD_RESET->value,
    //         'icon' => 'lock',
    //         'data' => [
    //             'verification_code' => $this->verificationCode,
    //             'expires_in' => 15,
    //         ],
    //     ];
    // }
}
