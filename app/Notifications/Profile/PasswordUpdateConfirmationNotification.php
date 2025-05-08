<?php

namespace App\Notifications\Profile;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordUpdateConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $verificationCode
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('profile.security_settings.password_update_confirmation'))
            ->line(__('profile.security_settings.password_update_confirmation_text'))
            ->line(__('profile.security_settings.verification_code') . ': ' . $this->verificationCode)
            ->line(__('profile.security_settings.code_expires_in', ['minutes' => 15]))
            ->line(__('profile.security_settings.if_you_did_not_request'));
    }
}
