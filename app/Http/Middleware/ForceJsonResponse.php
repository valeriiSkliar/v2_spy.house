<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Force Accept header to JSON for API routes
        $request->headers->set('Accept', 'application/json');

        try {
            $response = $next($request);

            // If this is a redirect response due to auth failure, convert to JSON error
            if ($response->getStatusCode() === 302 && $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated or session expired',
                ], 401);
            }

            return $response;
        } catch (AuthenticationException $e) {
            // Return JSON response for auth exceptions
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Unauthenticated',
            ], 401);
        }
    }
}
