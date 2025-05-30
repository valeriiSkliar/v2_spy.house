<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

trait SendsRegistrationNotifications
{
    /**
     * Обработка регистрации с отправкой уведомлений
     */
    public function processRegistrationNotifications(array $metadata = []): void
    {
        $cacheKey = 'registration_notifications:' . $this->id;

        if (Cache::has($cacheKey)) {
            Log::info('Registration notifications already sent', ['user_id' => $this->id]);
            return;
        }

        Cache::put($cacheKey, true, now()->addMinutes(15));

        Log::info('Sending registration notifications', [
            'user_id' => $this->id,
            'email' => $this->email,
            'metadata' => $metadata
        ]);

        $this->sendRegistrationEmailVerification();
        $this->sendRegistrationWelcomeNotifications();
    }

    /**
     * Отправка email верификации при регистрации
     */
    protected function sendRegistrationEmailVerification(): void
    {
        if (!$this->hasVerifiedEmail()) {
            Log::info('Sending registration email verification', ['user_id' => $this->id]);
            $this->sendEmailVerificationNotification();
        }
    }

    /**
     * Отправка приветственных уведомлений при регистрации
     */
    protected function sendRegistrationWelcomeNotifications(): void
    {
        Log::info('Sending registration welcome notifications', ['user_id' => $this->id]);

        $this->sendWelcomeNotification();
        $this->sendWelcomeInAppNotification();
    }
}
