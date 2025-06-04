<?php

namespace App\Listeners\Notifications;

use App\Enums\Frontend\NotificationType;
use App\Events\User\EmailUpdated;
use App\Notifications\Profile\EmailUpdatedNotification;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Слушатель для обработки смены email
 */
class EmailUpdatedListener
{
    /**
     * Обработка смены email
     */
    public function handle(EmailUpdated $event): void
    {
        Log::info('Processing EmailUpdated event', [
            'user_id' => $event->user->id,
            'old_email' => $event->oldEmail,
            'new_email' => $event->newEmail,
        ]);

        // Уведомление пользователю
        NotificationDispatcher::quickSend(
            $event->user,
            NotificationType::EMAIL_VERIFIED,
            [
                'old_email' => $event->oldEmail,
                'new_email' => $event->newEmail,
            ],
            __('profile.success.email_updated'),
            __('profile.success.email_updated_message', [
                'old_email' => $event->oldEmail,
                'new_email' => $event->newEmail,
            ])
        );

        // Уведомление на старый email
        NotificationDispatcher::sendTo(
            'mail',
            $event->oldEmail,
            new EmailUpdatedNotification($event->oldEmail, $event->newEmail)
        );
    }
}
