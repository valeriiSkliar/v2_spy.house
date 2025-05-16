<?php

namespace App\Http\Controllers;

use function App\Helpers\sanitize_input;


class FrontendController extends Controller
{
    protected function sanitizeInput(string $input): string
    {
        if (!$input) {
            return '';
        }
        $input = trim($input);

        return sanitize_input($input);
    }

    protected function jsonResponse($data, $status = 200)
    {
        $response = response()->json($data, $status);
        $response->header('Content-Type', 'application/json');
        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('X-Frame-Options', 'DENY');
        $response->header('X-XSS-Protection', '1; mode=block');
        $response->header('X-Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self' https://*.google.com; frame-src 'none';");
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->header('Permissions-Policy', 'geolocation=(self)');
        $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        return $response;
    }
}
