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
     *
     * @param TokenService $tokenService
     */
    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Create a new token for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
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
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function refreshToken(Request $request): JsonResponse
    {
        // Get refresh token from cookie
        $refreshToken = $request->cookie('refresh_token');

        // If no refresh token in cookie, check the request body (for clients that don't support cookies)
        if (!$refreshToken) {
            $request->validate([
                'refresh_token' => 'required|string',
            ]);
            $refreshToken = $request->input('refresh_token');
        }

        // If no refresh token in cookie or request, return error
        if (!$refreshToken) {
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
            // This allows token refresh even if session authentication expired
            $refreshTokenRecord = \App\Models\RefreshToken::where('token', $refreshToken)
                ->where('expires_at', '>', now())
                ->first();
                
            if ($refreshTokenRecord) {
                $user = \App\Models\User::find($refreshTokenRecord->user_id);
            }
        }
        
        // If still no user found, return authentication error
        if (!$user) {
            return response()->json([
                'message' => 'User not found for this refresh token',
            ], 401);
        }
        
        // Attempt to refresh the token
        $tokenData = $this->tokenService->refreshToken($user, $refreshToken);

        if (!$tokenData) {
            throw ValidationException::withMessages([
                'refresh_token' => ['The refresh token is invalid or expired.'],
            ]);
        }

        // Return new access token and set new refresh token in cookie
        $response = response()->json([
            'access_token' => $tokenData['access_token'],
            'expires_at' => $tokenData['expires_at'],
            'token_type' => 'Bearer',
        ]);

        // Set new refresh token in a HttpOnly cookie
        return $this->setRefreshTokenCookie($response, $tokenData['refresh_token']);
    }

    /**
     * Revoke the current token or a specific token.
     *
     * @param Request $request
     * @return JsonResponse
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
        } else if ($tokenId) {
            // Revoke the specified token
            $success = $this->tokenService->revokeToken($user, $tokenId);
            $message = $success ? 'Token revoked successfully' : 'Token not found';
        } else {
            // Revoke the current token
            $request->user()->currentAccessToken()->delete();
            $message = 'Current token revoked successfully';
        }

        // Clear refresh token cookie if all tokens are revoked or current token is revoked
        $response = response()->json(['message' => $message]);
        
        if ($revokeAll || !$tokenId) {
            $response->cookie(Cookie::forget('refresh_token'));
        }

        return $response;
    }

    /**
     * Get all of the tokens for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function listTokens(Request $request): JsonResponse
    {
        $tokens = $this->tokenService->getUserTokens($request->user());

        return response()->json(['tokens' => $tokens]);
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
        
        // Set cookie options
        $cookie = cookie(
            'refresh_token',       // name
            $refreshToken,         // value
            $minutes,              // minutes
            '/',                   // path
            null,                  // domain (null = current domain)
            true,                  // secure (HTTPS only)
            true,                  // httpOnly (not accessible via JavaScript)
            false,                 // raw
            'Strict'               // sameSite
        );

        return $response->withCookie($cookie);
    }
}