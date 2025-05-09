<?php

namespace App\Http\Controllers\Frontend\Profile;

use App\Enums\Frontend\UserExperience;
use App\Enums\Frontend\UserScopeOfActivity;
use App\Http\Controllers\FrontendController;
use App\Http\Requests\Profile\ProfileUpdateRequest;
use App\Http\Requests\Profile\ProfileSettingsUpdateRequest;
use App\Http\Requests\Profile\UpdateEmailRequest;
use App\Http\Requests\Profile\UpdateNotificationSettingsRequest;
use App\Services\Api\TokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use PragmaRX\Google2FALaravel\Facade as Google2FAFacade;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Services\App\ImageService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Notifications\Profile\EmailUpdateConfirmationNotification;
use App\Notifications\Profile\EmailUpdatedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Hash;
use App\Notifications\Profile\PasswordUpdateConfirmationNotification;
use App\Http\Requests\Profile\UpdatePersonalGreetingSettingsRequest;

class ProfileController extends FrontendController
{
    private $settingsView = 'pages.profile.settings';
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
     * Update the user's profile settings information.
     */
    public function updateSettings(ProfileSettingsUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validatedData = $request->validated();
        $settingsData = [];
        if (isset($validatedData['login'])) {
            $settingsData['login'] = $validatedData['login'];
        }
        if (isset($validatedData['name'])) {
            $settingsData['name'] = $validatedData['name'];
        }
        if (isset($validatedData['surname'])) {
            $settingsData['surname'] = $validatedData['surname'];
        }
        if (isset($validatedData['date_of_birth'])) {
            $settingsData['date_of_birth'] = $validatedData['date_of_birth'];
        }
        if (isset($validatedData['experience'])) {
            $settingsData['experience'] = $validatedData['experience'];
        }
        if (isset($validatedData['scope_of_activity'])) {
            $settingsData['scope_of_activity'] = $validatedData['scope_of_activity'];
        }
        if (isset($validatedData['messengers'])) {
            $settingsData['messengers'] = $validatedData['messengers'];
        }
        if (isset($validatedData['whatsapp_phone'])) {
            $settingsData['whatsapp_phone'] = $validatedData['whatsapp_phone'] ?? null;
        }
        if (isset($validatedData['viber_phone'])) {
            $settingsData['viber_phone'] = $validatedData['viber_phone'] ?? null;
        }
        if (isset($validatedData['telegram'])) {
            $settingsData['telegram'] = $validatedData['telegram'] ?? null;
        }

        if (isset($validatedData['user_avatar'])) {
            $imageService = app(ImageService::class);
            $avatarPath = $imageService->replace(
                $validatedData['user_avatar'],
                $user->user_avatar,
                'avatars'
            );

            $image = getimagesize($validatedData['user_avatar']);
            $settingsData['user_avatar_metadata'] = [
                'size' => round($validatedData['user_avatar']->getSize() / 1024),
                'name' => $validatedData['user_avatar']->getClientOriginalName(),
                'file_type' => $validatedData['user_avatar']->getClientMimeType(),
                'dimensions' => [
                    'width' => $image[0] ?? 0,
                    'height' => $image[1] ?? 0
                ],
                'proportions' => $image[0] && $image[1] ? round($image[0] / $image[1], 2) : 0
            ];

            $settingsData['user_avatar'] = $avatarPath;
        }

        $user->fill($settingsData);
        $user->save();

        return Redirect::route('profile.settings')->with('status', 'settings-updated');
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
        $user = $request->user();
        $experiences = UserExperience::getTranslatedList();
        $scopes = UserScopeOfActivity::getTranslatedList();

        // Get user's tokens
        $tokens = app(TokenService::class)->getUserTokens($user);
        return view($this->settingsView, [
            'user' => $request->user(),
            'scopes' => $scopes,
            'tokens' => $tokens,
            'api_token' => session('api_token'),
            'experiences' => $experiences,
        ]);
    }

    public function changePassword(Request $request): View
    {
        $user = $request->user();
        $pendingUpdate = Cache::get('password_update_code:' . $user->id);

        return view('pages.profile.change-password', [
            'user' => $user,
            'passwordUpdatePending' => $pendingUpdate ? true : false,
            'confirmationMethod' => $pendingUpdate['method'] ?? null
        ]);
    }

