<?php

namespace App\Services\Api;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

class TokenService
{
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
     * Create a basic access token for a user
     */
    public function createBasicToken(User $user): string
    {
        return $user->createToken('basic-access', self::BASIC_ABILITIES)->plainTextToken;
    }

    /**
     * Create an advanced access token for a user
     */
    public function createAdvancedToken(User $user, string $name = 'advanced-access'): string
    {
        return $user->createToken($name, self::ADVANCED_ABILITIES)->plainTextToken;
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
            ->exists();
    }

    /**
     * Get user's tokens with abilities
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
            ];
        });
    }

    /**
     * Revoke a specific token
     */
    public function revokeToken(User $user, int $tokenId): bool
    {
        return $user->tokens()->where('id', $tokenId)->delete() > 0;
    }
}
