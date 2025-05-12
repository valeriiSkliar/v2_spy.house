<?php

namespace App\Notifications\Profile;

use App\Enums\Frontend\NotificationType;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordUpdateConfirmationNotification extends BaseNotification
{
    public function __construct(
        private readonly int $verificationCode
    ) {
        parent::__construct(NotificationType::PASSWORD_RESET);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->getTitle($notifiable))
            ->line($this->getMessage($notifiable))
            ->line(__('profile.security_settings.verification_code') . ': ' . $this->verificationCode)
            ->line(__('profile.security_settings.code_expires_in', ['minutes' => 15]))
            ->line(__('profile.security_settings.if_you_did_not_request'));
    }

    protected function getTitle(object $notifiable): string
    {
        return __('profile.security_settings.password_update_confirmation');
    }

    protected function getMessage(object $notifiable): string
    {
        return __('profile.security_settings.password_update_confirmation_text');
    }

    protected function getIcon(): string
    {
        return 'lock';
    }

    protected function getAdditionalData(object $notifiable): array
    {
        return [
            'verification_code' => $this->verificationCode,
            'expires_in' => 15,
        ];
    }
}
