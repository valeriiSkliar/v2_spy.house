<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Api\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login user and create token with refresh token
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Use TokenService to create a token pair with refresh token
        $tokenService = app(\App\Services\Api\TokenService::class);
        $tokenData = $tokenService->createAdvancedToken($user, $request->device_name);

        // Check if client wants the refresh token in the response body
        $includeRefreshToken = $request->input('include_refresh_token') === 'true' ||
            $request->header('X-Include-Refresh-Token') === 'true';

        $responseData = [
            'access_token' => $tokenData['access_token'],
            'expires_at' => $tokenData['expires_at'],
            'token_type' => 'Bearer',
            'user' => $user,
        ];

        // Include refresh token in response body if requested
        if ($includeRefreshToken) {
            $responseData['refresh_token'] = $tokenData['refresh_token'];
        }

        $response = response()->json($responseData);

        // Set refresh token in a HttpOnly cookie
        return $this->setRefreshTokenCookie($response, $tokenData['refresh_token']);
    }

    /**
     * Logout user (revoke the token and its refresh token)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if ($token) {
            /** @var \Laravel\Sanctum\PersonalAccessToken $token */

            // Delete associated refresh tokens
            $request->user()->refreshTokens()->where('access_token_id', $token->id)->delete();

            // Delete the access token
            $token->delete();
        }

        // Clear refresh token cookie
        $response = response()->json(['message' => 'Successfully logged out']);
        return $response->withCookie(cookie()->forget('refresh_token'));
    }

    /**
     * Get the authenticated user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Refresh the access token using a refresh token.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function refreshToken(Request $request): JsonResponse
    {
        // Log request information for debugging
        \Illuminate\Support\Facades\Log::debug('Token refresh request received', [
            'has_cookie' => $request->hasCookie('refresh_token'),
            'has_body_param' => $request->has('refresh_token'),
            'has_json_body' => $request->isJson(),
            'content_type' => $request->header('Content-Type'),
            'request_ip' => $request->ip(),
            'request_method' => $request->method(),
            'user_agent' => $request->userAgent()
        ]);

        // Get refresh token from cookie
        $refreshToken = $request->cookie('refresh_token');

        // Log cookie info (careful not to log full token)
        if ($refreshToken) {
            \Illuminate\Support\Facades\Log::debug('Found refresh token in cookie', [
                'token_prefix' => substr($refreshToken, 0, 5) . '...',
                'token_length' => strlen($refreshToken)
            ]);
        } else {
            \Illuminate\Support\Facades\Log::debug('No refresh token in cookie, checking request body');
        }

        // If no refresh token in cookie, check the request body (for clients that don't support cookies)
        if (!$refreshToken) {
            // Validate request when token is supplied via body
            $request->validate([
                'refresh_token' => 'sometimes|string'
            ]);

            // Check if request has token in body
            if ($request->has('refresh_token')) {
                $refreshToken = $request->input('refresh_token');
                \Illuminate\Support\Facades\Log::debug('Found refresh token in request body parameter', [
                    'token_prefix' => $refreshToken ? substr($refreshToken, 0, 5) . '...' : 'null',
                    'token_length' => $refreshToken ? strlen($refreshToken) : 0
                ]);
            } else {
                // Check if token is in JSON request body
                $jsonData = $request->json()->all();
                if (isset($jsonData['refresh_token'])) {
                    $refreshToken = $jsonData['refresh_token'];
                    \Illuminate\Support\Facades\Log::debug('Found refresh token in JSON body', [
                        'token_prefix' => $refreshToken ? substr($refreshToken, 0, 5) . '...' : 'null',
                        'token_length' => $refreshToken ? strlen($refreshToken) : 0,
                        'json_keys' => array_keys($jsonData)
                    ]);
                }
            }
        }

        // If no refresh token found, return error
        if (!$refreshToken) {
            \Illuminate\Support\Facades\Log::warning('No refresh token found in request');
            return response()->json([
                'message' => 'Refresh token not provided',
            ], 400);
        }

        $user = null;

        // First check if user is authenticated
        if (Auth::check()) {
            $user = $request->user();
        } else {
            // Try to find user by refresh token
            $hashedToken = hash('sha256', $refreshToken);
            $refreshTokenRecord = \App\Models\RefreshToken::where('token', $hashedToken)
                ->where('expires_at', '>', now())
                ->first();

            if ($refreshTokenRecord) {
                $user = User::find($refreshTokenRecord->user_id);
            }
        }

        // If no user found, return error
        if (!$user) {
            return response()->json([
                'message' => 'User not found for this refresh token',
            ], 401);
        }

        // Attempt to refresh the token
        $tokenService = app(TokenService::class);
        $tokenData = $tokenService->refreshToken($user, $refreshToken);

        if (!$tokenData) {
            throw ValidationException::withMessages([
                'refresh_token' => ['The refresh token is invalid or expired.'],
            ]);
        }

        // Check if client wants the refresh token in the response body
        $includeRefreshToken = $request->input('include_refresh_token') === 'true' ||
            $request->header('X-Include-Refresh-Token') === 'true';

        // Return new access token and set new refresh token in cookie
        $responseData = [
            'access_token' => $tokenData['access_token'],
            'expires_at' => $tokenData['expires_at'],
            'token_type' => 'Bearer',
        ];

        // Include refresh token in response body if requested
        if ($includeRefreshToken) {
            $responseData['refresh_token'] = $tokenData['refresh_token'];
        }

        $response = response()->json($responseData);

        // Set new refresh token in a HttpOnly cookie
        return $this->setRefreshTokenCookie($response, $tokenData['refresh_token']);
    }

    /**
     * Set the refresh token cookie on the response.
     *
     * @param JsonResponse $response
     * @param string $refreshToken
     * @return JsonResponse
     */
    protected function setRefreshTokenCookie(JsonResponse $response, string $refreshToken): JsonResponse
    {
        // Calculate minutes from now to the TokenService::REFRESH_TOKEN_EXPIRATION
        $minutes = TokenService::REFRESH_TOKEN_EXPIRATION;

        // Set cookie options - Note: Using Lax for SameSite to enable cross-origin requests
        // while still providing some CSRF protection
        $cookie = cookie(
            'refresh_token',       // name
            $refreshToken,         // value
            $minutes,              // minutes
            '/',                   // path
            null,                  // domain (null = current domain)
            request()->secure(),   // secure (based on current request protocol)
            true,                  // httpOnly (not accessible via JavaScript)
            false,                 // raw
            'Lax'                  // sameSite - 'Lax' to allow cross-site requests in certain contexts
        );

        return $response->withCookie($cookie);
    }
}
