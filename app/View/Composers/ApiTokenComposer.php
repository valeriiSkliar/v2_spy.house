<?php

namespace App\View\Composers;

use App\Services\Api\TokenService;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class ApiTokenComposer
{
    /**
     * The token service instance.
     *
     * @var TokenService
     */
    protected $tokenService;

    /**
     * Create a new API token composer.
     *
     * @param TokenService $tokenService
     * @return void
     */
    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Bind data to the view.
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // First check if token is in session (just created during login)
            $apiToken = session('api_token');
            $expiresAt = session('api_token_expires_at');
            
            // If not in session, check if user has a basic token or create one
            if (!$apiToken || !$expiresAt) {
                // Create a new token
                $tokenData = $this->tokenService->createBasicToken($user);
                $apiToken = $tokenData['access_token'];
                $expiresAt = $tokenData['expires_at'];
                
                // Store token in session for future requests
                session(['api_token' => $apiToken]);
                session(['api_token_expires_at' => $expiresAt]);
                
                // Store refresh token in HttpOnly cookie
                if (isset($tokenData['refresh_token'])) {
                    $cookie = cookie(
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
                    
                    // Add cookie to response using the global cookie() helper
                    Cookie::queue($cookie);
                }
                
                // Log token creation for debugging
                \Illuminate\Support\Facades\Log::info('Created new API token for user', [
                    'user_id' => $user->id,
                    'token_expires_at' => $expiresAt,
                ]);
            }
            
            // Share token and expiration with view
            $view->with('api_token', $apiToken);
            $view->with('api_token_expires_at', $expiresAt);
        }
    }
}