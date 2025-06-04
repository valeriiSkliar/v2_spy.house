<?php

namespace App\Notifications;

use App\Enums\Frontend\NotificationType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WelcomeInAppNotification extends Notification implements ShouldQueue
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
        return [
            'title' => __('notifications.welcome.title'),
            'message' => __('notifications.welcome.message', [
                'name' => $notifiable->name ?? $notifiable->login,
            ]),
            'type' => NotificationType::WELCOME->value,
            'icon' => 'welcome',
            'data' => array_merge([
                'registration_date' => $notifiable->created_at->format('Y-m-d H:i:s'),
                'user_id' => $notifiable->id,
            ], $this->data),
        ];
    }
}
