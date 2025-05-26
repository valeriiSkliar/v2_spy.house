<?php

namespace App\Http\Controllers\Frontend\Profile;

use App\Enums\Frontend\NotificationType;
use App\Http\Requests\Profile\ProfileUpdateRequest;
use App\Http\Requests\Profile\UpdateEmailRequest;
use App\Http\Requests\Profile\UpdateIpRestrictionRequest;
use App\Http\Requests\Profile\UpdateNotificationSettingsRequest;
use App\Http\Requests\Profile\UpdatePersonalGreetingSettingsRequest;
use App\Notifications\Profile\EmailUpdateConfirmationNotification;
use App\Notifications\Profile\EmailUpdatedNotification;
use App\Notifications\Profile\PasswordUpdateConfirmationNotification;
use App\Notifications\Profile\PersonalGreetingUpdateConfirmationNotification;
use App\Services\Frontend\Toast;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use PragmaRX\Google2FALaravel\Facade as Google2FAFacade;

class ProfileController extends BaseProfileController
{
    protected $settingsView = 'pages.profile.settings';

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view($this->settingsView, [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function settings(Request $request): View
    {
        $activeTab = $request->query('tab', 'personal');

        return $this->renderSettingsView($request, $activeTab);
    }

    public function changePassword(Request $request): View
    {
        $user = $request->user();
        $pendingUpdate = Cache::get('password_update_code:' . $user->id);
        $confirmationMethod = $user->google_2fa_enabled ? 'authenticator' : 'email';

        return view('pages.profile.change-password', [
            'user' => $user,
            'passwordUpdatePending' => $pendingUpdate ? true : false,
            'confirmationMethod' => $confirmationMethod,
        ]);
    }

    public function changeEmail(Request $request): View
    {
        $user = $request->user();
        $pendingUpdate = Cache::get('email_update_code:' . $user->id);

        return view('pages.profile.change-email', [
            'user' => $user,
            'emailUpdatePending' => $pendingUpdate ? true : false,
            'confirmationMethod' => $pendingUpdate['method'] ?? null,
        ]);
    }

    public function initiateEmailUpdate(UpdateEmailRequest $request): RedirectResponse
    {

        $request->validated();
        Log::info('Initiate email update', [
            'request' => $request->all(),
            'user_id' => $request->user()->id,
            'new_email' => $request->input('new_email'),
            'confirmation_method' => $request->input('confirmation_method'),
        ]);
        $user = $request->user();
        $newEmail = $request->input('new_email');
        $method = $request->input('confirmation_method');

        if ($method === 'authenticator' && ! $user->google_2fa_enabled) {
            return back()->withErrors(['confirmation_method' => __('profile.security_settings.2fa_not_enabled')]);
        }

        if ($method === 'authenticator') {
            // $secret = Google2FAFacade::generateSecretKey();
            // $user->google_2fa_secret = Crypt::encryptString($secret);
            // $user->save();

            Cache::put('email_update_code:' . $user->id, [
                'new_email' => $newEmail,
                'method' => 'authenticator',
                'expires_at' => now()->addMinutes(15),
            ], now()->addMinutes(15));

            return redirect()->route('profile.change-email')
                ->with('status', 'authenticator-required');
        }

        $verificationCode = random_int(100000, 999999);
        Cache::put('email_update_code:' . $user->id, [
            'new_email' => $newEmail,
            'code' => $verificationCode,
            'method' => 'email',
            'expires_at' => now()->addMinutes(15),
        ], now()->addMinutes(15));

        // Отправляем уведомление о коде подтверждения через диспетчер
        NotificationDispatcher::sendNotification(
            $user,
            EmailUpdateConfirmationNotification::class,
            [$verificationCode]
        );

        return redirect()->route('profile.change-email')
            ->with('status', 'email-code-sent');
    }

    public function confirmEmailUpdate(Request $request): RedirectResponse
    {
        $request->validate([
            'verification_code' => 'required|string|digits:6',
        ]);

        $user = $request->user();
        $pendingUpdate = Cache::get('email_update_code:' . $user->id);

        if (! $pendingUpdate) {
            Log::warning('Email update confirmation failed: no pending update found', [
                'user_id' => $user->id,
            ]);

            return back()->withErrors(['verification_code' => __('profile.security_settings.update_request_expired')]);
        }

        if (now()->isAfter($pendingUpdate['expires_at'])) {
            Log::warning('Email update confirmation failed: request expired', [
                'user_id' => $user->id,
                'expires_at' => $pendingUpdate['expires_at'],
            ]);
            Cache::forget('email_update_code:' . $user->id);

            return back()->withErrors(['verification_code' => __('profile.security_settings.update_request_expired')]);
        }

        $isValid = false;
        if ($pendingUpdate['method'] === 'authenticator') {
            $secret = Crypt::decryptString($user->google_2fa_secret);
            $isValid = Google2FAFacade::verifyKey($secret, $request->input('verification_code'));
        } else {
            $isValid = $request->input('verification_code') === (string) $pendingUpdate['code'];
        }

        if (! $isValid) {
            Log::warning('Email update confirmation failed: invalid code', [
                'user_id' => $user->id,
                'method' => $pendingUpdate['method'],
                'provided_code' => $request->input('verification_code'),
            ]);

            return back()->withErrors(['verification_code' => __('profile.security_settings.invalid_verification_code')]);
        }

        $oldEmail = $user->email;
        $user->email = $pendingUpdate['new_email'];
        $user->email_verified_at = null;
        $user->save();

        Cache::forget('email_update_code:' . $user->id);

        Log::info('Email updated successfully', [
            'user_id' => $user->id,
            'old_email' => $oldEmail,
            'new_email' => $pendingUpdate['new_email'],
        ]);

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
                'new_email' => $pendingUpdate['new_email'],
            ])
        );

