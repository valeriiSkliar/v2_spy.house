<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckTokenAbilities
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$abilities): Response
    {
        Log::info('CheckTokenAbilities middleware');
        Log::info($request->user());
        if (!$request->user() || !$request->user()->currentAccessToken()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        foreach ($abilities as $ability) {
            if (!$request->user()->tokenCan($ability)) {
                return response()->json(['message' => 'Unauthorized. Missing ability: ' . $ability], 403);
            }
        }

        return $next($request);
    }
}
