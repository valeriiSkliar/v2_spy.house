<?php

namespace App\Notifications\Auth;

use App\Enums\Frontend\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class EmailVerifiedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification for database storage.
     */
    public function toDatabase(object $notifiable): array
    {
        // Сохраняем текущую локаль
        $currentLocale = App::getLocale();

        // Устанавливаем предпочитаемую локаль пользователя или дефолтную
        $userLocale = $notifiable->preferred_locale ?? config('app.locale', 'en');
        App::setLocale($userLocale);

        $result = [
            'title' => __('notifications.email_verified.title'),
            'message' => __('notifications.email_verified.message', [
                'email' => $notifiable->email
            ]),
            'type' => NotificationType::EMAIL_VERIFIED->value,
            'icon' => 'email-verified',
            'data' => array_merge([
                'verification_date' => now()->format('Y-m-d H:i:s'),
                'user_id' => $notifiable->id,
                'email' => $notifiable->email
            ], $this->data),
        ];

        // Восстанавливаем исходную локаль
        App::setLocale($currentLocale);

        return $result;
    }
}
