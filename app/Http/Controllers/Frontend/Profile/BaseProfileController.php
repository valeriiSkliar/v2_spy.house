<?php

namespace App\Http\Controllers\Frontend\Profile;

use App\Http\Controllers\FrontendController;
use App\Enums\Frontend\UserExperience;
use App\Enums\Frontend\UserScopeOfActivity;
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

    protected function renderChangePasswordForm(): View
    {
        $pendingUpdate = Cache::get('password_update_code:' . $this->user->id);

        $confirmationMethod = $this->user->google_2fa_enabled ? 'authenticator' : 'email';
        return view('components.profile.change-password-form', [
            'user' => $this->user,
            'passwordUpdatePending' => $pendingUpdate ? true : false,
            'confirmationMethod' => $confirmationMethod
        ]);
    }
}
