<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    public function __construct(
        private readonly EmailService $emailService,
        private readonly SecurityService $securityService
    ) {}

    /**
     * Update user's personal information
     */
    public function updatePersonalInfo(User $user, array $data): bool
    {
        try {
            // Handle messenger fields - remove if empty
            if (isset($data['messenger_type']) && isset($data['messenger_contact'])) {
                if (empty($data['messenger_type']) || empty($data['messenger_contact'])) {
                    unset($data['messenger_type'], $data['messenger_contact']);
                }
            }

            $user->fill($data);

            return $user->save();
        } catch (\Exception $e) {
            Log::error('Error updating personal info', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return false;
        }
    }

    /**
     * Initiate password change process
     */
    public function initiatePasswordChange(User $user, array $data): array
    {
        // Validate current password
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [__('auth.password')],
            ]);
        }

        // Generate verification code
        $code = $this->securityService->generateVerificationCode();
        $confirmationMethod = $this->securityService->getConfirmationMethod($user);

        // Store new password and code in cache
        $cacheData = [
            'new_password' => Hash::make($data['new_password']),
            'code' => $code,
            'method' => $confirmationMethod,
        ];

        Cache::put(
            'password_update_code:'.$user->id,
            Crypt::encrypt($cacheData),
            now()->addMinutes(10)
        );

        // Send verification code
        $this->securityService->sendVerificationCode($user, $confirmationMethod, $code);

        return [
            'confirmation_method' => $confirmationMethod,
            'success' => true,
        ];
    }

    /**
     * Confirm password change
     */
    public function confirmPasswordChange(User $user, string $code): bool
    {
        $cacheKey = 'password_update_code:'.$user->id;
        $encryptedData = Cache::get($cacheKey);

        if (! $encryptedData) {
            return false;
        }

        try {
            $data = Crypt::decrypt($encryptedData);

            if (! $this->securityService->verifyCode($user, $code, $data['method'])) {
                return false;
            }

            // Update password
            $user->password = $data['new_password'];
            $user->last_password_reset_at = now();
            $user->save();

            // Clear cache
            Cache::forget($cacheKey);

            Log::info('Password changed successfully', ['user_id' => $user->id]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error confirming password change', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Initiate email change process
     */
    public function initiateEmailChange(User $user, string $newEmail): array
    {
        // Check if email is already taken
        if (User::where('email', $newEmail)->where('id', '!=', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => [__('validation.unique', ['attribute' => 'email'])],
            ]);
        }

        $code = $this->securityService->generateVerificationCode();
        $confirmationMethod = $this->securityService->getConfirmationMethod($user);

        // Store new email and code in cache
        $cacheData = [
            'new_email' => $newEmail,
            'old_email' => $user->email,
            'code' => $code,
            'method' => $confirmationMethod,
        ];

        Cache::put(
            'email_update_code:'.$user->id,
            Crypt::encrypt($cacheData),
            now()->addMinutes(10)
        );

        // Send verification code
        $this->securityService->sendVerificationCode($user, $confirmationMethod, $code);

        return [
            'confirmation_method' => $confirmationMethod,
            'new_email' => $newEmail,
            'success' => true,
        ];
    }

    /**
     * Confirm email change
     */
    public function confirmEmailChange(User $user, string $code): bool
    {
        $cacheKey = 'email_update_code:'.$user->id;
        $encryptedData = Cache::get($cacheKey);

        if (! $encryptedData) {
            return false;
        }

        try {
            $data = Crypt::decrypt($encryptedData);

            if (! $this->securityService->verifyCode($user, $code, $data['method'])) {
                return false;
            }

            $oldEmail = $user->email;
            $newEmail = $data['new_email'];

            // Update email
            $user->email = $newEmail;
            $user->email_verified_at = now();
            $user->save();

            // Clear cache
            Cache::forget($cacheKey);

            // Send notification emails
            $this->emailService->sendEmailUpdatedNotification($user, $oldEmail, $newEmail);

            Log::info('Email changed successfully', [
                'user_id' => $user->id,
                'old_email' => $oldEmail,
                'new_email' => $newEmail,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error confirming email change', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationSettings(User $user, array $settings): bool
    {
        try {
            $user->fill($settings);

            return $user->save();
        } catch (\Exception $e) {
            Log::error('Error updating notification settings', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'settings' => $settings,
            ]);

            return false;
        }
    }

    /**
     * Update personal greeting
     */
    public function updatePersonalGreeting(User $user, string $greeting): bool
    {
        try {
            $user->personal_greeting = $greeting;

            return $user->save();
        } catch (\Exception $e) {
            Log::error('Error updating personal greeting', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Cancel pending operation
     */
    public function cancelPendingOperation(User $user, string $operationType): bool
    {
        $cacheKey = $operationType.'_update_code:'.$user->id;

        return Cache::forget($cacheKey);
    }

    /**
     * Check if operation is pending
     */
    public function isOperationPending(User $user, string $operationType): bool
    {
        $cacheKey = $operationType.'_update_code:'.$user->id;

        return Cache::has($cacheKey);
    }

    /**
     * Get pending operation data
     */
    public function getPendingOperationData(User $user, string $operationType): ?array
    {
        $cacheKey = $operationType.'_update_code:'.$user->id;
        $encryptedData = Cache::get($cacheKey);

        if (! $encryptedData) {
            return null;
        }

        try {
            return Crypt::decrypt($encryptedData);
        } catch (\Exception $e) {
            Log::error('Error decrypting pending operation data', [
                'user_id' => $user->id,
                'operation_type' => $operationType,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
