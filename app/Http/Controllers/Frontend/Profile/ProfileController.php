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

        $user = $request->user();
        $newEmail = $request->input('new_email');
        $method = $request->input('confirmation_method');

        if ($method === 'authenticator' && ! $user->google_2fa_enabled) {
            return back()->withErrors(['confirmation_method' => __('profile.error.2fa_not_enabled')]);
        }

        if ($method === 'authenticator') {
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

            return back()->withErrors(['verification_code' => __('profile.security_settings.update_request_expired')]);
        }

        if (now()->isAfter($pendingUpdate['expires_at'])) {
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

            return back()->withErrors(['verification_code' => __('profile.error.invalid_verification_code')]);
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


        $activeTab = $request->query('tab', 'security');

        return redirect()->route('profile.settings', ['tab' => $activeTab])
            ->with('status', 'email-updated');
    }

    public function cancelEmailUpdate(Request $request): RedirectResponse
    {
        $user = $request->user();
        $pendingUpdate = Cache::get('email_update_code:' . $user->id);

        if ($pendingUpdate) {
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

        $google2fa = app('pragmarx.google2fa');

        // Generate a new secret only if one doesn't exist or 2FA is not enabled
        if (empty($user->google_2fa_secret) || ! $user->google_2fa_enabled) {
            $secret = $google2fa->generateSecretKey();
            $request->session()->put('google_2fa_secret_temp', $secret);
        } else {
            // If 2FA is enabled, this page should ideally be for disabling or viewing status.
            // For now, if a temp secret is in session (e.g. refresh during setup), use it.
            // Otherwise, we won't regenerate QR/secret for already enabled 2FA to avoid confusion.
            // The user should disable and re-enable if they need a new QR.
            // For this version, we'll re-fetch/generate if $secret is not in session.
            $secret = $google2fa->generateSecretKey(); // Regenerate if not in session for setup page
            $request->session()->put('google_2fa_secret_temp', $secret);
        }

        $qrCodeInline = null;
        if ($secret) { // Only generate QR if we have a secret
            $qrCodeInline = $google2fa->getQRCodeInline(
                config('app.name', 'Laravel'),
                $user->email,
                $secret
            );
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

        // Проверяем, есть ли временный секрет в сессии
        if (! $request->session()->has('google_2fa_secret_temp')) {

            return redirect()->route('profile.connect-2fa')
                ->withErrors(['error' => __('profile.2fa.start_activation_process')]);
        }

        $secret = $request->session()->get('google_2fa_secret_temp');

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

        $google2fa = app('pragmarx.google2fa');

        $secret = $request->session()->get('google_2fa_secret_temp');

        if (! $secret) {

            return Redirect::route('profile.connect-2fa')
                ->withErrors(['verification_code' => __('profile.2fa.secret_not_found')]);
        }

        $valid = $google2fa->verifyKey($secret, $otp);

        if ($valid) {
            $user->google_2fa_secret = Crypt::encryptString($secret);
            $user->google_2fa_enabled = true;
            $user->save();

            $request->session()->forget('google_2fa_secret_temp');

            return Redirect::route('profile.settings', ['tab' => 'security'])->with('status', '2fa-enabled');
        } else {

            return Redirect::route('profile.connect-2fa-step2')
                ->withInput()
                ->withErrors(['verification_code' => __('profile.2fa.invalid_code')]);
        }
    }

    public function disable2fa(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // Проверяем, что 2FA действительно включен у пользователя
        if (! $user->google_2fa_enabled) {

            return redirect()->route('profile.settings', ['tab' => 'security'])
                ->withErrors(['error' => __('profile.2fa.already_disabled')]);
        }

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

        // Проверяем, что 2FA действительно включен у пользователя
        if (! $user->google_2fa_enabled) {

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


        // Проверяем, что 2FA включен у пользователя
        if (! $user->google_2fa_enabled || ! $user->google_2fa_secret) {

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
                ->withErrors(['error' => __('profile.2fa.already_disabled')]);
        }

        $google2fa = app('pragmarx.google2fa');

        try {
            $secret = Crypt::decryptString($user->google_2fa_secret);
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
                        'verification_code' => [__('profile.2fa.error_decrypting_secret')],
                    ],
                ], 500);
            }

            // Toast::error(__('profile.2fa.error_decrypting_secret'));

            return redirect()->route('profile.disable-2fa', ['tab' => 'security'])
                ->withErrors(['verification_code' => __('profile.2fa.error_decrypting_secret')]);
        }

        // Проверяем одноразовый пароль

        $valid = $google2fa->verifyKey($secret, $otp);


        if ($valid) {

            $user->google_2fa_enabled = false;
            $user->google_2fa_secret = null; // Clear the secret
            $user->save();

            $activeTab = $request->query('tab', 'security');

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => __('profile.success.2fa_disabled'),
                    'redirect' => route('profile.connect-2fa'),
                ]);
            }

            return redirect()->route('profile.settings', ['tab' => $activeTab])
                ->with('status', 'authenticator-disabled');
        } else {

            // Return JSON response for AJAX requests
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => __('profile.2fa.invalid_code'),
                    'errors' => [
                        'verification_code' => [__('profile.error.invalid_verification_code')],
                    ],
                ], 422);
            }

            return redirect()->route('profile.disable-2fa')
                ->withErrors(['verification_code' => __('profile.error.invalid_verification_code')]);
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
            return back()->withErrors(['confirmation_method' => __('profile.error.2fa_not_enabled')])->withInput();
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
            return redirect()->route('profile.personal-greeting')->withErrors(['verification_code' => __('profile.security_settings.update_request_expired')]);
        }

        if (now()->isAfter($pendingUpdate['expires_at'])) {
            Cache::forget('personal_greeting_update_code:' . $user->id);

            return redirect()->route('profile.personal-greeting')->withErrors(['verification_code' => __('profile.security_settings.update_request_expired')]);
        }

        $isValid = false;
        if ($pendingUpdate['method'] === 'authenticator') {
            if (! $user->google_2fa_secret) {
                return redirect()->route('profile.personal-greeting')->withErrors(['verification_code' => __('profile.error.invalid_verification_code')]); // Generic error
            }
            $secret = Crypt::decryptString($user->google_2fa_secret);
            $isValid = Google2FAFacade::verifyKey($secret, $request->input('verification_code'));
        } else { // Email
            $isValid = $request->input('verification_code') === (string) $pendingUpdate['code'];
        }

        if (! $isValid) {

            return back()->withErrors(['verification_code' => __('profile.error.invalid_verification_code')]);
        }

        $user->personal_greeting = $pendingUpdate['personal_greeting'];
        $user->save();

        Cache::forget('personal_greeting_update_code:' . $user->id);

        // Отправляем уведомление об успешном обновлении через quickSend


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
            return back()->withErrors(['confirmation_method' => __('profile.error.2fa_not_enabled')]);
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
            return back()->withErrors(['verification_code' => __('profile.security_settings.update_request_expired')]);
        }

        if (now()->isAfter($pendingUpdate['expires_at'])) {
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

            return back()->withErrors(['verification_code' => __('profile.error.invalid_verification_code')]);
        }

        $user->password = Hash::make($pendingUpdate['password']);
        $user->save();

        Cache::forget('password_update_code:' . $user->id);

        Log::info('Password updated successfully', [
            'user_id' => $user->id,
        ]);



        $activeTab = $request->query('tab', 'security');

        return redirect()->route('profile.settings', ['tab' => $activeTab])
            ->with('status', 'password-updated');
    }

    public function cancelPasswordUpdate(Request $request): RedirectResponse
    {
        $user = $request->user();
        $pendingUpdate = Cache::get('password_update_code:' . $user->id);

        if ($pendingUpdate) {
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

        // Generate QR code
        $qrCodeInline = $google2fa->getQRCodeInline(
            config('app.name', 'spy.house'),
            $user->email,
            $secret
        );

        return response()->json([
            'success' => true,
            'secret' => $secret,
            'qrCode' => $qrCodeInline,
        ]);
    }

    /**
     * Get HTML content for 2FA step 2 via AJAX
     */
    public function getConnect2faStep2Content(Request $request)
    {
        $user = $request->user();

        // Проверяем, есть ли временный секрет в сессии
        if (!$request->session()->has('google_2fa_secret_temp')) {
            return response()->json([
                'success' => false,
                'message' => __('profile.2fa.invalid_request'),
                'redirect' => route('profile.connect-2fa')
            ], 400);
        }

        // Рендерим только контент второго шага
        $htmlContent = view('components.profile.two-factor.step2-content', [
            'user' => $user,
        ])->render();

        return response()->json([
            'success' => true,
            'html' => $htmlContent,
        ]);
    }

    /**
     * Store 2FA verification via AJAX
     */
    public function store2faAjax(Request $request)
    {
        if (!$request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => __('profile.2fa.invalid_request'),
            ], 400);
        }

        $request->validate([
            'verification_code' => 'required|string|digits:6',
        ]);

        $user = $request->user();
        $otp = $request->input('verification_code');

        $google2fa = app('pragmarx.google2fa');

        $secret = $request->session()->get('google_2fa_secret_temp');

        if (!$secret) {
            return response()->json([
                'success' => false,
                'message' => __('profile.2fa.secret_not_found'),
                'redirect' => route('profile.connect-2fa')
            ], 400);
        }

        $valid = $google2fa->verifyKey($secret, $otp);

        if ($valid) {
            $user->google_2fa_secret = Crypt::encryptString($secret);
            $user->google_2fa_enabled = true;
            $user->save();

            $request->session()->forget('google_2fa_secret_temp');

            return response()->json([
                'success' => true,
                'message' => __('profile.2fa.enabled'),
                'redirect' => route('profile.disable-2fa')
            ]);
        } else {
            Log::warning('Invalid 2FA verification code provided via AJAX', [
                'user_id' => $user->id,
                'otp_masked' => substr($otp, 0, 2) . '****',
            ]);

            return response()->json([
                'success' => false,
                'message' => __('profile.2fa.invalid_code'),
                'errors' => [
                    'verification_code' => [__('profile.2fa.invalid_code')]
                ]
            ], 422);
        }
    }
    public function verifyYourAccount(Request $request): View
    {
        $user = $request->user();

        return view('pages.profile.verify-your-account', [
            'user' => $user,
        ]);
    }
}
