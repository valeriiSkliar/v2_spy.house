<?php

namespace App\Http\Controllers\Api\Profile;

use App\Enums\Frontend\NotificationType;
use App\Http\Controllers\Frontend\Profile\BaseProfileController;
use App\Http\Requests\Api\Profile\ChangePasswordApiRequest;
use App\Http\Requests\Profile\ProfileUpdateRequest;
use App\Http\Requests\Profile\UpdateEmailRequest;
use App\Http\Requests\Profile\UpdateIpRestrictionRequest;
use App\Http\Requests\Profile\UpdateNotificationSettingsRequest;
use App\Http\Requests\Profile\UpdatePersonalGreetingSettingsRequest;
use App\Notifications\Profile\EmailUpdateConfirmationNotification;
use App\Notifications\Profile\EmailUpdatedNotification;
use App\Notifications\Profile\PasswordUpdateConfirmationNotification;
use App\Notifications\Profile\PersonalGreetingUpdateConfirmationNotification;
use App\Services\Notification\NotificationDispatcher;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use PragmaRX\Google2FALaravel\Facade as Google2FAFacade;

class ProfileSettingsController extends BaseProfileController
{
    /**
     * Update user's personal settings asynchronously
     */
    public function updateSettingsApi(ProfileUpdateRequest $request): JsonResponse
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
                'message' => __('profile.success.settings_update_success'),
                'user' => [
                    'login' => $user->login,
                    'experience' => $user->experience,
                    'scope_of_activity' => $user->scope_of_activity,
                    'messenger_type' => $user->messenger_type,
                    'messenger_contact' => $user->messenger_contact,
                ],
                'field_statuses' => [
                    'login' => ['status' => 'success'],
                    'experience' => ['status' => 'success'],
                    'scope_of_activity' => ['status' => 'success'],
                    'messenger_type' => ['status' => 'success'],
                    'messenger_contact' => ['status' => 'success'],
                ],
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            Log::error('Unique constraint violation while updating profile settings: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e,
                'request_data' => $request->except(['password', 'current_password']),
            ]);

            // Determine which field caused the unique constraint violation
            $field = 'login'; // default
            $message = __('validation.login_taken');

            // Check if the error is related to messenger contact
            if (str_contains($e->getMessage(), 'messenger_contact')) {
                $field = 'messenger_contact';
                $message = __('validation.messenger_contact_taken');
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => [
                    $field => [$message],
                ],
                'field_statuses' => [
                    'login' => ['status' => $field === 'login' ? 'error' : 'success'],
                    'experience' => ['status' => 'success'],
                    'scope_of_activity' => ['status' => 'success'],
                    'messenger_type' => ['status' => 'success'],
                    'messenger_contact' => ['status' => $field === 'messenger_contact' ? 'error' : 'success'],
                ],
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating profile settings: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e,
                'request_data' => $request->except(['password', 'current_password']),
            ]);

            $errorDetails = [];
            $errorMessage = __('profile.error.settings_update_error');
            $fieldStatuses = [
                'login' => ['status' => 'success'],
                'experience' => ['status' => 'success'],
                'scope_of_activity' => ['status' => 'success'],
                'messenger_type' => ['status' => 'success'],
                'messenger_contact' => ['status' => 'success'],
            ];

            // Если это ошибка валидации, добавляем детали
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                $errorDetails = $e->validator->errors()->messages();
                $errorMessage = __('validation.validation_error');

                // Обновляем статусы полей с ошибками
                foreach ($errorDetails as $field => $messages) {
                    $fieldStatuses[$field] = ['status' => 'error', 'message' => $messages[0] ?? null];
                }
            }

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'error_details' => $errorDetails,
                'error_type' => get_class($e),
                'field_statuses' => $fieldStatuses,
            ], $e instanceof \Illuminate\Validation\ValidationException ? 422 : 500);
        }
    }

    /**
     * Initiate email update process via API
     */
    public function initiateEmailUpdateApi(UpdateEmailRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $newEmail = $request->input('new_email');
            $confirmationMethod = $request->input('confirmation_method');

            if ($confirmationMethod === 'authenticator' && ! $user->google_2fa_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.error.2fa_not_enabled'),
                ], 422);
            }

            if ($confirmationMethod === 'authenticator') {
                Cache::put('email_update_code:' . $user->id, [
                    'new_email' => $newEmail,
                    'method' => 'authenticator',
                    'expires_at' => now()->addMinutes(15),
                    'status' => 'pending',
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
                'status' => 'pending',
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
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.error.error_occurred'),
            ], 500);
        }
    }

    /**
     * Cancel email update process via API
     */
    public function cancelEmailUpdateApi(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $pendingUpdate = Cache::get('email_update_code:' . $user->id);

            if ($pendingUpdate) {
                Cache::forget('email_update_code:' . $user->id);
            }

            $confirmationMethod = $user->google_2fa_enabled ? 'authenticator' : 'email';

            return response()->json([
                'success' => true,
                'message' => __('profile.success.email_update_cancelled'),
                'emailUpdatePending' => false,
                'initialFormHtml' => $this->renderChangeEmailForm($confirmationMethod)->render(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling email update: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.error.error_occurred'),
            ], 500);
        }
    }

    /**
     * Confirm email update via API
     */
    public function confirmEmailUpdateApi(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'verification_code' => 'required|string|digits:6',
            ]);

            $user = $request->user();
            $pendingUpdate = Cache::get('email_update_code:' . $user->id);

            if (! $pendingUpdate || ! isset($pendingUpdate['status']) || $pendingUpdate['status'] !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.error.update_request_expired'),
                ], 422);
            }

            if (now()->isAfter($pendingUpdate['expires_at'])) {
                Cache::forget('email_update_code:' . $user->id);

                return response()->json([
                    'success' => false,
                    'message' => __('profile.error.update_request_expired'),
                ], 422);
            }

            $isValid = false;
            if ($pendingUpdate['method'] === 'authenticator') {
                $secret = Crypt::decryptString($user->google_2fa_secret);
                $isValid = Google2FAFacade::verifyKey($secret, $request->input('verification_code'));
            } else {
                $isValid = $request->input('verification_code') === (string) $pendingUpdate['code'];
            }

            if (! $isValid) {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.error.invalid_verification_code'),
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
                __('profile.success.email_updated'),
                __('profile.success.email_updated_message', [
                    'old_email' => $oldEmail,
                    'new_email' => $pendingUpdate['new_email'],
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
                'message' => __('profile.success.email_updated'),
                'successFormHtml' => $this->renderChangeEmailForm()->render(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error confirming email update: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.error.error_occurred'),
            ], 500);
        }
    }

    /**
     * Initialize personal greeting update process via API
     */
    public function initiatePersonalGreetingUpdateApi(UpdatePersonalGreetingSettingsRequest $request): JsonResponse
    {
        try {
            // Validate confirmation_method
            $request->validate([
                'confirmation_method' => ['required', 'string', \Illuminate\Validation\Rule::in(['email', 'authenticator'])],
            ]);

            $user = $request->user();
            $newPersonalGreeting = $request->input('personal_greeting');
            $confirmationMethod = $request->input('confirmation_method');

            Log::debug('Personal greeting update initiated via API', [
                'user_id' => $user->id,
                'method' => $confirmationMethod,
                'greeting_length' => strlen($newPersonalGreeting),
            ]);

            if ($confirmationMethod === 'authenticator' && ! $user->google_2fa_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.error.2fa_not_enabled'),
                    'errors' => [
                        'confirmation_method' => [__('profile.error.2fa_not_enabled')],
                    ],
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
                    'status' => 'pending',
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
                'status' => 'pending',
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Personal greeting update validation failed', [
                'user_id' => $request->user()->id ?? null,
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error initiating personal greeting update: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.error.error_occurred'),
            ], 500);
        }
    }

    /**
     * Cancel personal greeting update process via API
     */
    public function cancelPersonalGreetingUpdateApi(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $pendingUpdate = Cache::get('personal_greeting_update_code:' . $user->id);

            Log::debug('Personal greeting update cancellation requested', [
                'user_id' => $user->id,
                'has_pending_update' => ! empty($pendingUpdate),
            ]);

            if ($pendingUpdate) {
                Cache::forget('personal_greeting_update_code:' . $user->id);
                Log::info('Personal greeting update cancelled by user (API)', ['user_id' => $user->id]);
            }

            $confirmationMethod = $user->google_2fa_enabled ? 'authenticator' : 'email';

            return response()->json([
                'success' => true,
                'message' => __('profile.success.personal_greeting_update_cancelled'),
                'personalGreetingUpdatePending' => false,
                'initialFormHtml' => $this->renderPersonalGreetingForm($confirmationMethod)->render(),
                'showToast' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling personal greeting update: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.error.error_occurred'),
            ], 500);
        }
    }

    /**
     * Confirm personal greeting update via API
     */
    public function confirmPersonalGreetingUpdateApi(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'verification_code' => 'required|string|digits:6',
            ]);

            $user = $request->user();
            $otp = $request->input('verification_code');
            $pendingUpdate = Cache::get('personal_greeting_update_code:' . $user->id);

            if (! $pendingUpdate) {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.error.update_request_expired'),
                    'errors' => [
                        'verification_code' => [__('profile.error.update_request_expired')],
                    ],
                ], 422);
            }

            if (now()->isAfter($pendingUpdate['expires_at'])) {
                Cache::forget('personal_greeting_update_code:' . $user->id);

                return response()->json([
                    'success' => false,
                    'message' => __('profile.error.update_request_expired'),
                    'errors' => [
                        'verification_code' => [__('profile.error.update_request_expired')],
                    ],
                ], 422);
            }

            $isValid = false;
            if ($pendingUpdate['method'] === 'authenticator') {
                if (! $user->google_2fa_secret) {
                    Log::error('Personal greeting 2FA confirmation failed (API): 2FA secret not found for user.', ['user_id' => $user->id]);

                    return response()->json([
                        'success' => false,
                        'message' => __('profile.error.invalid_verification_code'),
                        'errors' => [
                            'verification_code' => [__('profile.error.invalid_verification_code')],
                        ],
                    ], 422);
                }

                try {
                    $secret = Crypt::decryptString($user->google_2fa_secret);
                    $isValid = Google2FAFacade::verifyKey($secret, $otp);
                } catch (\Exception $e) {
                    Log::error('Failed to decrypt 2FA secret for personal greeting confirmation', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => __('profile.2fa.error_decrypting_secret'),
                        'errors' => [
                            'verification_code' => [__('profile.2fa.error_decrypting_secret')],
                        ],
                    ], 500);
                }
            } else {
                $isValid = $otp === (string) $pendingUpdate['code'];
            }

            if (! $isValid) {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.error.invalid_verification_code'),
                    'errors' => [
                        'verification_code' => [__('profile.error.invalid_verification_code')],
                    ],
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
                __('profile.success.personal_greeting_update_success_title'),
                __('profile.success.personal_greeting_update_success_message')
            );

            return response()->json([
                'success' => true,
                'message' => __('profile.success.personal_greeting_update_success_message'),
                'successFormHtml' => $this->renderPersonalGreetingForm()->render(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Personal greeting confirmation validation failed', [
                'user_id' => $request->user()->id ?? null,
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error confirming personal greeting update: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.error.error_occurred'),
            ], 500);
        }
    }

    /**
     * Update IP restrictions via API
     */
    public function updateIpRestrictionApi(UpdateIpRestrictionRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Get current IP list
            $currentIps = $user->ip_restrictions ?? [];

            // Get new IPs from form
            $newIps = array_filter(array_map('trim', explode("\n", $request->input('ip_restrictions', ''))));

            // Merge and remove duplicates
            $user->ip_restrictions = array_unique(array_merge($currentIps, $newIps));
            $user->save();

            Log::info('IP restrictions updated successfully (API).', ['user_id' => $user->id]);

            return response()->json([
                'success' => true,
                'message' => __('profile.success.ip_restriction_update_success'),
                'ip_restrictions' => $user->ip_restrictions,
                'successFormHtml' => $this->renderIpRestrictionForm()->render(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating IP restrictions: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.error.error_occurred'),
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

        return response()->json(['message' => __('profile.success.password_updated_success_message')]);
    }

    public function initiatePasswordUpdateApi(ChangePasswordApiRequest $request): JsonResponse
    {
        try {
            // Validate confirmation_method
            $request->validate([
                'confirmation_method' => ['required', 'string', \Illuminate\Validation\Rule::in(['email', 'authenticator'])],
            ]);

            $user = $request->user();
            $confirmationMethod = $request->input('confirmation_method');

            Log::debug('Password update initiated via API', [
                'user_id' => $user->id,
                'method' => $confirmationMethod,
                'has_2fa' => $user->google_2fa_enabled,
            ]);

            // Check current password first
            if (! Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.error.current_password_incorrect'),
                    'errors' => [
                        'current_password' => [__('profile.error.current_password_incorrect')]
                    ]
                ], 422);
            }

            if ($confirmationMethod === 'authenticator' && ! $user->google_2fa_enabled) {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.error.2fa_not_enabled'),
                    'errors' => [
                        'confirmation_method' => [__('profile.error.2fa_not_enabled')]
                    ]
                ], 422);
            }

            // Clear any previous expired attempts
            if (Cache::has('password_update_code:' . $user->id)) {
                Cache::forget('password_update_code:' . $user->id);
            }

            if ($confirmationMethod === 'authenticator') {
                Cache::put('password_update_code:' . $user->id, [
                    'password' => $request->input('password'),
                    'method' => 'authenticator',
                    'expires_at' => now()->addMinutes(15),
                    'status' => 'pending',
                ], now()->addMinutes(15));


                return response()->json([
                    'success' => true,
                    'message' => __('profile.security_settings.authenticator_required'),
                    'confirmation_method' => 'authenticator',
                    'confirmation_form_html' => $this->renderChangePasswordForm('authenticator')->render(),
                ]);
            }

            // Email confirmation
            $verificationCode = random_int(100000, 999999);
            Cache::put('password_update_code:' . $user->id, [
                'password' => $request->input('password'),
                'code' => $verificationCode,
                'method' => 'email',
                'expires_at' => now()->addMinutes(15),
                'status' => 'pending',
            ], now()->addMinutes(15));

            // Send notification with verification code
            NotificationDispatcher::sendTo(
                'mail',
                $user->email,
                new PasswordUpdateConfirmationNotification($verificationCode)
            );

            return response()->json([
                'success' => true,
                'message' => __('profile.security_settings.email_code_sent'),
                'confirmation_method' => 'email',
                'confirmation_form_html' => $this->renderChangePasswordForm('email')->render(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Password update validation failed', [
                'user_id' => $request->user()->id ?? null,
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error initiating password update: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.error.error_occurred'),
            ], 500);
        }
    }

    public function cancelPasswordUpdateApi(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $pendingUpdate = Cache::get('password_update_code:' . $user->id);

            if ($pendingUpdate) {
                Cache::forget('password_update_code:' . $user->id);
            }

            $confirmationMethod = $user->google_2fa_enabled ? 'authenticator' : 'email';

            return response()->json([
                'success' => true,
                'message' => __('profile.success.password_update_cancelled'),
                'passwordUpdatePending' => false,
                'initialFormHtml' => $this->renderChangePasswordForm($confirmationMethod)->render(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error cancelling password update: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.error.error_occurred'),
            ], 500);
        }
    }

    public function confirmPasswordUpdateApi(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'verification_code' => 'required|string|digits:6',
            ]);

            $user = $request->user();
            $otp = $request->input('verification_code');
            $pendingUpdate = Cache::get('password_update_code:' . $user->id);

            if (! $pendingUpdate) {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.security_settings.update_request_expired'),
                    'errors' => [
                        'verification_code' => [__('profile.security_settings.update_request_expired')]
                    ]
                ], 422);
            }

            if (now()->isAfter($pendingUpdate['expires_at'])) {
                Cache::forget('password_update_code:' . $user->id);

                return response()->json([
                    'success' => false,
                    'message' => __('profile.security_settings.update_request_expired'),
                    'errors' => [
                        'verification_code' => [__('profile.security_settings.update_request_expired')]
                    ]
                ], 422);
            }

            $isValid = false;
            if ($pendingUpdate['method'] === 'authenticator') {
                if (! $user->google_2fa_secret) {
                    Log::error('Password update 2FA confirmation failed (API): 2FA secret not found for user.', ['user_id' => $user->id]);
                    return response()->json([
                        'success' => false,
                        'message' => __('profile.error.invalid_verification_code'),
                        'errors' => [
                            'verification_code' => [__('profile.error.invalid_verification_code')]
                        ]
                    ], 422);
                }

                try {
                    $secret = Crypt::decryptString($user->google_2fa_secret);
                    $google2fa = app('pragmarx.google2fa');
                    $isValid = $google2fa->verifyKey($secret, $otp);
                } catch (\Exception $e) {
                    Log::error('Failed to decrypt 2FA secret for password confirmation', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => __('profile.2fa.error_decrypting_secret'),
                        'errors' => [
                            'verification_code' => [__('profile.2fa.error_decrypting_secret')]
                        ]
                    ], 500);
                }
            } else {
                // For email confirmation
                $isValid = $otp === (string)$pendingUpdate['code'];
            }

            if (! $isValid) {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.error.invalid_verification_code'),
                    'errors' => [
                        'verification_code' => [__('profile.error.invalid_verification_code')]
                    ]
                ], 422);
            }

            // Update the password
            $user->forceFill([
                'password' => Hash::make($pendingUpdate['password']),
            ])->save();

            // Clear the pending update
            Cache::forget('password_update_code:' . $user->id);

            // Send notification about successful update
            NotificationDispatcher::quickSend(
                $user,
                NotificationType::PASSWORD_CHANGED,
                [],
                __('profile.security_settings.password_updated_success_title'),
                __('profile.security_settings.password_updated_success_message')
            );

            return response()->json([
                'success' => true,
                'message' => __('profile.success.password_updated_success_message'),
                'successFormHtml' => $this->renderChangePasswordForm()->render(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Password confirmation validation failed', [
                'user_id' => $request->user()->id ?? null,
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error confirming password update: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.error.error_occurred'),
            ], 500);
        }
    }

    /**
     * Update user's notification settings via API
     */
    public function updateNotificationsApi(UpdateNotificationSettingsRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $validatedSettings = $request->validated('notification_settings');

            // Get existing notification settings
            $currentSettings = $user->notification_settings ?? [];

            // Special handling for the 'system' key which is used in the UI
            if (isset($validatedSettings['system'])) {
                // Convert string "1"/"0" to boolean
                $settingBool = ($validatedSettings['system'] === '1' || $validatedSettings['system'] === 'true' || $validatedSettings['system'] === true);

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
                'system_notifications' => $currentSettings['system'] ?? false,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('profile.success.notifications_update_success'),
                'system_enabled' => (bool) ($currentSettings['system'] ?? false),
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating notification settings: ' . $e->getMessage(), [
                'user_id' => $request->user()->id ?? null,
                'exception' => $e,
                'request_data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.error.error_occurred'),
            ], 500);
        }
    }

    /**
     * Get default notification channels for a specific notification type
     */
    protected function getDefaultChannelsForType(string $type): array
    {
        $notificationType = app(\App\Models\NotificationType::class)
            ->where('key', $type)
            ->first();

        return $notificationType ? $notificationType->default_channels : ['mail', 'database'];
    }

    /**
     * Validate login uniqueness for jQuery Validation remote rule
     */
    public function validateLoginUnique(Request $request): JsonResponse
    {
        $login = $request->input('login');
        $userId = $request->user()->id;

        if (! $login) {
            return response()->json([
                'valid' => false,
                'message' => __('validation.unique_login_required'),
            ]);
        }

        $exists = \App\Models\User::where('login', $login)
            ->where('id', '!=', $userId)
            ->exists();

        return response()->json([
            'valid' => ! $exists,
            'message' => $exists ? __('validation.login_not_unique') : __('validation.unique_login_required'),
        ]);
    }
}
