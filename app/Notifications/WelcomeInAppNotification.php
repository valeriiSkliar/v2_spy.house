<?php

namespace App\Notifications;

use App\Models\User;
use App\Enums\Frontend\NotificationType;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;

class WelcomeInAppNotification extends Notification
{
    public function __construct(
        private User $user
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'type' => NotificationType::WELCOME->value,
            'title' => __('notifications.welcome.title'),
            'message' => __('notifications.welcome.message', ['name' => $this->user->name]),
            'data' => [
                'user_id' => $this->user->id,
                'timestamp' => now()->toISOString(),
            ]
        ]);
    }
}