    public function changeEmail(Request $request): View
    {
        $user = $request->user();
        $pendingUpdate = Cache::get('email_update_code:' . $user->id);

        return view('pages.profile.change-email', [
            'user' => $user,
            'emailUpdatePending' => $pendingUpdate ? true : false,
            'confirmationMethod' => $pendingUpdate['method'] ?? null
        ]);
    }

    public function initiateEmailUpdate(UpdateEmailRequest $request): RedirectResponse
    {

        $request->validated();
        Log::info('Initiate email update', [
            'request' => $request->all(),
            'user_id' => $request->user()->id,
            'new_email' => $request->input('new_email'),
            'confirmation_method' => $request->input('confirmation_method')
        ]);
        $user = $request->user();
        $newEmail = $request->input('new_email');
        $method = $request->input('confirmation_method');

        if ($method === 'authenticator' && !$user->google_2fa_enabled) {
            return back()->withErrors(['confirmation_method' => __('profile.security_settings.2fa_not_enabled')]);
        }

        if ($method === 'authenticator') {
            // $secret = Google2FAFacade::generateSecretKey();
            // $user->google_2fa_secret = Crypt::encryptString($secret);
            // $user->save();

            Cache::put('email_update_code:' . $user->id, [
                'new_email' => $newEmail,
                'method' => 'authenticator',
                'expires_at' => now()->addMinutes(15)
            ], now()->addMinutes(15));

            return redirect()->route('profile.change-email')
                ->with('status', 'authenticator-required');
        }

        $verificationCode = random_int(100000, 999999);
        Cache::put('email_update_code:' . $user->id, [
            'new_email' => $newEmail,
            'code' => $verificationCode,
            'method' => 'email',
            'expires_at' => now()->addMinutes(15)
        ], now()->addMinutes(15));

        Notification::route('mail', $newEmail)
            ->notify(new EmailUpdateConfirmationNotification($verificationCode));

        return redirect()->route('profile.change-email')
            ->with('status', 'email-code-sent');
    }

