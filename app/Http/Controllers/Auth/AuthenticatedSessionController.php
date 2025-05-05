<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $user = Auth::user();


        // Check if user has a basic access token
        $basicToken = $user->tokens()
            ->where('name', 'basic-access')
            ->whereJsonContains('abilities', 'read:profile')
            ->whereJsonContains('abilities', 'read:public')
            ->first();

        if (!$basicToken) {
            // Create a basic API token if it doesn't exist
            $token = $user->createToken('basic-access', ['read:profile', 'read:public', 'read:base-token'])->plainTextToken;

            session()->flash('api_token', $token);
        }

        return redirect()->intended(route('profile.settings', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
