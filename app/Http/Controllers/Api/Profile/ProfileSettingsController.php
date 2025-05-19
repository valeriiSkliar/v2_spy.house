<?php

namespace App\Http\Controllers\Api\Profile;

use App\Enums\Frontend\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Profile\BaseProfileController;
use App\Http\Requests\Profile\ProfileSettingsUpdateRequest;
use App\Http\Requests\Profile\UpdateEmailRequest;
use App\Http\Requests\Profile\UpdateNotificationSettingsRequest;
use App\Http\Requests\Profile\UpdatePersonalGreetingSettingsRequest;
use App\Notifications\Profile\EmailUpdateConfirmationNotification;
use App\Notifications\Profile\EmailUpdatedNotification;
use App\Notifications\Profile\PersonalGreetingUpdateConfirmationNotification;
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
    public function updateSettingsApi(ProfileSettingsUpdateRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $validatedData = $request->validated();

            // Проверяем наличие обязательных полей мессенджера
            if (isset($validatedData['messenger_type']) && isset($validatedData['messenger_contact'])) {
                // Проверяем, что значения не пустые
                if (empty($validatedData['messenger_type']) || empty($validatedData['messenger_contact'])) {
                    // Если одно из полей пустое, удаляем оба поля из валидированных данных
                    unset($validatedData['messenger_type']);
                    unset($validatedData['messenger_contact']);
                }
            }

            // Update user record with the filtered validated data
            $user->fill($validatedData);
            $user->save();

            // Return success response with updated user data
            return response()->json([
                'success' => true,
                'message' => 'Настройки профиля успешно обновлены',
                'user' => [
                    'login' => $user->login,
                    'experience' => $user->experience,
                    'scope_of_activity' => $user->scope_of_activity,
                    'messenger_type' => $user->messenger_type,
                    'messenger_contact' => $user->messenger_contact,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating profile settings: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при обновлении настроек профиля',
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

    /**
     * Initialize personal greeting update process via API
     *
     * @param UpdatePersonalGreetingSettingsRequest $request
     * @return JsonResponse
     */
    public function initiatePersonalGreetingUpdateApi(UpdatePersonalGreetingSettingsRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $newPersonalGreeting = $request->input('personal_greeting');
            $confirmationMethod = $request->input('confirmation_method');

            if ($confirmationMethod === 'authenticator' && !$user->google_2fa_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.security_settings.2fa_not_enabled'),
                ], 422);
            }

            // Clear any previous expired attempts
            if (Cache::has('personal_greeting_update_code:' . $user->id)) {
                Cache::forget('personal_greeting_update_code:' . $user->id);
            }

            if ($confirmationMethod === 'authenticator') {
                Cache::put('personal_greeting_update_code:' . $user->id, [
                    'personal_greeting' => $newPersonalGreeting,
                    'method' => 'authenticator',
                    'expires_at' => now()->addMinutes(15),
                    'status' => 'pending'
                ], now()->addMinutes(15));

                Log::info('Personal greeting update initiated via authenticator (API).', ['user_id' => $user->id]);

                return response()->json([
                    'success' => true,
                    'message' => __('profile.security_settings.authenticator_required'),
                    'confirmation_method' => 'authenticator',
                    'confirmation_form_html' => $this->renderPersonalGreetingForm('authenticator', 'confirmation')->render(),
                ]);
            }

            // Email confirmation
            $verificationCode = random_int(100000, 999999);
            Cache::put('personal_greeting_update_code:' . $user->id, [
                'personal_greeting' => $newPersonalGreeting,
                'code' => $verificationCode,
                'method' => 'email',
                'expires_at' => now()->addMinutes(15),
                'status' => 'pending'
            ], now()->addMinutes(15));

            // Send notification with verification code
            NotificationDispatcher::sendNotification(
                $user,
                PersonalGreetingUpdateConfirmationNotification::class,
                [$verificationCode]
            );

            Log::info('Personal greeting update initiated via email (API).', ['user_id' => $user->id, 'email' => $user->email]);

            return response()->json([
                'success' => true,
                'message' => __('profile.security_settings.email_code_sent'),
                'confirmation_method' => 'email',
                'confirmation_form_html' => $this->renderPersonalGreetingForm('email', 'confirmation')->render(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error initiating personal greeting update: ' . $e->getMessage(), [
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
     * Cancel personal greeting update process via API
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cancelPersonalGreetingUpdateApi(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $pendingUpdate = Cache::get('personal_greeting_update_code:' . $user->id);

            if ($pendingUpdate) {
                Cache::forget('personal_greeting_update_code:' . $user->id);
                Log::info('Personal greeting update cancelled by user (API)', ['user_id' => $user->id]);
            }

            $confirmationMethod = $user->google_2fa_enabled ? 'authenticator' : 'email';

            return response()->json([
                'success' => true,
                'message' => __('profile.security_settings.personal_greeting_update_cancelled'),
                'personalGreetingUpdatePending' => false,
                'initialFormHtml' => $this->renderPersonalGreetingForm($confirmationMethod)->render(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling personal greeting update: ' . $e->getMessage(), [
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
     * Confirm personal greeting update via API
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function confirmPersonalGreetingUpdateApi(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'verification_code' => 'required|string|digits:6'
            ]);

            $user = $request->user();
            $pendingUpdate = Cache::get('personal_greeting_update_code:' . $user->id);

            if (!$pendingUpdate || !isset($pendingUpdate['status']) || $pendingUpdate['status'] !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.security_settings.update_request_expired'),
                ], 422);
            }

            if (now()->isAfter($pendingUpdate['expires_at'])) {
                Cache::forget('personal_greeting_update_code:' . $user->id);
                return response()->json([
                    'success' => false,
                    'message' => __('profile.security_settings.update_request_expired'),
                ], 422);
            }

            $isValid = false;
            if ($pendingUpdate['method'] === 'authenticator') {
                if (!$user->google_2fa_secret) {
                    Log::error('Personal greeting 2FA confirmation failed (API): 2FA secret not found for user.', ['user_id' => $user->id]);
                    return response()->json([
                        'success' => false,
                        'message' => __('profile.2fa.error_verifying_code')
                    ], 422);
                }
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

            $user->personal_greeting = $pendingUpdate['personal_greeting'];
            $user->save();

            Cache::forget('personal_greeting_update_code:' . $user->id);

            Log::info('Personal greeting updated successfully (API).', ['user_id' => $user->id]);

            // Send notification about successful update
            NotificationDispatcher::quickSend(
                $user,
                NotificationType::PROFILE_UPDATED,
                ['greeting_updated' => true],
                __('profile.personal_greeting_update.success_title'),
                __('profile.personal_greeting_update.success_message')
            );

            return response()->json([
                'success' => true,
                'message' => __('profile.personal_greeting_update.success_message'),
                'successFormHtml' => $this->renderPersonalGreetingForm()->render(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error confirming personal greeting update: ' . $e->getMessage(), [
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
     * Update IP restrictions via API
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateIpRestrictionApi(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ip_restrictions' => ['nullable', 'string'],
                'password' => ['required', 'current_password'],
            ]);

            $user = $request->user();

            // Get current IP list
            $currentIps = $user->ip_restrictions ?? [];

            // Get new IPs from form
            $newIps = array_filter(array_map('trim', explode("\n", $request->input('ip_restrictions', ''))));

            // Validate each IP
            foreach ($newIps as $ip) {
                if (!filter_var($ip, FILTER_VALIDATE_IP) && !$this->isValidIpRange($ip)) {
                    return response()->json([
                        'success' => false,
                        'message' => __('validation.ip', ['attribute' => 'IP address']),
                        'errors' => ['ip_restrictions' => [__('validation.ip', ['attribute' => 'IP address'])]]
                    ], 422);
                }
            }

            // Merge and remove duplicates
            $user->ip_restrictions = array_unique(array_merge($currentIps, $newIps));
            $user->save();

            Log::info('IP restrictions updated successfully (API).', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => __('profile.ip_restriction.update_success'),
                'ip_restrictions' => $user->ip_restrictions,
                'successFormHtml' => $this->renderIpRestrictionForm()->render(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating IP restrictions: ' . $e->getMessage(), [
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
     * Check if the IP range is valid
     *
     * @param string $ip
     * @return bool
     */
    private function isValidIpRange(string $ip): bool
    {
        // Check CIDR format (e.g., 192.168.1.0/24)
        if (preg_match('/^(\d{1,3}\.){3}\d{1,3}\/\d{1,2}$/', $ip)) {
            list($ip, $mask) = explode('/', $ip);
            return filter_var($ip, FILTER_VALIDATE_IP) && $mask >= 0 && $mask <= 32;
        }

        // Check range format (e.g., 192.168.1.1-192.168.1.255)
        if (preg_match('/^(\d{1,3}\.){3}\d{1,3}-(\d{1,3}\.){3}\d{1,3}$/', $ip)) {
            list($start, $end) = explode('-', $ip);
            return filter_var($start, FILTER_VALIDATE_IP) && filter_var($end, FILTER_VALIDATE_IP);
        }

        return false;
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
            // Используем метод sendTo
            NotificationDispatcher::sendTo(
                'mail',
                $user->email,
                new PasswordUpdateConfirmationNotification($verificationCode)
            );
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
            $request->validate([
                'verification_code' => 'required|string|digits:6'
            ]);
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
                if (!$user->google_2fa_secret) {
                    Log::error('2FA confirmation failed: 2FA secret not found for user.', ['user_id' => $user->id]);
                    return response()->json([
                        'success' => false,
                        'message' => __('profile.2fa.error_verifying_code')
                    ], 422);
                }

                try {
                    $secret = Crypt::decryptString($user->google_2fa_secret);
                    $google2fa = app('pragmarx.google2fa');
                    $valid = $google2fa->verifyKey($secret, $code);

                    if (!$valid) {
                        return response()->json([
                            'success' => false,
                            'message' => __('profile.security_settings.invalid_authenticator_code'),
                        ], 422);
                    }
                } catch (\Exception $e) {
                    Log::error('Error verifying 2FA code: ' . $e->getMessage(), [
                        'user_id' => $user->id,
                        'exception' => $e
                    ]);
                    return response()->json([
                        'success' => false,
                        'message' => __('profile.2fa.error_verifying_code')
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

            NotificationDispatcher::quickSend(
                $user,
                NotificationType::PASSWORD_CHANGED,
                [],
                __('profile.security_settings.password_updated_success_title'),
                __('profile.security_settings.password_updated_success_message')
            );

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

    /**
     * Update user's notification settings via API
     *
     * @param \App\Http\Requests\Profile\UpdateNotificationSettingsRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateNotificationsApi(UpdateNotificationSettingsRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $validatedSettings = $request->validated('notification_settings');

            // Log the received settings for debugging
            Log::debug('Notification settings request received', [
                'validated_settings' => $validatedSettings
            ]);

            // Get existing notification settings
            $currentSettings = $user->notification_settings ?? [];

            // Special handling for the 'system' key which is used in the UI
            if (isset($validatedSettings['system'])) {
                // Convert string "1"/"0" to boolean
                $settingBool = ($validatedSettings['system'] === "1" || $validatedSettings['system'] === "true" || $validatedSettings['system'] === true);

                // Update the settings directly without lookup
                $currentSettings['system'] = $settingBool;
            }

            // This simplified version only handles the 'system' key directly

            // Update user's notification settings
            $user->notification_settings = $currentSettings;
            $user->save();

            // Log the update
            Log::info('Notification settings updated via API', [
                'user_id' => $user->id,
                'system_notifications' => $currentSettings['system'] ?? false
            ]);

            return response()->json([
                'success' => true,
                'message' => __('profile.notifications.update_success'),
                'system_enabled' => (bool)($currentSettings['system'] ?? false),
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating notification settings: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e,
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.messages.error_occurred'),
            ], 500);
        }
    }

    /**
     * Get default notification channels for a specific notification type
     *
     * @param string $type
     * @return array
     */
    protected function getDefaultChannelsForType(string $type): array
    {
        $notificationType = app(\App\Models\NotificationType::class)
            ->where('key', $type)
            ->first();

        return $notificationType ? $notificationType->default_channels : ['mail', 'database'];
    }
}
