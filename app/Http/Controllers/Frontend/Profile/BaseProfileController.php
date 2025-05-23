<?php

namespace App\Http\Controllers\Frontend\Profile;

use App\Enums\Frontend\UserExperience;
use App\Enums\Frontend\UserScopeOfActivity;
use App\Http\Controllers\FrontendController;
use App\Services\Api\TokenService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class BaseProfileController extends FrontendController
{
    protected $user;

    protected $settingsView = 'pages.profile.settings';

    public function __construct()
    {
        $this->user = Auth::user();
    }

    public function getUser()
    {
        return $this->user;
    }

    protected function renderSettingsView($request, $activeTab = 'personal'): View
    {
        $experiences = UserExperience::getTranslatedList();
        $scopes = UserScopeOfActivity::getTranslatedList();

        // Get user's tokens
        $tokens = app(TokenService::class)->getUserTokens($this->user);

        return view($this->settingsView, [
            'user' => $this->user,
            'scopes' => $scopes,
            'tokens' => $tokens,
            'api_token' => session('api_token'),
            'experiences' => $experiences,
            'activeTab' => $activeTab,
            'tab' => $activeTab,
            'displayDefaultValues' => [
                'experience' => __('profile.select_default_value'),
                'scope_of_activity' => __('profile.select_default_value'),
            ],
        ]);
    }

    protected function renderTabContent($activeTab = 'personal'): View
    {
        $experiences = UserExperience::getTranslatedList();
        $scopes = UserScopeOfActivity::getTranslatedList();

        return view('components.profile.tab-content', [
            'user' => $this->user,
            'scopes' => $scopes,
            'api_token' => session('api_token'),
            'experiences' => $experiences,
            'google_2fa_enabled' => $this->user->google_2fa_enabled,
            'active-tab' => $activeTab,
            'display-default-values' => [
                'experience' => __('profile.select_default_value'),
                'scope_of_activity' => __('profile.select_default_value'),
            ],
        ]);
    }

    protected function renderChangePasswordForm($confirmationMethod = null): View
    {
        $pendingUpdate = Cache::get('password_update_code:'.$this->user->id);

        if (! $confirmationMethod) {
            $confirmationMethod = $this->user->google_2fa_enabled ? 'authenticator' : 'email';
        }

        return view('components.profile.change-password-form', [
            'user' => $this->user,
            'passwordUpdatePending' => $pendingUpdate ? true : false,
            'confirmationMethod' => $confirmationMethod,
        ]);
    }

    protected function renderChangeEmailForm($confirmationMethod = null): View
    {
        $pendingUpdate = Cache::get('email_update_code:'.$this->user->id);

        if (! $confirmationMethod) {
            $confirmationMethod = $this->user->google_2fa_enabled ? 'authenticator' : 'email';
        }

        return view('components.profile.change-email-form', [
            'user' => $this->user,
            'emailUpdatePending' => $pendingUpdate ? true : false,
            'confirmationMethod' => $confirmationMethod,
            'authenticatorEnabled' => $this->user->google_2fa_enabled,
        ]);
    }

    protected function renderPersonalGreetingForm($confirmationMethod = null, $step = 'initiation'): View
    {
        $pendingUpdate = Cache::get('personal_greeting_update_code:'.$this->user->id);

        if (! $confirmationMethod) {
            $confirmationMethod = $this->user->google_2fa_enabled ? 'authenticator' : 'email';
        }

        if ($step === 'confirmation' || ($pendingUpdate && isset($pendingUpdate['status']) && $pendingUpdate['status'] === 'pending')) {
            return view('components.profile.personal-greeting-confirmation-form', [
                'user' => $this->user,
                'personalGreetingUpdatePending' => true,
                'confirmationMethod' => $pendingUpdate['method'] ?? $confirmationMethod,
                'authenticatorEnabled' => $this->user->google_2fa_enabled,
            ]);
        }

        return view('components.profile.personal-greeting-form', [
            'user' => $this->user,
            'personalGreetingUpdatePending' => false,
            'confirmationMethod' => $confirmationMethod,
            'authenticatorEnabled' => $this->user->google_2fa_enabled,
        ]);
    }

    protected function renderIpRestrictionForm(): View
    {
        return view('components.profile.ip-restriction-form', [
            'user' => $this->user,
            'ip_restrictions' => $this->user->ip_restrictions ?? [],
        ]);
    }

    /**
     * Render notification settings form
     */
    protected function renderNotificationsForm(): View
    {
        return view('components.profile.notifications-tab', [
            'user' => $this->user,
        ]);
    }
}
