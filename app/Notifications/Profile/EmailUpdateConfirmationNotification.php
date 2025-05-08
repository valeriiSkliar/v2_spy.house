<?php

namespace App\Notifications\Profile;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

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
        Log::info('toMail', ['code' => $this->code]);

        $mailMessage = (new MailMessage)
            ->subject(__('profile.email_update.confirmation_title'))
            ->line(__('profile.email_update.confirmation_message'))
            ->line(__('profile.email_update.verification_code_label') . ': ' . $this->code)
            ->line(__('profile.email_update.verification_expires'));

        Log::info('Mail content', [
            'subject' => __('profile.email_update.confirmation_title'),
            'message' => __('profile.email_update.confirmation_message'),
            'code' => $this->code,
            'expires' => __('profile.email_update.verification_expires')
        ]);

        return $mailMessage;
    }
}
