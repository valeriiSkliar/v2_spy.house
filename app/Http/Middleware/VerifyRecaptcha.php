<?php

namespace App\Http\Middleware;

use App\Rules\Recaptcha;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class VerifyRecaptcha
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Пропускаем проверку для GET запросов
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        // Проверяем только если есть reCAPTCHA в запросе
        if ($request->has('g-recaptcha-response')) {
            $validator = Validator::make($request->all(), [
                'g-recaptcha-response' => ['required', new Recaptcha],
            ]);

            if ($validator->fails()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'reCAPTCHA verification failed',
                        'errors' => $validator->errors(),
                    ], 422);
                }

                return back()->withErrors($validator)->withInput();
            }
        }

        return $next($request);
    }
}
