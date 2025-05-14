<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Frontend\Profile\BaseProfileController;
use App\Http\Requests\Profile\ProfileSettingsUpdateRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\Api\Profile\ChangePasswordApiRequest;
use App\Notifications\Profile\PasswordUpdateConfirmationNotification;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

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
                    'confirmation_form_html' => view('components.profile.change-password-form', [
                        'confirmationMethod' => 'authenticator',
                        'passwordUpdatePending' => true,
                    ])->render(),
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
                'confirmation_form_html' => view('components.profile.change-password-form', [
                    'confirmationMethod' => 'email',
                    'passwordUpdatePending' => true,
                ])->render(),
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
            'initialFormHtml' => view('components.profile.change-password-form', [
                'confirmationMethod' => $confirmationMethod,
                'passwordUpdatePending' => false,
            ])->render(),
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
