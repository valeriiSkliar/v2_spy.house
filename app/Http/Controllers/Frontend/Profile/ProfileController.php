<?php

namespace App\Http\Controllers\Frontend\Profile;

use App\Http\Controllers\FrontendController;
use App\Http\Requests\ProfileUpdateRequest;
use App\Services\Api\TokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

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

        // Get user scopes of activity (for dropdown)
        $scopes = [
            'Arbitrage (solo)',
            'Arbitrage (team)',
            'Affiliate marketing',
            'Media buying',
            'SEO',
            'Content marketing',
            'Other'
        ];
        $user = $request->user();

        // Get user's tokens
        $tokens = app(TokenService::class)->getUserTokens($user);
        return view($this->settingsView, [
            'user' => $request->user(),
            'scopes' => $scopes,
            'tokens' => $tokens,
            'api_token' => session('api_token'),

        ]);
    }

    public function changePassword(Request $request): View
    {
        return view('pages.profile.change-password');
    }
}
