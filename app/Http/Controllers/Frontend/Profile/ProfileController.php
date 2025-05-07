<?php

namespace App\Http\Controllers\Frontend\Profile;

use App\Enums\Frontend\UserExperience;
use App\Enums\Frontend\UserScopeOfActivity;
use App\Http\Controllers\FrontendController;
use App\Http\Requests\Profile\ProfileUpdateRequest;
use App\Http\Requests\Profile\ProfileSettingsUpdateRequest;
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
        return view('pages.profile.change-password');
    }

    public function changeEmail(Request $request): View
    {
        return view('pages.profile.change-password');
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
                ->withErrors(['one_time_password' => __('2FA secret not found. Please try setting up again.')]);
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
                ->withErrors(['one_time_password' => __('Invalid 2FA code. Please try again.')]);
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

        return Redirect::route('profile.settings')->with('status', '2fa-disabled');
    }
}
