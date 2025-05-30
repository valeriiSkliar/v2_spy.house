<?php

namespace App\Listeners\Notifications;

use App\Enums\Frontend\NotificationType;
use App\Events\User\EmailVerified;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Слушатель для обработки подтверждения email
 */
class EmailVerifiedListener
{
    /**
     * Обработка подтверждения email
     */
    public function handle(EmailVerified $event): void
    {
        Log::info('Processing EmailVerified event', [
            'user_id' => $event->user->id
        ]);

        // Отправляем уведомление об успешном подтверждении
        NotificationDispatcher::quickSend(
            $event->user,
            NotificationType::EMAIL_VERIFIED,
            [],
            __('profile.success.email_verified'),
            __('profile.success.email_verified_message')
        );
    }
}