        // Также отправляем уведомление на старый email через sendTo
        NotificationDispatcher::sendTo(
            'mail',
            $oldEmail,
            new EmailUpdatedNotification($oldEmail, $pendingUpdate['new_email'])
        );

        $activeTab = $request->query('tab', 'security');

        return redirect()->route('profile.settings', ['tab' => $activeTab])
            ->with('status', 'email-updated');
    }

    public function cancelEmailUpdate(Request $request): RedirectResponse
    {
        $user = $request->user();
        $pendingUpdate = Cache::get('email_update_code:' . $user->id);

        if ($pendingUpdate) {
            Log::info('Email update cancelled by user', [
                'user_id' => $user->id,
                'new_email' => $pendingUpdate['new_email'],
            ]);
            Cache::forget('email_update_code:' . $user->id);
        }

        $activeTab = $request->query('tab', 'security');

        return redirect()->route('profile.settings', ['tab' => $activeTab])
            ->with('status', 'email-update-cancelled');
    }

    /**
     * Update the user's notification settings.
     */
    public function updateNotifications(UpdateNotificationSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validatedSettings = $request->validated('notification_settings');
        $user->notification_settings = $validatedSettings ?: [];

        $user->save();

        $activeTab = $request->query('tab', 'notifications');

        return Redirect::route('profile.settings', ['tab' => $activeTab])->with('status', 'notifications-updated');
    }

    public function connect2fa(Request $request): View
    {
        $user = $request->user();
        Log::debug('2FA setup initiated', ['user_id' => $user->id, 'email' => $user->email]);

        $google2fa = app('pragmarx.google2fa');

        // Generate a new secret only if one doesn't exist or 2FA is not enabled
        if (empty($user->google_2fa_secret) || ! $user->google_2fa_enabled) {
            $secret = $google2fa->generateSecretKey();
            $request->session()->put('google_2fa_secret_temp', $secret);
            Log::debug('New 2FA secret generated', ['user_id' => $user->id, 'secret_length' => strlen($secret)]);
        } else {
            // If 2FA is enabled, this page should ideally be for disabling or viewing status.
            // For now, if a temp secret is in session (e.g. refresh during setup), use it.
            // Otherwise, we won't regenerate QR/secret for already enabled 2FA to avoid confusion.
            // The user should disable and re-enable if they need a new QR.
            // For this version, we'll re-fetch/generate if $secret is not in session.
            $secret = $google2fa->generateSecretKey(); // Regenerate if not in session for setup page
            $request->session()->put('google_2fa_secret_temp', $secret);
            Log::debug('Regenerating 2FA secret for existing 2FA user', ['user_id' => $user->id, 'is_2fa_enabled' => $user->google_2fa_enabled]);
        }

        $qrCodeInline = null;
        if ($secret) { // Only generate QR if we have a secret
            $qrCodeInline = $google2fa->getQRCodeInline(
                config('app.name', 'Laravel'),
                $user->email,
                $secret
            );
            Log::debug('QR code generated successfully', ['user_id' => $user->id]);
        } else {
            Log::error('Failed to generate QR code - no secret', ['user_id' => $user->id]);
        }

        return view('pages.profile.connect_2fa_step1', [
            'user' => $user,
            'qrCodeInline' => $qrCodeInline,
            'google_2fa_secret' => $secret,
        ]);
    }

    /**
     * Display the second step of 2FA setup
     */
    public function connect2faStep2(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        Log::debug('2FA setup step 2 initiated', ['user_id' => $user->id]);

        // Проверяем, есть ли временный секрет в сессии
        if (! $request->session()->has('google_2fa_secret_temp')) {
            Log::warning('No temp 2FA secret found in session', ['user_id' => $user->id]);
            Toast::error(__('profile.2fa.secret_not_found'));

            return redirect()->route('profile.connect-2fa')
                ->withErrors(['error' => 'Необходимо начать процесс активации с первого шага']);
        }

        $secret = $request->session()->get('google_2fa_secret_temp');
        Log::debug('Retrieved temp 2FA secret from session', ['user_id' => $user->id, 'secret_length' => strlen($secret)]);

        return view('pages.profile.connect_2fa_step2', [
            'user' => $user,
        ]);
    }

    public function store2fa(Request $request): RedirectResponse
    {
        $request->validate([
            'verification_code' => 'required|string|digits:6',
        ]);

        $user = $request->user();
        $otp = $request->input('verification_code');
        Log::debug('2FA verification attempt', [
            'user_id' => $user->id,
            'otp_length' => strlen($otp),
            'otp_masked' => substr($otp, 0, 2) . '****',
        ]);

        $google2fa = app('pragmarx.google2fa');

        $secret = $request->session()->get('google_2fa_secret_temp');

        if (! $secret) {
            Log::error('No temp 2FA secret found in session during verification', ['user_id' => $user->id]);
            Toast::error(__('profile.2fa.secret_not_found'));

            return Redirect::route('profile.connect-2fa')
                ->withErrors(['verification_code' => __('profile.2fa.secret_not_found')]);
        }

        Log::debug('Retrieved temp 2FA secret from session for verification', ['user_id' => $user->id, 'secret_length' => strlen($secret)]);

        // Ensure the secret being verified is the one stored (encrypted) for the user if 2FA was already enabled and being re-verified (not typical for initial setup)
        // For initial setup, $secret from session is correct.

        $valid = $google2fa->verifyKey($secret, $otp);
        Log::debug('2FA verification result', ['user_id' => $user->id, 'is_valid' => $valid]);

        if ($valid) {
            $user->google_2fa_secret = Crypt::encryptString($secret);
            $user->google_2fa_enabled = true;
            $user->save();
            Log::info('2FA successfully enabled for user', ['user_id' => $user->id]);

            $request->session()->forget('google_2fa_secret_temp');
            Log::debug('Temp 2FA secret removed from session', ['user_id' => $user->id]);

            Toast::success(__('profile.2fa.enabled'));

            return Redirect::route('profile.settings', ['tab' => 'security'])->with('status', '2fa-enabled');
        } else {
            // Pass the secret back to the view so the same QR code can be shown
            Log::warning('Invalid 2FA verification code provided', [
                'user_id' => $user->id,
                'otp_masked' => substr($otp, 0, 2) . '****',
            ]);
            Toast::error(__('profile.2fa.invalid_code'));

            return Redirect::route('profile.connect-2fa-step2')
                ->withInput()
                ->withErrors(['verification_code' => __('profile.2fa.invalid_code')]);
        }
    }

    public function disable2fa(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        Log::debug('2FA disable page requested', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toIso8601String(),
            'is_2fa_enabled' => $user->google_2fa_enabled,
            'has_2fa_secret' => ! empty($user->google_2fa_secret),
        ]);

        // Проверяем, что 2FA действительно включен у пользователя
        if (! $user->google_2fa_enabled) {
            Log::warning('Attempt to disable 2FA for user without 2FA enabled', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'timestamp' => now()->toIso8601String(),
            ]);
            Toast::error(__('profile.2fa.already_disabled'));

            return redirect()->route('profile.settings', ['tab' => 'security'])
                ->withErrors(['error' => 'Двухфакторная аутентификация уже отключена']);
        }

        Log::debug('Displaying 2FA disable warning', [
            'user_id' => $user->id,
            'route' => $request->route()->getName(),
            'session_id' => $request->session()->getId(),
            'timestamp' => now()->toIso8601String(),
        ]);

        return view('pages.profile.disable_2fa-notification', [
            'user' => $user,
        ]);
    }

    /**
     * Load the 2FA disable password form via AJAX
     */
    public function load2faDisableForm(Request $request)
    {
        $user = $request->user();

        Log::debug('2FA disable form requested via AJAX', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);

        // Проверяем, что 2FA действительно включен у пользователя
        if (! $user->google_2fa_enabled) {
            Log::warning('Attempt to load 2FA disable form for user without 2FA enabled', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'timestamp' => now()->toIso8601String(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.2fa.already_disabled'),
                'redirect' => route('profile.settings', ['tab' => 'security']),
            ], 400);
        }

        // Возвращаем отрендеренную форму
        $formHtml = view('pages.profile.disable_2fa', [
            'user' => $user,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $formHtml,
        ]);
    }

    /**
     * Подтверждение и отключение 2FA после проверки одноразового пароля
     */
    public function confirmDisable2fa(Request $request)
    {
        $request->validate([
            'verification_code' => 'required|string|digits:6',
        ]);

        $user = $request->user();
        $otp = $request->input('verification_code');

        Log::debug('2FA disable verification attempt', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'otp_length' => strlen($otp),
            'otp_masked' => $otp ? substr($otp, 0, 2) . '****' : 'null',
            'request_all' => $request->all(),
            'timestamp' => now()->toIso8601String(),
            'session_id' => $request->session()->getId(),
        ]);

        // Проверяем, что 2FA включен у пользователя
        if (! $user->google_2fa_enabled || ! $user->google_2fa_secret) {
            Log::warning('Attempt to disable 2FA for user without 2FA enabled', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'has_2fa_enabled' => $user->google_2fa_enabled,
                'has_2fa_secret' => ! empty($user->google_2fa_secret),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.2fa.already_disabled'),
                    'redirect' => route('profile.settings', ['tab' => 'security']),
                ], 400);
            }

            // Toast::error(__('profile.2fa.already_disabled'));

            return redirect()->route('profile.settings', ['tab' => 'security'])
                ->withErrors(['error' => 'Двухфакторная аутентификация уже отключена']);
        }

        $google2fa = app('pragmarx.google2fa');
        Log::debug('Google2FA instance created', [
            'user_id' => $user->id,
            'timestamp' => now()->toIso8601String(),
        ]);

        try {
            $secret = Crypt::decryptString($user->google_2fa_secret);
            Log::debug('Retrieved and decrypted 2FA secret for verification', [
                'user_id' => $user->id,
                'secret_length' => strlen($secret),
                'secret_hash' => md5($secret), // Хеш для отладки без раскрытия самого секрета
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to decrypt 2FA secret', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'timestamp' => now()->toIso8601String(),
            ]);

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.2fa.error_decrypting_secret'),
                    'errors' => [
                        'verification_code' => ['Ошибка расшифровки секретного ключа. Пожалуйста, обратитесь в поддержку.'],
                    ],
                ], 500);
            }

            // Toast::error(__('profile.2fa.error_decrypting_secret'));

            return redirect()->route('profile.disable-2fa', ['tab' => 'security'])
                ->withErrors(['verification_code' => 'Ошибка расшифровки секретного ключа. Пожалуйста, обратитесь в поддержку.']);
        }

        // Проверяем одноразовый пароль
        Log::debug('About to verify OTP', [
            'user_id' => $user->id,
            'otp_masked' => substr($otp, 0, 2) . '****',
            'secret_hash' => md5($secret),
            'timestamp' => now()->toIso8601String(),
        ]);

        $valid = $google2fa->verifyKey($secret, $otp);
        Log::debug('2FA disable verification result', [
            'user_id' => $user->id,
            'is_valid' => $valid,
            'ip' => $request->ip(),
            'timestamp' => now()->toIso8601String(),
            'verification_time_ms' => microtime(true) - LARAVEL_START,
        ]);

        if ($valid) {
            // Отключаем 2FA
            Log::info('About to disable 2FA for user', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'timestamp' => now()->toIso8601String(),
            ]);

            $user->google_2fa_enabled = false;
            $user->google_2fa_secret = null; // Clear the secret
            $user->save();

            Log::info('2FA successfully disabled for user', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'timestamp' => now()->toIso8601String(),
                'db_update_success' => true,
            ]);

            $activeTab = $request->query('tab', 'security');

            Log::debug('Responding after successful 2FA disable', [
                'user_id' => $user->id,
                'redirect_to' => 'profile.settings',
                'tab' => $activeTab,
                'timestamp' => now()->toIso8601String(),
                'is_ajax' => $request->ajax(),
            ]);

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('profile.2fa.disabled'),
                    'redirect' => route('profile.connect-2fa'),
                ]);
            }

            // Toast::success(__('profile.2fa.disabled'));

            return redirect()->route('profile.settings', ['tab' => $activeTab])
                ->with('status', 'authenticator-disabled');
        } else {
            Log::warning('Invalid 2FA code provided for disabling 2FA', [
                'user_id' => $user->id,
                'otp_masked' => substr($otp, 0, 2) . '****',
                'ip' => $request->ip(),
                'attempt_time' => now()->toIso8601String(),
                'user_agent' => $request->userAgent(),
                'failed_verification' => true,
            ]);

            Log::debug('Responding after failed 2FA disable attempt', [
                'user_id' => $user->id,
                'timestamp' => now()->toIso8601String(),
                'is_ajax' => $request->ajax(),
            ]);

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.2fa.invalid_code'),
                    'errors' => [
                        'verification_code' => [__('profile.2fa.invalid_code')],
                    ],
                ], 422);
            }

            // Toast::error(__('profile.2fa.invalid_code'));

            return redirect()->route('profile.disable-2fa')
                ->withErrors(['verification_code' => __('profile.2fa.invalid_code')]);
        }
    }

    /**
     * Display the personal greeting form, potentially with a pending confirmation.
     */
    public function personalGreeting(Request $request): View
    {
        $user = $request->user();
        $pendingUpdate = Cache::get('personal_greeting_update_code:' . $user->id);

        return view('pages.profile.personal-greeting', [
            'user' => $user,
            'personalGreetingUpdatePending' => $pendingUpdate ? true : false,
            'confirmationMethod' => $pendingUpdate['method'] ?? null,
            'isExpired' => $pendingUpdate && isset($pendingUpdate['expires_at']) && now()->isAfter($pendingUpdate['expires_at']),
        ]);
    }

    /**
     * Initiate the personal greeting update process.
     * This replaces the old updatePersonalGreeting method's direct save.
     */
    public function initiatePersonalGreetingUpdate(UpdatePersonalGreetingSettingsRequest $request): RedirectResponse
    {
        // Validate personal_greeting (already done by UpdatePersonalGreetingSettingsRequest)
        // Add validation for confirmation_method if not in the request class
        $request->validate([
            'confirmation_method' => ['required', 'string', \Illuminate\Validation\Rule::in(['email', 'authenticator'])],
            // Add password confirmation if required for initiating changes, similar to UpdateEmailRequest
            // 'password' => ['required', 'string', 'current_password'],
        ]);

        $user = $request->user();
        $newPersonalGreeting = $request->input('personal_greeting');
        $method = $request->input('confirmation_method');

        if ($method === 'authenticator' && ! $user->google_2fa_enabled) {
            return back()->withErrors(['confirmation_method' => __('profile.security_settings.2fa_not_enabled')])->withInput();
        }

        // Clear any previous expired attempts
        if (Cache::has('personal_greeting_update_code:' . $user->id)) {
            Cache::forget('personal_greeting_update_code:' . $user->id);
        }

        if ($method === 'authenticator') {
            Cache::put('personal_greeting_update_code:' . $user->id, [
                'personal_greeting' => $newPersonalGreeting,
                'method' => 'authenticator',
                'expires_at' => now()->addMinutes(15),
            ], now()->addMinutes(15));

            Log::info('Personal greeting update initiated via authenticator.', ['user_id' => $user->id]);

            return redirect()->route('profile.personal-greeting')
                ->with('status', 'authenticator-required-for-greeting');
        }

        // Email confirmation
        $verificationCode = random_int(100000, 999999);
        Cache::put('personal_greeting_update_code:' . $user->id, [
            'personal_greeting' => $newPersonalGreeting,
            'code' => $verificationCode,
            'method' => 'email',
            'expires_at' => now()->addMinutes(15),
        ], now()->addMinutes(15));

        // Используем метод sendNotification
        NotificationDispatcher::sendNotification(
            $user,
            PersonalGreetingUpdateConfirmationNotification::class,
            [$verificationCode]
        );

        Log::info('Personal greeting update initiated via email.', ['user_id' => $user->id, 'email' => $user->email]);

        return redirect()->route('profile.personal-greeting')
            ->with('status', 'greeting-code-sent');
    }

    /**
     * Confirm and apply the personal greeting update.
     */
    public function confirmPersonalGreetingUpdate(Request $request): RedirectResponse
    {
        $request->validate([
            'verification_code' => 'required|string|digits:6',
        ]);

        $user = $request->user();
        $pendingUpdate = Cache::get('personal_greeting_update_code:' . $user->id);

        if (! $pendingUpdate) {
            Log::warning('Personal greeting update confirmation failed: no pending update found', ['user_id' => $user->id]);

            return redirect()->route('profile.personal-greeting')->withErrors(['verification_code' => __('profile.security_settings.update_request_expired')]);
        }

        if (now()->isAfter($pendingUpdate['expires_at'])) {
            Log::warning('Personal greeting update confirmation failed: request expired', [
                'user_id' => $user->id,
                'expires_at' => $pendingUpdate['expires_at'],
            ]);
            Cache::forget('personal_greeting_update_code:' . $user->id);

            return redirect()->route('profile.personal-greeting')->withErrors(['verification_code' => __('profile.security_settings.update_request_expired')]);
        }

        $isValid = false;
        if ($pendingUpdate['method'] === 'authenticator') {
            if (! $user->google_2fa_secret) {
                Log::error('Personal greeting 2FA confirmation failed: 2FA secret not found for user.', ['user_id' => $user->id]);

                return redirect()->route('profile.personal-greeting')->withErrors(['verification_code' => __('profile.2fa.error_verifying_code')]); // Generic error
            }
            $secret = Crypt::decryptString($user->google_2fa_secret);
            $isValid = Google2FAFacade::verifyKey($secret, $request->input('verification_code'));
        } else { // Email
            $isValid = $request->input('verification_code') === (string) $pendingUpdate['code'];
        }

        if (! $isValid) {
            Log::warning('Personal greeting update confirmation failed: invalid code', [
                'user_id' => $user->id,
                'method' => $pendingUpdate['method'],
                'provided_code' => $request->input('verification_code'),
            ]);

            return back()->withErrors(['verification_code' => __('profile.security_settings.invalid_verification_code')]);
        }

        $user->personal_greeting = $pendingUpdate['personal_greeting'];
        $user->save();

        Cache::forget('personal_greeting_update_code:' . $user->id);

        Log::info('Personal greeting updated successfully.', ['user_id' => $user->id]);

        // Отправляем уведомление об успешном обновлении через quickSend
        NotificationDispatcher::quickSend(
            $user,
            NotificationType::PROFILE_UPDATED,
            ['greeting_updated' => true],
            __('profile.personal_greeting_update.success_title'),
            __('profile.personal_greeting_update.success_message')
        );

        // Determine active tab for redirect, assuming personal greeting is on the 'security' or a new 'general' tab.
        // For this example, let's assume it's part of general settings, redirecting to profile.settings with 'personal' tab
        $activeTab = $request->query('tab', 'personal'); // Or a specific tab if personal greeting moves

        return redirect()->route('profile.settings', ['tab' => $activeTab]) // Or profile.personal-greeting if it's a standalone page
            ->with('status', 'personal-greeting-updated');
    }

    /**
     * Cancel a pending personal greeting update.
     */
    public function cancelPersonalGreetingUpdate(Request $request): RedirectResponse
    {
        $user = $request->user();
        $pendingUpdate = Cache::get('personal_greeting_update_code:' . $user->id);

        if ($pendingUpdate) {
            Log::info('Personal greeting update cancelled by user', ['user_id' => $user->id]);
            Cache::forget('personal_greeting_update_code:' . $user->id);
        }

        $activeTab = $request->query('tab', 'personal');

        return redirect()->route('profile.personal-greeting') // Or profile.settings if preferred
            ->with('status', 'personal-greeting-update-cancelled');
    }

    public function ipRestriction(Request $request): View
    {
        $user = $request->user();

        return view('pages.profile.ip-restriction', [
            'user' => $user,
            'ip_restrictions' => $user->ip_restrictions ?? [],
        ]);
    }

    public function updateIpRestriction(UpdateIpRestrictionRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Получаем текущий список IP
        $currentIps = $user->ip_restrictions ?? [];

        // Получаем новые IP из формы
        $newIps = array_filter(array_map('trim', explode("\n", $request->input('ip_restrictions', ''))));

        // Объединяем и удаляем дубликаты
        $user->ip_restrictions = array_unique(array_merge($currentIps, $newIps));
        $user->save();

        return Redirect::route('profile.ip-restriction')->with('status', 'ip-restriction-updated');
    }

    public function initiatePasswordUpdate(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
            'confirmation_method' => 'required|in:authenticator,email',
        ]);

        $user = $request->user();
        $method = $request->input('confirmation_method');

        if ($method === 'authenticator' && ! $user->google_2fa_enabled) {
            return back()->withErrors(['confirmation_method' => __('profile.security_settings.2fa_not_enabled')]);
        }

        if ($method === 'authenticator') {
            Cache::put('password_update_code:' . $user->id, [
                'password' => $request->input('password'),
                'method' => 'authenticator',
                'expires_at' => now()->addMinutes(15),
            ], now()->addMinutes(15));

            return redirect()->route('profile.change-password')
                ->with('status', 'authenticator-required');
        }

        $verificationCode = random_int(100000, 999999);
        Cache::put('password_update_code:' . $user->id, [
            'password' => $request->input('password'),
            'code' => $verificationCode,
            'method' => 'email',
            'expires_at' => now()->addMinutes(15),
        ], now()->addMinutes(15));

        // Используем метод sendNotification для отправки уведомления о смене пароля
        NotificationDispatcher::sendTo(
            'mail',
            $user->email,
            new PasswordUpdateConfirmationNotification($verificationCode)
        );

        return redirect()->route('profile.change-password')
            ->with('status', 'password-code-sent');
    }

    public function confirmPasswordUpdate(Request $request): RedirectResponse
    {
        $request->validate([
            'verification_code' => 'required|string|digits:6',
        ]);

        $user = $request->user();
        $pendingUpdate = Cache::get('password_update_code:' . $user->id);

        if (! $pendingUpdate) {
            Log::warning('Password update confirmation failed: no pending update found', [
                'user_id' => $user->id,
            ]);

            return back()->withErrors(['verification_code' => __('profile.security_settings.update_request_expired')]);
        }

        if (now()->isAfter($pendingUpdate['expires_at'])) {
            Log::warning('Password update confirmation failed: request expired', [
                'user_id' => $user->id,
                'expires_at' => $pendingUpdate['expires_at'],
            ]);
            Cache::forget('password_update_code:' . $user->id);

            return back()->withErrors(['verification_code' => __('profile.security_settings.update_request_expired')]);
        }

        $isValid = false;
        if ($pendingUpdate['method'] === 'authenticator') {
            $secret = Crypt::decryptString($user->google_2fa_secret);
            $isValid = Google2FAFacade::verifyKey($secret, $request->input('verification_code'));
        } else {
            $isValid = $request->input('verification_code') === (string) $pendingUpdate['code'];
        }

        if (! $isValid) {
            Log::warning('Password update confirmation failed: invalid code', [
                'user_id' => $user->id,
                'method' => $pendingUpdate['method'],
                'provided_code' => $request->input('verification_code'),
            ]);

            return back()->withErrors(['verification_code' => __('profile.security_settings.invalid_verification_code')]);
        }

        $user->password = Hash::make($pendingUpdate['password']);
        $user->save();

        Cache::forget('password_update_code:' . $user->id);

        Log::info('Password updated successfully', [
            'user_id' => $user->id,
        ]);

        // Отправляем уведомление об успешном обновлении пароля
        NotificationDispatcher::quickSend(
            $user,
            NotificationType::PASSWORD_CHANGED,
            [],
            __('profile.security_settings.password_updated_success_title'),
            __('profile.security_settings.password_updated_success_message')
        );

        $activeTab = $request->query('tab', 'security');

        return redirect()->route('profile.settings', ['tab' => $activeTab])
            ->with('status', 'password-updated');
    }

    public function cancelPasswordUpdate(Request $request): RedirectResponse
    {
        $user = $request->user();
        $pendingUpdate = Cache::get('password_update_code:' . $user->id);

        if ($pendingUpdate) {
            Log::info('Password update cancelled by user', [
                'user_id' => $user->id,
            ]);
            Cache::forget('password_update_code:' . $user->id);
        }

        $activeTab = $request->query('tab', 'security');

        return redirect()->route('profile.settings', ['tab' => $activeTab])
            ->with('status', 'password-update-cancelled');
    }

    /**
     * Regenerate 2FA secret key for the user (AJAX endpoint)
     */
    public function regenerate2faSecret(Request $request)
    {
        $user = $request->user();
        Log::debug('2FA secret regeneration requested', ['user_id' => $user->id]);

        $google2fa = app('pragmarx.google2fa');
        $secret = $google2fa->generateSecretKey();

        // Store new secret in session (temporary)
        $request->session()->put('google_2fa_secret_temp', $secret);
        Log::debug('New 2FA secret generated and stored in session', ['user_id' => $user->id, 'secret_length' => strlen($secret)]);

        // Generate QR code
        $qrCodeInline = $google2fa->getQRCodeInline(
            config('app.name', 'Laravel'),
            $user->email,
            $secret
        );

        return response()->json([
            'success' => true,
            'secret' => $secret,
            'qrCode' => $qrCodeInline,
        ]);
    }
}
