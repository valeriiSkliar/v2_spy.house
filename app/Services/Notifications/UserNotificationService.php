<?php

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserNotificationService
{
    public function sendEmailVerification(User $user): void
    {
        $code = random_int(100000, 999999);

        Mail::to($user->email)->send(new \App\Mail\VerifyEmailMail($code));

        Log::info('Verification email sent', [
            'user_id' => $user->id,
            'code' => $code
        ]);
    }

    public function sendWelcomeEmail(User $user): void
    {
        Mail::to($user->email)->send(new \App\Mail\WelcomeMail($user));

        Log::info('Welcome email sent', [
            'user_id' => $user->id
        ]);
    }

    public function sendWelcomeInAppNotification(User $user): void
    {
        // Implement in-app notification logic here
        Log::info('Welcome in-app notification sent', [
            'user_id' => $user->id
        ]);
    }

    public function sendEmailUpdateConfirmation(User $user, string $newEmail, int $code): void
    {
        Mail::to($user->email)->send(new \App\Mail\EmailUpdateConfirmationMail($code));

        Log::info('Email update confirmation sent', [
            'user_id' => $user->id,
            'new_email' => $newEmail,
            'code' => $code
        ]);
    }

    public function sendPasswordUpdateConfirmation(User $user, int $code): void
    {
        Mail::to($user->email)->send(new \App\Mail\PasswordUpdateConfirmationMail($code));

        Log::info('Password update confirmation sent', [
            'user_id' => $user->id,
            'code' => $code
        ]);
    }
}