    public function confirmEmailUpdate(Request $request): RedirectResponse
    {
        $request->validate([
            'verification_code' => 'required|string|digits:6'
        ]);

        $user = $request->user();
        $pendingUpdate = Cache::get('email_update_code:' . $user->id);

        if (!$pendingUpdate) {
            Log::warning('Email update confirmation failed: no pending update found', [
                'user_id' => $user->id
            ]);
            return back()->withErrors(['verification_code' => __('profile.security_settings.update_request_expired')]);
        }

        if (now()->isAfter($pendingUpdate['expires_at'])) {
            Log::warning('Email update confirmation failed: request expired', [
                'user_id' => $user->id,
                'expires_at' => $pendingUpdate['expires_at']
            ]);
            Cache::forget('email_update_code:' . $user->id);
            return back()->withErrors(['verification_code' => __('profile.security_settings.update_request_expired')]);
        }

        $isValid = false;
        if ($pendingUpdate['method'] === 'authenticator') {
            $secret = Crypt::decryptString($user->google_2fa_secret);
            $isValid = Google2FAFacade::verifyKey($secret, $request->input('verification_code'));
        } else {
            $isValid = $request->input('verification_code') === (string)$pendingUpdate['code'];
        }

        if (!$isValid) {
            Log::warning('Email update confirmation failed: invalid code', [
                'user_id' => $user->id,
                'method' => $pendingUpdate['method'],
                'provided_code' => $request->input('verification_code')
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
            'new_email' => $pendingUpdate['new_email']
        ]);

        Notification::route('mail', $oldEmail)
            ->notify(new EmailUpdatedNotification($oldEmail, $pendingUpdate['new_email']));
        Notification::route('mail', $pendingUpdate['new_email'])
            ->notify(new EmailUpdatedNotification($oldEmail, $pendingUpdate['new_email']));

        return redirect()->route('profile.settings')
            ->with('status', 'email-updated');
    }

    public function cancelEmailUpdate(Request $request): RedirectResponse
    {
        $user = $request->user();
        $pendingUpdate = Cache::get('email_update_code:' . $user->id);

        if ($pendingUpdate) {
            Log::info('Email update cancelled by user', [
                'user_id' => $user->id,
                'new_email' => $pendingUpdate['new_email']
            ]);
            Cache::forget('email_update_code:' . $user->id);
        }

        return redirect()->route('profile.settings')
            ->with('status', 'email-update-cancelled');
    }

    /**
     * Update the user's notification settings.
     * @param UpdateNotificationSettingsRequest $request
     * @return RedirectResponse
     */
    public function updateNotifications(UpdateNotificationSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validatedSettings = $request->validated('notification_settings');
        $user->notification_settings = $validatedSettings ?: [];

        $user->save();

        return Redirect::route('profile.settings')->with('status', 'notifications-updated');
    }

    public function connect2fa(Request $request): View
    {
        $user = $request->user();
        $google2fa = app('pragmarx.google2fa');

        // Generate a new secret only if one doesn't exist or 2FA is not enabled
        if (empty($user->google_2fa_secret) || !$user->google_2fa_enabled) {
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
        }

        return view('pages.profile.connect_2fa', [
            'user' => $user,
            'qrCodeInline' => $qrCodeInline,
            'google_2fa_secret' => $secret,
        ]);
    }

    public function store2fa(Request $request): RedirectResponse
    {
        $request->validate([
            'one_time_password' => 'required|string|digits:6',
        ]);

        $user = $request->user();
        $google2fa = app('pragmarx.google2fa');

        $secret = $request->session()->get('google_2fa_secret_temp');

        if (!$secret) {
            return Redirect::route('profile.connect-2fa')
                ->withErrors(['one_time_password' => __('profile.2fa.secret_not_found')]);
        }

        // Ensure the secret being verified is the one stored (encrypted) for the user if 2FA was already enabled and being re-verified (not typical for initial setup)
        // For initial setup, $secret from session is correct.

        $valid = $google2fa->verifyKey($secret, $request->input('one_time_password'));

        if ($valid) {
            $user->google_2fa_secret = Crypt::encryptString($secret);
            $user->google_2fa_enabled = true;
            $user->save();

            $request->session()->forget('google_2fa_secret_temp');

            return Redirect::route('profile.settings')->with('status', '2fa-enabled');
        } else {
            // Pass the secret back to the view so the same QR code can be shown
            $request->session()->flash('google_2fa_secret_temp', $secret);
            return Redirect::route('profile.connect-2fa')
                ->withInput()
                ->withErrors(['one_time_password' => __('profile.2fa.invalid_code')]);
        }
    }

    public function disable2fa(Request $request): RedirectResponse
    {
        $user = $request->user();
        // For security, user might need to confirm with password or current 2FA code
        // For simplicity, directly disabling here. Add confirmation if needed.

        $user->google_2fa_enabled = false;
        $user->google_2fa_secret = null; // Clear the secret
        $user->save();

        return Redirect::route('profile.settings')->with('status', 'authenticator-disabled');
    }

    public function personalGreeting(Request $request): View
    {
        $user = $request->user();
        return view('pages.profile.personal-greeting', [
            'user' => $user,
        ]);
    }

    public function updatePersonalGreeting(UpdatePersonalGreetingSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->personal_greeting = $request->input('personal_greeting');
        $user->save();
        return Redirect::route('profile.personal-greeting')->with('status', 'personal-greeting-updated');
    }

    public function ipRestriction(Request $request): View
    {
        $user = $request->user();
        return view('pages.profile.ip-restriction', [
            'user' => $user,
            'ip_restrictions' => $user->ip_restrictions ?? [],
        ]);
    }

    public function updateIpRestriction(Request $request): RedirectResponse
    {
        $request->validate([
            'ip_restrictions' => ['nullable', 'string'],
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Получаем текущий список IP
        $currentIps = $user->ip_restrictions ?? [];

        // Получаем новые IP из формы
        $newIps = array_filter(array_map('trim', explode("\n", $request->input('ip_restrictions', ''))));

        // Валидация каждого IP
        foreach ($newIps as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP) && !$this->isValidIpRange($ip)) {
                return back()->withErrors(['ip_restrictions' => __('validation.ip', ['attribute' => 'IP address'])]);
            }
        }

        // Объединяем и удаляем дубликаты
        $user->ip_restrictions = array_unique(array_merge($currentIps, $newIps));
        $user->save();

        return Redirect::route('profile.ip-restriction')->with('status', 'ip-restriction-updated');
    }

    private function isValidIpRange(string $ip): bool
    {
        // Проверка на CIDR формат (например, 192.168.1.0/24)
        if (preg_match('/^(\d{1,3}\.){3}\d{1,3}\/\d{1,2}$/', $ip)) {
            list($ip, $mask) = explode('/', $ip);
            return filter_var($ip, FILTER_VALIDATE_IP) && $mask >= 0 && $mask <= 32;
        }

        // Проверка на диапазон (например, 192.168.1.1-192.168.1.255)
        if (preg_match('/^(\d{1,3}\.){3}\d{1,3}-(\d{1,3}\.){3}\d{1,3}$/', $ip)) {
            list($start, $end) = explode('-', $ip);
            return filter_var($start, FILTER_VALIDATE_IP) && filter_var($end, FILTER_VALIDATE_IP);
        }

        return false;
    }

    public function initiatePasswordUpdate(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
            'confirmation_method' => 'required|in:authenticator,email'
        ]);

