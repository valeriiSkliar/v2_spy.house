<?php

namespace App\Notifications\Auth;

use App\Enums\Frontend\NotificationType;
use App\Notifications\BaseNotification;

class WelcomeNotification extends BaseNotification
{
    public function __construct()
    {
        parent::__construct(NotificationType::WELCOME);
    }

    protected function getEmailTemplate(): string
    {
        return 'welcome';
    }

    protected function getEmailSubject(object $notifiable): string
    {
        return 'Welcome to Partners.House!';
    }

    protected function getEmailTemplateData(object $notifiable): array
    {
        return array_merge(parent::getEmailTemplateData($notifiable), [
            'username' => $notifiable->login,
            'dashboardUrl' => config('app.url') . '/profile/settings',
        ]);
    }

    protected function getTitle(object $notifiable): string
    {
        return __('notifications.welcome.title');
    }

    protected function getMessage(object $notifiable): string
    {
        return __('notifications.welcome.message', ['name' => $notifiable->name ?? $notifiable->login]);
    }

    protected function getIcon(): string
    {
        return 'user-plus';
    }

    protected function getAdditionalData(object $notifiable): array
    {
        return [
            'registration_date' => $notifiable->created_at->format('Y-m-d H:i:s'),
            'user_id' => $notifiable->id
        ];
    }
}
