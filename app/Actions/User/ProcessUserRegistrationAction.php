<?php

namespace App\Actions\User;

use App\Models\User;
use App\Services\Notifications\UserNotificationService;
use Illuminate\Support\Facades\Log;

class ProcessUserRegistrationAction
{
    public function __construct(
        private readonly UserNotificationService $notificationService
    ) {}

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
            $this->notificationService->sendEmailVerification($user);
            Log::info('Verification email sent', ['user_id' => $user->id]);
        }
    }

    private function handleWelcomeNotifications(User $user): void
    {
        $this->notificationService->sendWelcomeEmail($user);
        $this->notificationService->sendWelcomeInAppNotification($user);
        Log::info('Welcome notifications sent', ['user_id' => $user->id]);
    }
}
