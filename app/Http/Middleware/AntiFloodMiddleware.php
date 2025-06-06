<?php

namespace App\Http\Middleware;

use App\Services\Common\AntiFloodService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AntiFloodMiddleware
{
    /**
     * The AntiFloodService instance.
     */
    protected AntiFloodService $antiFloodService;

    /**
     * Create a new middleware instance.
     */
    public function __construct(AntiFloodService $antiFloodService)
    {
        $this->antiFloodService = $antiFloodService;
    }

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $action = 'default', ?int $limit = null, ?int $window = null)
    {
        $userId = Auth::id() ?? $request->ip();

        if (! $this->antiFloodService->check($userId, $action, $limit, $window)) {
            $currentUsage = $this->antiFloodService->getRecord($userId, $action) ?? 0;
            $actualLimit = $limit ?? $this->antiFloodService->defaultLimit;
            $actualWindow = $window ?? $this->antiFloodService->defaultWindow;

            $headers = [
                'X-RateLimit-Limit' => $actualLimit,
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => now()->addSeconds($actualWindow)->getTimestamp(),
                'Retry-After' => $actualWindow,
            ];

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Too Many Requests',
                    'error' => 'Rate limit exceeded. Please try again later.',
                    'limit' => $actualLimit,
                    'current_usage' => $currentUsage,
                ], 429, $headers);
            }

            return response('Too Many Requests. Please try again later.', 429, $headers);
        }

        $response = $next($request);

        // Add rate limit headers to the response
        $currentUsage = $this->antiFloodService->getRecord($userId, $action) ?? 0;
        $actualLimit = $limit ?? $this->antiFloodService->defaultLimit;
        $remaining = max(0, $actualLimit - $currentUsage);

        $response->headers->set('X-RateLimit-Limit', $actualLimit);
        $response->headers->set('X-RateLimit-Remaining', $remaining);

        return $response;
    }
}
