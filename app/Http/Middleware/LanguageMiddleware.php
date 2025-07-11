<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class LanguageMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = null;
        $supportedLocales = config('languages', []); // Get supported languages, default to empty array if not set

        // Приоритет 1: Локаль из профиля авторизованного пользователя
        if (Auth::check() && Auth::user()->preferred_locale) {
            $userLocale = Auth::user()->preferred_locale;
            if (array_key_exists($userLocale, $supportedLocales)) {
                $locale = $userLocale;
                // Синхронизируем с сессией для консистентности
                if (Session::get('locale') !== $locale) {
                    Session::put('locale', $locale);
                }
            }
        }

        // Приоритет 2: Локаль из сессии (если не установлена из профиля)
        if (! $locale) {
            $sessionLocale = Session::get('locale');
            if ($sessionLocale && array_key_exists($sessionLocale, $supportedLocales)) {
                $locale = $sessionLocale;

                // Синхронизируем с профилем пользователя только если значения действительно отличаются
                // и пользователь авторизован
                if (Auth::check() && Auth::user()->preferred_locale !== $sessionLocale) {
                    try {
                        Auth::user()->update(['preferred_locale' => $sessionLocale]);
                    } catch (\Exception $e) {
                        // Логируем ошибку синхронизации, но не прерываем запрос
                        Log::warning('Failed to sync user preferred locale from session', [
                            'user_id' => Auth::id(),
                            'session_locale' => $sessionLocale,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        // Приоритет 3: Fallback локаль
        if (! $locale) {
            $locale = config('app.fallback_locale');
            Session::put('locale', $locale);

            // Обновляем профиль пользователя fallback локалью, если он авторизован и у него нет локали
            if (Auth::check() && ! Auth::user()->preferred_locale) {
                try {
                    Auth::user()->update(['preferred_locale' => $locale]);
                } catch (\Exception $e) {
                    Log::warning('Failed to set fallback locale for user', [
                        'user_id' => Auth::id(),
                        'fallback_locale' => $locale,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // Set the application locale for the current request
        App::setLocale($locale);

        return $next($request);
    }
}
