<?php

namespace App\Services\Api;

use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;

class TokenService
{
    /**
     * Access token expiration time in minutes.
     * Short-lived for security reasons.
     */
    const ACCESS_TOKEN_EXPIRATION = 60; // 1 hour

    /**
     * Refresh token expiration time in minutes.
     * Longer lifetime as it's stored in HttpOnly cookies.
     */
    const REFRESH_TOKEN_EXPIRATION = 10080; // 7 days

    /**
     * Token types and their abilities
     */
    const BASIC_ABILITIES = [
        'read:profile',
        'read:public',
        'read:base-token',
    ];
    const ADVANCED_ABILITIES = [
        'read:profile',
        'read:public',
        'write:profile',
        'read:services',
        'write:comments'
    ];
    const ADMIN_ABILITIES = ['*'];

    /**
     * Create a basic access token for a user with expiration
     * 
     * @param User $user The user to create the token for
     * @return array Containing the token string and refresh token
     */
    public function createBasicToken(User $user): array
    {
        return $this->createToken($user, 'basic-access', self::BASIC_ABILITIES);
    }

    /**
     * Create an advanced access token for a user with expiration
     * 
     * @param User $user The user to create the token for
     * @param string $name Token name
     * @return array Containing the token string and refresh token
     */
    public function createAdvancedToken(User $user, string $name = 'advanced-access'): array
    {
        return $this->createToken($user, $name, self::ADVANCED_ABILITIES);
    }

    /**
     * Create a token with expiration and refresh token
     * 
     * @param User $user The user to create the token for
     * @param string $name Token name
     * @param array $abilities Token abilities
     * @return array Containing the token string, refresh token, and expiration time
     */
    protected function createToken(User $user, string $name, array $abilities): array
    {
        // Create the access token with expiration
        $expiration = now()->addMinutes(self::ACCESS_TOKEN_EXPIRATION);
        $token = $user->createToken($name, $abilities, $expiration);
        
        // Generate refresh token
        $refreshToken = $this->generateRefreshToken($user, $token->accessToken->id);
        
        return [
            'access_token' => $token->plainTextToken,
            'refresh_token' => $refreshToken,
            'expires_at' => $expiration->timestamp
        ];
    }

    /**
     * Generate a refresh token and store it in the database
     * 
     * @param User $user The user to create the refresh token for
     * @param int $tokenId The ID of the related access token
     * @return string The refresh token string
     */
    protected function generateRefreshToken(User $user, int $tokenId): string
    {
        // Generate a unique token string
        $refreshToken = hash('sha256', Str::random(60));
        
        // Store the refresh token with its association to the access token
        $user->refreshTokens()->create([
            'token' => $refreshToken,
            'access_token_id' => $tokenId,
            'expires_at' => now()->addMinutes(self::REFRESH_TOKEN_EXPIRATION),
        ]);
        
        return $refreshToken;
    }

    /**
     * Refresh an access token using a refresh token
     * 
     * @param User $user The user to refresh the token for
     * @param string $refreshToken The refresh token string
     * @return array|null The new tokens or null if refresh token is invalid
     */
    public function refreshToken(User $user, string $refreshToken): ?array
    {
        // Hash the token for database comparison
        $hashedToken = hash('sha256', $refreshToken);

        // Find the refresh token in the database
        $tokenRecord = $user->refreshTokens()
            ->where('token', $hashedToken)
            ->first();

        if (!$tokenRecord) {
            // Only log user ID without any token information
            \Illuminate\Support\Facades\Log::warning('Token service: No matching refresh token found for user', [
                'user_id' => $user->id,
                'refresh_token_count' => $user->refreshTokens()->count()
            ]);
            return null;
        }

        // Check if token is expired
        if ($tokenRecord->expires_at < now()) {
            \Illuminate\Support\Facades\Log::warning('Token service: Refresh token expired', [
                'user_id' => $user->id,
                'expired_at' => $tokenRecord->expires_at->format('Y-m-d H:i:s'),
                'now' => now()->format('Y-m-d H:i:s')
            ]);

            // Delete expired token
            $tokenRecord->delete();
            return null;
        }
        
        // Get the original access token to copy its name and abilities
        $originalToken = PersonalAccessToken::find($tokenRecord->access_token_id);
        if (!$originalToken) {
            // If original token is gone, delete the refresh token and return null
            $tokenRecord->delete();
            return null;
        }
        
        // Revoke the old token
        $originalToken->delete();
        
        // Delete the used refresh token
        $tokenRecord->delete();
        
        // Create a new token pair with the same abilities
        return $this->createToken($user, $originalToken->name, $originalToken->abilities);
    }

    /**
     * Check if user has a basic token
     */
    public function hasBasicToken(User $user): bool
    {
        return $user->tokens()
            ->where('name', 'basic-access')
            ->whereJsonContains('abilities', 'read:profile')
            ->whereJsonContains('abilities', 'read:public')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    /**
     * Get user's tokens with abilities and expiration information
     */
    public function getUserTokens(User $user)
    {
        return $user->tokens->map(function ($token) {
            return [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at,
                'created_at' => $token->created_at,
                'expires_at' => $token->expires_at,
                'is_expired' => $token->expires_at ? now()->isAfter($token->expires_at) : false,
            ];
        });
    }

    /**
     * Revoke a specific token and its refresh tokens
     */
    public function revokeToken(User $user, int $tokenId): bool
    {
        // Delete any associated refresh tokens
        $user->refreshTokens()->where('access_token_id', $tokenId)->delete();
        
        // Delete the access token itself
        return $user->tokens()->where('id', $tokenId)->delete() > 0;
    }
    
    /**
     * Revoke all tokens for a user
     */
    public function revokeAllTokens(User $user): int
    {
        // Delete all refresh tokens for this user
        $user->refreshTokens()->delete();
        
        // Delete all access tokens and return the count
        return $user->tokens()->delete();
    }
}
