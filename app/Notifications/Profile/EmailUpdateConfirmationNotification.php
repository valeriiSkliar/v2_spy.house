<?php

namespace App\Notifications\Profile;

use App\Enums\Frontend\NotificationType;
use App\Notifications\BaseNotification;

class EmailUpdateConfirmationNotification extends BaseNotification
{
    private string $code;

    public function __construct(string $code)
    {
        parent::__construct(NotificationType::EMAIL_VERIFIED);
        $this->code = $code;
    }

    protected function getEmailTemplate(): string
    {
        return 'verification-account';
    }

    protected function getEmailSubject(object $notifiable): string
    {
        return __('profile.email_update.confirmation_title');
    }

    protected function getEmailTemplateData(object $notifiable): array
    {
        return array_merge(parent::getEmailTemplateData($notifiable), [
            'code' => $this->code,
        ]);
    }

    protected function getTitle(object $notifiable): string
    {
        return __('profile.email_update.confirmation_title');
    }

    protected function getMessage(object $notifiable): string
    {
        return __('profile.email_update.confirmation_message');
    }

    protected function getIcon(): string
    {
        return 'mail';
    }

    protected function getAdditionalData(object $notifiable): array
    {
        return [
            'code' => $this->code,
            'expires_in' => 15, // минут
        ];
    }
}
