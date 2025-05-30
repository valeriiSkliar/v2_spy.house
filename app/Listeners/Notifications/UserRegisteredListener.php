<?php

namespace App\Listeners\Notifications;

use App\Events\User\UserRegistered;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Слушатель для обработки события регистрации пользователя
 */
class UserRegisteredListener
{
    /**
     * Обработка события регистрации пользователя
     */
    public function handle(UserRegistered $event): void
    {
        Log::info('Processing UserRegistered event', [
            'user_id' => $event->user->id,
            'email' => $event->user->email
        ]);

        try {
            // Отправляем уведомление о необходимости подтверждения email
            if (!$event->user->hasVerifiedEmail()) {
                Log::info('Sending email verification notification', ['user_id' => $event->user->id]);
                $event->user->sendEmailVerificationNotification();
                Log::info('Email verification notification sent successfully', ['user_id' => $event->user->id]);
            }

            // Отправляем приветственное уведомление по email
            Log::info('Attempting to send welcome email notification', ['user_id' => $event->user->id]);
            $event->user->sendWelcomeNotification();
            Log::info('Welcome email notification sent successfully', ['user_id' => $event->user->id]);

            // Отправляем приветственное уведомление в приложение
            Log::info('Attempting to send welcome in-app notification', ['user_id' => $event->user->id]);
            $event->user->sendWelcomeInAppNotification();
            Log::info('Welcome in-app notification sent successfully', ['user_id' => $event->user->id]);
        } catch (Exception $e) {
            Log::error('Error processing UserRegistered event', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Не прерываем выполнение, позволяем другим уведомлениям пройти
        }
    }
}
