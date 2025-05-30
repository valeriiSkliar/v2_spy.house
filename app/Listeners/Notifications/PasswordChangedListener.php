<?php

namespace App\Listeners\Notifications;

use App\Enums\Frontend\NotificationType;
use App\Events\User\PasswordChanged;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Слушатель для обработки смены пароля
 */
class PasswordChangedListener
{
    /**
     * Обработка смены пароля
     */
    public function handle(PasswordChanged $event): void
    {
        Log::info('Processing PasswordChanged event', [
            'user_id' => $event->user->id
        ]);

        // Уведомление об успешной смене пароля
        NotificationDispatcher::quickSend(
            $event->user,
            NotificationType::PASSWORD_CHANGED,
            [],
            __('profile.security_settings.password_updated_success_title'),
            __('profile.security_settings.password_updated_success_message')
        );
    }
}
