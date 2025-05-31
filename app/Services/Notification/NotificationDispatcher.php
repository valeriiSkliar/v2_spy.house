<?php

namespace App\Services\Notification;

use App\Enums\Frontend\NotificationType;
use App\Models\User;
use App\Notifications\CustomNotification;

/**
 * Service for sending in-app notifications (database channel only)
 * For email notifications, use standard Laravel notification classes directly
 */
class NotificationDispatcher
{
    /**
     * Send a quick in-app notification without creating a separate class
     *
     * @param  User  $user  User to send notification to
     * @param  NotificationType  $type  Notification type
     * @param  array  $data  Additional data for notification
     * @param  string|null  $title  Custom title (optional)
     * @param  string|null  $message  Custom message (optional)
     */
    public static function quickSend(
        User $user,
        NotificationType $type,
        array $data = [],
        ?string $title = null,
        ?string $message = null
    ): void {
        $notification = new CustomNotification($type, $data, $title, $message);
        $user->notify($notification);
    }
}
