<?php

namespace App\Notifications\Profile;

use App\Enums\Frontend\NotificationType;
use App\Notifications\BaseNotification;

class PasswordUpdateConfirmationNotification extends BaseNotification
{
    private int $verificationCode;

    public function __construct(int $verificationCode)
    {
        parent::__construct(NotificationType::PASSWORD_RESET);
        $this->verificationCode = $verificationCode;
    }

    protected function getEmailTemplate(): string
    {
        return 'password-update-confirmation';
    }

    protected function getEmailSubject(object $notifiable): string
    {
        return __('profile.security_settings.password_update_confirmation');
    }

    protected function getEmailTemplateData(object $notifiable): array
    {
        return array_merge(parent::getEmailTemplateData($notifiable), [
            'verification_code' => $this->verificationCode,
            'expires_in' => 15,
        ]);
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
