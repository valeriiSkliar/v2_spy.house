<?php

namespace App\Actions\User;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class ProcessUserRegistrationAction
{
    /**
     * Выполнить обработку регистрации пользователя
     */
    public function execute(User $user, array $metadata = []): void
    {
        Log::info('Processing user registration via Action', [
            'user_id' => $user->id,
            'email' => $user->email,
            'metadata' => $metadata
        ]);

        // Последовательно выполняем действия
        $this->handleEmailVerification($user);
        $this->handleWelcomeNotifications($user);
    }

    private function handleEmailVerification(User $user): void
    {
        if (!$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            Log::info('Verification email sent', ['user_id' => $user->id]);
        }
    }

    private function handleWelcomeNotifications(User $user): void
    {
        $user->sendWelcomeNotification();
        $user->sendWelcomeInAppNotification();
        Log::info('Welcome notifications sent', ['user_id' => $user->id]);
    }
}
