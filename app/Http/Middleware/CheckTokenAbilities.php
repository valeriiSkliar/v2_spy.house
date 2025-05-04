<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenAbilities
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$abilities): Response
    {
        Log::info('--- CheckTokenAbilities Start ---');
        $user = $request->user();

        Log::info('CheckTokenAbilities: User: ' . json_encode($user));
        Log::info('CheckTokenAbilities: Request: ' . json_encode($request->headers->all()));

        if (!$user) {
            Log::warning('CheckTokenAbilities: User not authenticated.');
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = $user->currentAccessToken();

        if (!$token instanceof PersonalAccessToken) {
            Log::warning('CheckTokenAbilities: Authentication via session/cookie (TransientToken) or no token. User ID: ' . $user->id . '. This route requires API token authentication.');
            return response()->json(['message' => 'API token authentication required.'], 401);
        }

        Log::info('CheckTokenAbilities: User ID: ' . $user->id);
        Log::info('CheckTokenAbilities: Token ID: ' . $token->id);
        Log::info('CheckTokenAbilities: Token Name: ' . $token->name);
        Log::info('CheckTokenAbilities: Token Abilities: ' . json_encode($token->abilities));
        Log::info('CheckTokenAbilities: Required Abilities: ' . json_encode($abilities));

        foreach ($abilities as $ability) {
            if (!$user->tokenCan($ability)) {
                Log::warning('CheckTokenAbilities: Unauthorized. User ID: ' . $user->id . ' Token ID: ' . $token->id . ' missing ability: ' . $ability);
                return response()->json(['message' => 'Unauthorized. Missing ability: ' . $ability], 403);
            }
            Log::info('CheckTokenAbilities: Ability check passed for: ' . $ability);
        }

        Log::info('--- CheckTokenAbilities Passed ---');
        return $next($request);
    }
}
