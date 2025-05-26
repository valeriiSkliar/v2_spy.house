<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PreLogin2FACheckRequest;
use App\Services\Api\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
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

        if (! $hasValidToken) {
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
     * Handle an incoming authentication request via AJAX.
     */
    public function storeAjax(LoginRequest $request): JsonResponse
    {
        Log::debug('AJAX login attempt started', [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'has_2fa_code' => $request->has('code'),
            'code_length' => $request->input('code') ? strlen($request->input('code')) : 0,
            'remember_me' => $request->boolean('remember'),
        ]);

        try {
            $request->authenticate();
            Log::debug('Authentication successful');

            $request->session()->regenerate();
            $user = Auth::user();

            Log::info('User authenticated successfully via AJAX', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

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

            Log::debug('Token check completed', [
                'user_id' => $user->id,
                'has_valid_token' => $hasValidToken,
                'total_tokens_count' => $user->tokens()->count(),
            ]);

            $tokenData = null;
            if (!$hasValidToken) {
                Log::debug('Creating new tokens for user', ['user_id' => $user->id]);

                // Get the token service to create a basic token with refresh token
                $tokenService = app(TokenService::class);
                $tokenData = $tokenService->createBasicToken($user);

                // Store the access token in the session for JavaScript to use
                session(['api_token' => $tokenData['access_token']]);
                session(['api_token_expires_at' => $tokenData['expires_at']]);

                Log::debug('New tokens created and stored in session', [
                    'user_id' => $user->id,
                    'access_token_length' => strlen($tokenData['access_token']),
                    'refresh_token_length' => strlen($tokenData['refresh_token']),
                    'expires_at' => $tokenData['expires_at'],
                ]);
            } else {
                Log::debug('Using existing valid tokens', ['user_id' => $user->id]);
            }

            $redirectUrl = route('profile.settings', absolute: false);
            Log::debug('Preparing response', [
                'user_id' => $user->id,
                'redirect_url' => $redirectUrl,
                'will_set_cookie' => $tokenData !== null,
            ]);

            $response = response()->json([
                'success' => true,
                'message' => __('auth.login_successful'),
                'redirect' => $redirectUrl,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                ],
                'csrf_token' => csrf_token(),
            ]);

            // Add refresh token cookie if we created new tokens
            if ($tokenData) {
                $cookie = cookie(
                    'refresh_token',
                    $tokenData['refresh_token'],
                    \App\Services\Api\TokenService::REFRESH_TOKEN_EXPIRATION,
                    '/',
                    null,
                    request()->secure(),
                    true,
                    false,
                    'Strict'
                );
                $response = $response->cookie($cookie);
                Log::debug('Refresh token cookie attached to response', ['user_id' => $user->id]);
            }

            Log::info('AJAX login completed successfully', [
                'user_id' => $user->id,
                'redirect_url' => $redirectUrl,
            ]);

            return $response;
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('AJAX login validation failed', [
                'email' => $request->input('email'),
                'ip' => $request->ip(),
                'errors' => $e->errors(),
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('auth.failed'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('AJAX login error', [
                'email' => $request->input('email'),
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('auth.login_error'),
            ], 500);
        }
    }

    public function preLogin2FACheck(PreLogin2FACheckRequest $request): JsonResponse
    {
        $user = \App\Models\User::where('email', $request->email)->first();

        // Always return success to prevent user enumeration attacks
        // Only reveal 2FA form if the user exists and has 2FA enabled
        $has2fa = false;
        $buttonText = __('Log In');

        if ($user && $request->password && Hash::check($request->password, $user->password)) {
            $has2fa = $user->google_2fa_enabled;
            $buttonText = $has2fa ? __('Confirm') : __('Log In');
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'has_2fa' => $has2fa,
                'html' => $has2fa ? view('components.auth.login-2fa-confirmation', [
                    'error' => '',
                    'message' => '',
                ])->render() : '',
                'button_text' => $buttonText,
            ],
        ]);
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
