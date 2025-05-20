<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Api\TokenService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('pages.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $user = Auth::user();

        // Check if user has a valid basic access token
        $hasValidToken = $user->tokens()
            ->where('name', 'basic-access')
            ->whereJsonContains('abilities', 'read:profile')
            ->whereJsonContains('abilities', 'read:public')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();

        if (!$hasValidToken) {
            // Get the token service to create a basic token with refresh token
            $tokenService = app(TokenService::class);
            $tokenData = $tokenService->createBasicToken($user);

            // Store the access token in the session for JavaScript to use (not as flash)
            session(['api_token' => $tokenData['access_token']]);

            // Add expires_at to session to allow JavaScript to know when token expires
            session(['api_token_expires_at' => $tokenData['expires_at']]);

            // Store the refresh token in an HttpOnly cookie
            $cookie = cookie(
                'refresh_token',                  // name
                $tokenData['refresh_token'],      // value
                \App\Services\Api\TokenService::REFRESH_TOKEN_EXPIRATION, // minutes
                '/',                              // path
                null,                             // domain
                request()->secure(),              // secure (HTTPS only)
                true,                             // httpOnly
                false,                            // raw
                'Strict'                          // sameSite
            );

            // Add the cookie to the response
            return redirect()->intended(route('profile.settings', absolute: false))->cookie($cookie);
        }

        return redirect()->intended(route('profile.settings', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Get the user to revoke their tokens before logging out
        $user = $request->user();
        if ($user) {
            // Optionally, revoke all tokens for the user
            app(TokenService::class)->revokeAllTokens($user);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Clear the refresh token cookie
        $cookie = Cookie::forget('refresh_token');

        return redirect('/')->cookie($cookie);
    }
}