        $user = $request->user();
        $method = $request->input('confirmation_method');

        if ($method === 'authenticator' && !$user->google_2fa_enabled) {
            return back()->withErrors(['confirmation_method' => __('profile.security_settings.2fa_not_enabled')]);
        }

        if ($method === 'authenticator') {
            Cache::put('password_update_code:' . $user->id, [
                'password' => $request->input('password'),
                'method' => 'authenticator',
                'expires_at' => now()->addMinutes(15)
            ], now()->addMinutes(15));

            return redirect()->route('profile.change-password')
                ->with('status', 'authenticator-required');
        }

        $verificationCode = random_int(100000, 999999);
        Cache::put('password_update_code:' . $user->id, [
            'password' => $request->input('password'),
            'code' => $verificationCode,
            'method' => 'email',
            'expires_at' => now()->addMinutes(15)
        ], now()->addMinutes(15));

        Notification::route('mail', $user->email)
            ->notify(new PasswordUpdateConfirmationNotification($verificationCode));

        return redirect()->route('profile.change-password')
            ->with('status', 'password-code-sent');
    }

    public function confirmPasswordUpdate(Request $request): RedirectResponse
    {
        $request->validate([
            'verification_code' => 'required|string|digits:6'
        ]);

        $user = $request->user();
        $pendingUpdate = Cache::get('password_update_code:' . $user->id);

        if (!$pendingUpdate) {
            Log::warning('Password update confirmation failed: no pending update found', [
                'user_id' => $user->id
            ]);
            return back()->withErrors(['verification_code' => __('profile.security_settings.update_request_expired')]);
        }

        if (now()->isAfter($pendingUpdate['expires_at'])) {
            Log::warning('Password update confirmation failed: request expired', [
                'user_id' => $user->id,
                'expires_at' => $pendingUpdate['expires_at']
            ]);
            Cache::forget('password_update_code:' . $user->id);
            return back()->withErrors(['verification_code' => __('profile.security_settings.update_request_expired')]);
        }

        $isValid = false;
        if ($pendingUpdate['method'] === 'authenticator') {
            $secret = Crypt::decryptString($user->google_2fa_secret);
            $isValid = Google2FAFacade::verifyKey($secret, $request->input('verification_code'));
        } else {
            $isValid = $request->input('verification_code') === (string)$pendingUpdate['code'];
        }

        if (!$isValid) {
            Log::warning('Password update confirmation failed: invalid code', [
                'user_id' => $user->id,
                'method' => $pendingUpdate['method'],
                'provided_code' => $request->input('verification_code')
            ]);
            return back()->withErrors(['verification_code' => __('profile.security_settings.invalid_verification_code')]);
        }

        $user->password = Hash::make($pendingUpdate['password']);
        $user->save();

        Cache::forget('password_update_code:' . $user->id);

        Log::info('Password updated successfully', [
            'user_id' => $user->id
        ]);

        return redirect()->route('profile.settings')
            ->with('status', 'password-updated');
    }

    public function cancelPasswordUpdate(Request $request): RedirectResponse
    {
        $user = $request->user();
        $pendingUpdate = Cache::get('password_update_code:' . $user->id);

        if ($pendingUpdate) {
            Log::info('Password update cancelled by user', [
                'user_id' => $user->id
            ]);
            Cache::forget('password_update_code:' . $user->id);
        }

        return redirect()->route('profile.settings')
            ->with('status', 'password-update-cancelled');
    }
}
