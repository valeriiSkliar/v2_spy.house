<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = Session::get('locale');
        $supportedLocales = config('languages', []); // Get supported languages, default to empty array if not set

        // Validate the locale from session against supported languages
        if (!$locale || !array_key_exists($locale, $supportedLocales)) {
            $locale = config('app.fallback_locale');
            // Optionally update session with fallback if locale was invalid or missing
            // Session::put('locale', $locale); 
        }

        // Set the application locale for the current request
        App::setLocale($locale);

        return $next($request);
    }
}
