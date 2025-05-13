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
        \Illuminate\Support\Facades\Log::info('Token refresh attempt', [
            'has_cookies' => $request->hasCookie('refresh_token'),
            'cookie_names' => array_keys($request->cookies->all()),
            'has_auth' => Auth::check(),
            'content_type' => $request->header('Content-Type'),
            'user_id' => Auth::id()
        ]);

        // Get refresh token from cookie
        $refreshToken = $request->cookie('refresh_token');
        \Illuminate\Support\Facades\Log::info('Refresh token from cookie', [
            'has_token' => !empty($refreshToken)
        ]);

        // If no refresh token in cookie, check the request body (for clients that don't support cookies)
        if (!$refreshToken) {
            \Illuminate\Support\Facades\Log::info('No refresh token in cookie, checking request body');
            
            // Validate request when token is supplied via body
            $request->validate([
                'refresh_token' => 'sometimes|string'
            ]);
            
            // Check if request has token in body
            if ($request->has('refresh_token')) {
                $refreshToken = $request->input('refresh_token');
                \Illuminate\Support\Facades\Log::info('Found refresh token in request body', [
                    'token_length' => strlen($refreshToken)
                ]);
            } else {
                // Check if token is in JSON request body
                $jsonData = $request->json()->all();
                if (isset($jsonData['refresh_token'])) {
                    $refreshToken = $jsonData['refresh_token'];
                    \Illuminate\Support\Facades\Log::info('Found refresh token in JSON request body', [
                        'token_length' => strlen($refreshToken)
                    ]);
                } else {
                    \Illuminate\Support\Facades\Log::warning('No refresh token in request body either');
                }
            }
        }

        // If no refresh token in cookie or request, generate a new one if user is authenticated
        if (!$refreshToken && Auth::check()) {
            \Illuminate\Support\Facades\Log::info('No refresh token, but user is authenticated. Creating new tokens.');
            $user = Auth::user();
            $tokenData = $this->tokenService->createBasicToken($user);
            
            return response()->json([
                'access_token' => $tokenData['access_token'],
                'expires_at' => $tokenData['expires_at'],
                'token_type' => 'Bearer',
                'message' => 'New token created'
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
        if (!$refreshToken) {
            \Illuminate\Support\Facades\Log::warning('No refresh token provided and user not authenticated');
            return response()->json([
                'message' => 'Refresh token not provided and user not authenticated',
            ], 400);
        }

        $user = null;

        // First check if user is authenticated
        if (Auth::check()) {
            $user = $request->user();
            \Illuminate\Support\Facades\Log::info('User authenticated via session', [
                'user_id' => $user->id
            ]);
        } else {
            // Try to find user by refresh token
            // This allows token refresh even if session authentication expired
            \Illuminate\Support\Facades\Log::info('Looking up user by refresh token hash');
            
            // Directly hash the token for comparison
            $hashedToken = hash('sha256', $refreshToken);
            $refreshTokenRecord = \App\Models\RefreshToken::where('token', $hashedToken)
                ->where('expires_at', '>', now())
                ->first();
                
            if ($refreshTokenRecord) {
                $user = \App\Models\User::find($refreshTokenRecord->user_id);
                \Illuminate\Support\Facades\Log::info('Found user by refresh token', [
                    'user_id' => $user->id,
                    'token_id' => $refreshTokenRecord->id
                ]);
            } else {
                \Illuminate\Support\Facades\Log::warning('No valid refresh token record found');
                \Illuminate\Support\Facades\Log::debug('Tried to find token with hash', [
                    'token_length' => strlen($refreshToken),
                    'token_prefix' => substr($refreshToken, 0, 10) . '...'
                ]);
            }
        }
        
        // If still no user found, return authentication error
        if (!$user) {
            \Illuminate\Support\Facades\Log::warning('No user found for this refresh token');
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
            \Illuminate\Support\Facades\Log::info('Including refresh token in response body for client fallback');
        }
        
        $response = response()->json($responseData);

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