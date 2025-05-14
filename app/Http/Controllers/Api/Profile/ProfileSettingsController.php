<?php

namespace App\Http\Controllers\Api\Profile;

use App\Enums\Frontend\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Profile\BaseProfileController;
use App\Http\Requests\Profile\ProfileSettingsUpdateRequest;
use App\Http\Requests\Profile\UpdateEmailRequest;
use App\Notifications\Profile\EmailUpdateConfirmationNotification;
use App\Notifications\Profile\EmailUpdatedNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Api\Profile\ChangePasswordApiRequest;
use App\Notifications\Profile\PasswordUpdateConfirmationNotification;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FALaravel\Facade as Google2FAFacade;

class ProfileSettingsController extends BaseProfileController
{
    /**
     * Update user's personal settings asynchronously
     *
     * @param ProfileSettingsUpdateRequest $request
     * @return JsonResponse
     */
    public function update(ProfileSettingsUpdateRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $validatedData = $request->validated();
            $settingsData = [];

            // Process validated data fields
            $fields = [
                'login',
                'name',
                'surname',
                'date_of_birth',
                'experience',
                'scope_of_activity',
                'messengers',
                'whatsapp_phone',
                'viber_phone',
                'telegram'
            ];

            foreach ($fields as $field) {
                if (isset($validatedData[$field])) {
                    $settingsData[$field] = $validatedData[$field];
                }
            }

            // Update user record
            $user->fill($settingsData);
            $user->save();

            // Return success response with updated user data
            return response()->json([
                'success' => true,
                'message' => __('profile.personal_info.update_success'),
                'user' => [
                    'login' => $user->login,
                    'name' => $user->name,
                    'surname' => $user->surname,
                    'experience' => $user->experience,
                    'scope_of_activity' => $user->scope_of_activity,
                    'telegram' => $user->telegram,
                    'viber_phone' => $user->viber_phone,
                    'whatsapp_phone' => $user->whatsapp_phone,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating profile settings: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.personal_info.update_error'),
            ], 500);
        }
    }

