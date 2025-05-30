<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class UserRegistrationService
{
    /**
     * Обработка регистрации пользователя с отправкой уведомлений
     */
    public function processRegistration(User $user, array $metadata = []): void
    {
        $cacheKey = 'user_registration_processed:' . $user->id;

        // Защита от дублирования
        if (Cache::has($cacheKey)) {
            Log::info('User registration already processed, skipping', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            return;
        }

        // Блокируем повторную обработку на 15 минут
        Cache::put($cacheKey, true, now()->addMinutes(15));

        Log::info('Processing user registration', [
            'user_id' => $user->id,
            'email' => $user->email,
            'metadata' => $metadata
        ]);

        try {
            $this->sendVerificationNotification($user);
            $this->sendWelcomeNotifications($user);

            Log::info('User registration processed successfully', [
                'user_id' => $user->id
            ]);
        } catch (Exception $e) {
            // Убираем блокировку при ошибке
            Cache::forget($cacheKey);

            Log::error('Error processing user registration', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Отправка уведомления о верификации email
     */
    private function sendVerificationNotification(User $user): void
    {
        if (!$user->hasVerifiedEmail()) {
            Log::info('Sending email verification notification', ['user_id' => $user->id]);
            $user->sendEmailVerificationNotification();
            Log::info('Email verification notification sent', ['user_id' => $user->id]);
        }
    }

    /**
     * Отправка приветственных уведомлений
     */
    private function sendWelcomeNotifications(User $user): void
    {
        // Email уведомление
        Log::info('Sending welcome email notification', ['user_id' => $user->id]);
        $user->sendWelcomeNotification();
        Log::info('Welcome email notification sent', ['user_id' => $user->id]);

        // In-app уведомление
        Log::info('Sending welcome in-app notification', ['user_id' => $user->id]);
        $user->sendWelcomeInAppNotification();
        Log::info('Welcome in-app notification sent', ['user_id' => $user->id]);
    }
}
