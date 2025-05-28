<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Api\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\ValidationException;

class TokenController extends Controller
{
    /** @var TokenService */
    protected $tokenService;

    /**
     * Create a new controller instance.
     */
    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Create a new token for the authenticated user.
     */
    public function createToken(Request $request): JsonResponse
    {
        // Validate the request
        $request->validate([
            'token_name' => 'nullable|string|max:255',
            'token_type' => 'required|string|in:basic,advanced',
        ]);

        $user = $request->user();
        $tokenName = $request->input('token_name', 'api-token');
        $tokenType = $request->input('token_type');

        // Create the token based on the requested type
        if ($tokenType === 'basic') {
            $tokenData = $this->tokenService->createBasicToken($user);
        } else {
            $tokenData = $this->tokenService->createAdvancedToken($user, $tokenName);
        }

        // Store refresh token in HttpOnly cookie
        $response = response()->json([
            'access_token' => $tokenData['access_token'],
            'expires_at' => $tokenData['expires_at'],
            'token_type' => 'Bearer',
        ]);

        // Set refresh token in a HttpOnly cookie that lasts for the refresh token lifetime
        return $this->setRefreshTokenCookie($response, $tokenData['refresh_token']);
    }

    /**
     * Refresh the access token using a refresh token.
     *
     * @throws ValidationException
     */
    public function refreshToken(Request $request): JsonResponse
    {

        // Get refresh token from cookie
        $refreshToken = $request->cookie('refresh_token');

        // If no refresh token in cookie, check the request body (for clients that don't support cookies)
        if (! $refreshToken) {

            // Validate request when token is supplied via body
            $request->validate([
                'refresh_token' => 'sometimes|string',
            ]);

            // Check if request has token in body
            if ($request->has('refresh_token')) {
                $refreshToken = $request->input('refresh_token');
            } else {
                // Check if token is in JSON request body
                $jsonData = $request->json()->all();
                if (isset($jsonData['refresh_token'])) {
                    $refreshToken = $jsonData['refresh_token'];
                }
            }
        }

        // If no refresh token in cookie or request, generate a new one if user is authenticated
        if (! $refreshToken && Auth::check()) {
            $user = Auth::user();
            $tokenData = $this->tokenService->createBasicToken($user);

            return response()->json([
                'access_token' => $tokenData['access_token'],
                'expires_at' => $tokenData['expires_at'],
                'token_type' => 'Bearer',
                'message' => __('common.success.new_token_created'),
            ])->cookie(
                'refresh_token',
                $tokenData['refresh_token'],
                TokenService::REFRESH_TOKEN_EXPIRATION,
                '/',
                null,
                request()->secure(),
                true,
                false,
                'Strict'
            );
        }

        // If still no refresh token and not authenticated, return error
        if (! $refreshToken) {
            return response()->json([
                'message' => __('common.error.refresh_token_not_provided_and_user_not_authenticated'),
            ], 400);
        }

        $user = null;

        // First check if user is authenticated
        if (Auth::check()) {
            $user = $request->user();
        } else {

            $hashedToken = hash('sha256', $refreshToken);
            $refreshTokenRecord = \App\Models\RefreshToken::where('token', $hashedToken)
                ->where('expires_at', '>', now())
                ->first();

            if ($refreshTokenRecord) {
                $user = \App\Models\User::find($refreshTokenRecord->user_id);
            } else {
            }
        }

        // If still no user found, return authentication error
        if (! $user) {

            return response()->json([
                'message' => __('common.error.user_not_found_for_refresh_token'),
            ], 401);
        }

        // Attempt to refresh the token
        $tokenData = $this->tokenService->refreshToken($user, $refreshToken);

        if (! $tokenData) {
            throw ValidationException::withMessages([
                'refresh_token' => [__('common.error.refresh_token_invalid_or_expired')],
            ]);
        }

        // Check if client wants the refresh token in the response body
        // This is less secure but necessary for environments that don't support cookies properly
        $includeRefreshToken = $request->input('include_refresh_token') === 'true' ||
            $request->header('X-Include-Refresh-Token') === 'true';

        // Return new access token and set new refresh token in cookie
        $responseData = [
            'access_token' => $tokenData['access_token'],
            'expires_at' => $tokenData['expires_at'],
            'token_type' => 'Bearer',
        ];

        // Include refresh token in response body if requested
        // This allows clients that can't use cookies to still refresh tokens
        if ($includeRefreshToken) {
            $responseData['refresh_token'] = $tokenData['refresh_token'];
        }

        $response = response()->json($responseData);

        // Set new refresh token in a HttpOnly cookie
        return $this->setRefreshTokenCookie($response, $tokenData['refresh_token']);
    }

    /**
     * Revoke the current token or a specific token.
     */
    public function revokeToken(Request $request): JsonResponse
    {
        // Validate the request
        $request->validate([
            'token_id' => 'nullable|integer',
            'all' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $tokenId = $request->input('token_id');
        $revokeAll = $request->input('all', false);

        if ($revokeAll) {
            // Revoke all tokens for the user
            $count = $this->tokenService->revokeAllTokens($user);
            $message = $count . ' tokens revoked successfully';
        } elseif ($tokenId) {
            // Revoke the specified token
            $success = $this->tokenService->revokeToken($user, $tokenId);
            $message = $success ? __('common.success.current_token_revoked_successfully') : __('common.error.token_not_found');
        } else {
            // Revoke the current token
            $request->user()->currentAccessToken()->delete();
            $message = __('common.success.current_token_revoked_successfully');
        }

        // Clear refresh token cookie if all tokens are revoked or current token is revoked
        $response = response()->json(['message' => $message]);

        if ($revokeAll || ! $tokenId) {
            $response->cookie(Cookie::forget('refresh_token'));
        }

        return $response;
    }

    /**
     * Get all of the tokens for the authenticated user.
     */
    public function listTokens(Request $request): JsonResponse
    {
        $tokens = $this->tokenService->getUserTokens($request->user());

        return response()->json(['tokens' => $tokens]);
    }

    /**
     * Set the refresh token cookie on the response.
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
            'Lax'                  // sameSite - Changed to 'Lax' to allow cross-site requests in certain contexts
        );

        return $response->withCookie($cookie);
    }
}