    /**
     * Initiate email update process via API
     *
     * @param UpdateEmailRequest $request
     * @return JsonResponse
     */
    public function initiateEmailUpdateApi(UpdateEmailRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $newEmail = $request->input('new_email');
            $confirmationMethod = $request->input('confirmation_method');

            if ($confirmationMethod === 'authenticator' && !$user->google_2fa_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.security_settings.2fa_not_enabled'),
                ], 422);
            }

            if ($confirmationMethod === 'authenticator') {
                Cache::put('email_update_code:' . $user->id, [
                    'new_email' => $newEmail,
                    'method' => 'authenticator',
                    'expires_at' => now()->addMinutes(15),
                    'status' => 'pending'
                ], now()->addMinutes(15));

                return response()->json([
                    'success' => true,
                    'message' => __('profile.security_settings.authenticator_required'),
                    'confirmation_method' => 'authenticator',
                    'confirmation_form_html' => $this->renderChangeEmailForm('authenticator')->render(),
                ]);
            }

            $verificationCode = random_int(100000, 999999);
            Cache::put('email_update_code:' . $user->id, [
                'new_email' => $newEmail,
                'code' => $verificationCode,
                'method' => 'email',
                'expires_at' => now()->addMinutes(15),
                'status' => 'pending'
            ], now()->addMinutes(15));

            // Отправляем уведомление о коде подтверждения через диспетчер
            NotificationDispatcher::sendNotification(
                $user,
                EmailUpdateConfirmationNotification::class,
                [$verificationCode]
            );

            return response()->json([
                'success' => true,
                'message' => __('profile.security_settings.email_code_sent'),
                'confirmation_method' => 'email',
                'confirmation_form_html' => $this->renderChangeEmailForm('email')->render(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error initiating email update: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.messages.error_occurred'),
            ], 500);
        }
    }

    /**
     * Cancel email update process via API
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cancelEmailUpdateApi(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $pendingUpdate = Cache::get('email_update_code:' . $user->id);

            if ($pendingUpdate) {
                Cache::forget('email_update_code:' . $user->id);
                Log::info('Email update cancelled by user', [
                    'user_id' => $user->id,
                    'new_email' => $pendingUpdate['new_email'] ?? null
                ]);
            }

            $confirmationMethod = $user->google_2fa_enabled ? 'authenticator' : 'email';

            return response()->json([
                'success' => true,
                'message' => __('profile.security_settings.email_update_cancelled'),
                'emailUpdatePending' => false,
                'initialFormHtml' => $this->renderChangeEmailForm($confirmationMethod)->render(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling email update: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.messages.error_occurred'),
            ], 500);
        }
    }

    /**
     * Confirm email update via API
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function confirmEmailUpdateApi(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'verification_code' => 'required|string|digits:6'
            ]);

            $user = $request->user();
            $pendingUpdate = Cache::get('email_update_code:' . $user->id);

            if (!$pendingUpdate || !isset($pendingUpdate['status']) || $pendingUpdate['status'] !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.security_settings.update_request_expired'),
                ], 422);
            }

            if (now()->isAfter($pendingUpdate['expires_at'])) {
                Cache::forget('email_update_code:' . $user->id);
                return response()->json([
                    'success' => false,
                    'message' => __('profile.security_settings.update_request_expired'),
                ], 422);
            }

            $isValid = false;
            if ($pendingUpdate['method'] === 'authenticator') {
                $secret = Crypt::decryptString($user->google_2fa_secret);
                $isValid = Google2FAFacade::verifyKey($secret, $request->input('verification_code'));
            } else {
                $isValid = $request->input('verification_code') === (string)$pendingUpdate['code'];
            }

            if (!$isValid) {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.security_settings.invalid_verification_code'),
                ], 422);
            }

            $oldEmail = $user->email;
            $user->email = $pendingUpdate['new_email'];
            $user->email_verified_at = null;
            $user->save();

            Cache::forget('email_update_code:' . $user->id);

            // Используем метод quickSend для отправки уведомления на старый email
            NotificationDispatcher::quickSend(
                $user,
                NotificationType::EMAIL_VERIFIED,
                [
                    'old_email' => $oldEmail,
                    'new_email' => $pendingUpdate['new_email'],
                ],
                __('profile.email_updated'),
                __('profile.email_updated_message', [
                    'old_email' => $oldEmail,
                    'new_email' => $pendingUpdate['new_email']
                ])
            );

            // Также отправляем уведомление на старый email через sendTo
            NotificationDispatcher::sendTo(
                'mail',
                $oldEmail,
                new EmailUpdatedNotification($oldEmail, $pendingUpdate['new_email'])
            );

            return response()->json([
                'success' => true,
                'message' => __('profile.security_settings.email_updated'),
                'successFormHtml' => $this->renderChangeEmailForm()->render(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error confirming email update: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.messages.error_occurred'),
            ], 500);
        }
    }


    public function updatePasswordApi(ChangePasswordApiRequest $request): JsonResponse
    {
        dd($request->all());
        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        // Опционально: отозвать все другие токены пользователя для повышения безопасности
        // $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return response()->json(['message' => __('profile.messages.password_updated_successfully')]);
    }

    public function initiatePasswordUpdateApi(ChangePasswordApiRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $confirmationMethod = $request->input('confirmation_method');

            if ($confirmationMethod === 'authenticator' && !$user->google_2fa_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.security_settings.2fa_not_enabled'),
                ], 422);
            }
            if ($user->google_2fa_enabled && $confirmationMethod === 'email') {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.security_settings.2fa_enabled_email_not_allowed'),
                ], 422);
            }

            if ($confirmationMethod === 'authenticator') {
                Cache::put('password_update_code:' . $user->id, [
                    'password' => $request->input('password'),
                    'method' => 'authenticator',
                    'expires_at' => now()->addMinutes(15),
                    'google_2fa_enabled' => $user->google_2fa_enabled,
                    'status' => 'pending',
                ], now()->addMinutes(15));

                return response()->json([
                    'success' => true,
                    'message' => __('profile.messages.confirmation_code_sent'),
                    'confirmation_method' => 'authenticator',
                    'confirmation_form_html' => $this->renderChangePasswordForm('authenticator')->render(),
                ]);
            }
            $verificationCode = random_int(100000, 999999);
            Cache::put('password_update_code:' . $user->id, [
                'password' => $request->input('password'),
                'code' => $verificationCode,
                'method' => 'email',
                'expires_at' => now()->addMinutes(15),
                'google_2fa_enabled' => $user->google_2fa_enabled,
                'status' => 'pending',
            ], now()->addMinutes(15));





            // Проверяем текущий пароль
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.validation.current_password_incorrect'),
                ], 422);
            }


            // Используем метод sendNotification для отправки уведомления о смене пароля
            NotificationDispatcher::sendTo(
                'mail',
                $user->email,
                new PasswordUpdateConfirmationNotification($verificationCode)
            );
            return response()->json([
                'success' => true,
                'message' => __('profile.messages.confirmation_code_sent'),
                'confirmation_method' => 'email',
                'confirmation_form_html' => $this->renderChangePasswordForm('email')->render(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error initiating password update: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.messages.error_occurred'),
            ], 500);
        }
    }

    public function cancelPasswordUpdateApi(Request $request): JsonResponse
    {
        $user = $request->user();
        $pendingUpdate = Cache::get('password_update_code:' . $user->id);
        $passwordUpdateStatus = isset($pendingUpdate['status']) ? $pendingUpdate['status'] : null;
        if ($passwordUpdateStatus === 'pending') {
            Cache::forget('password_update_code:' . $user->id);
        }
        $confirmationMethod = $user->google_2fa_enabled ? 'authenticator' : 'email';
        return response()->json([
            'passwordUpdatePending' => false,
            'initialFormHtml' => $this->renderChangePasswordForm($confirmationMethod)->render(),
            'success' => true,
            'message' => __('profile.messages.password_update_cancelled'),
        ]);
    }

    public function confirmPasswordUpdateApi(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $code = $request->input('verification_code');

            // Get pending update data from cache
            $pendingUpdate = Cache::get('password_update_code:' . $user->id);

            if (!$pendingUpdate || $pendingUpdate['status'] !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.security_settings.no_pending_update'),
                ], 422);
            }

            // Validate expiration - handle both Carbon object and string
            $expiresAt = $pendingUpdate['expires_at'];

            // Check if expires_at is already a Carbon instance or needs parsing
            if (is_array($expiresAt) && isset($expiresAt['date'])) {
                // It's a serialized Carbon object
                $expirationTime = Carbon::parse($expiresAt['date']);
            } else if (is_string($expiresAt)) {
                // It's a string date
                $expirationTime = Carbon::parse($expiresAt);
            } else {
                // Use a default expiration (15 minutes ago + 30 minutes)
                $expirationTime = now()->addMinutes(15);
            }

            if (now()->isAfter($expirationTime)) {
                Cache::forget('password_update_code:' . $user->id);
                return response()->json([
                    'success' => false,
                    'message' => __('profile.security_settings.confirmation_expired'),
                ], 422);
            }

            // Handle authentication method
            if ($pendingUpdate['method'] === 'authenticator') {
                // For authenticator, we need to validate the 2FA code
                $google2fa = app('pragmarx.google2fa');
                $valid = $google2fa->verifyKey(
                    $user->google2fa_secret,
                    $code
                );

                if (!$valid) {
                    return response()->json([
                        'success' => false,
                        'message' => __('profile.security_settings.invalid_authenticator_code'),
                    ], 422);
                }
            } else {
                // For email confirmation
                // Convert both values to strings and trim for comparison
                $inputCode = trim((string)$code);
                $storedCode = trim((string)$pendingUpdate['code']);

                if ($inputCode !== $storedCode) {
                    Log::debug('Code mismatch', [
                        'input_code' => $inputCode,
                        'stored_code' => $storedCode,
                        'type_input' => gettype($inputCode),
                        'type_stored' => gettype($storedCode)
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => __('profile.security_settings.invalid_code'),
                    ], 422);
                }
            }

            // Update the password
            $user->forceFill([
                'password' => Hash::make($pendingUpdate['password']),
            ])->save();

            // Clear the pending update
            Cache::forget('password_update_code:' . $user->id);

            // Return success
            return response()->json([
                'success' => true,
                'message' => __('profile.security_settings.password_updated'),
                'successFormHtml' => $this->renderChangePasswordForm()->render(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error confirming password update: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.messages.error_occurred'),
            ], 500);
        }
    }
}
