<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    public function switch(Request $request, $locale)
    {
        $supportedLocales = config('languages', []);

        if (array_key_exists($locale, $supportedLocales)) {
            // Устанавливаем локаль в сессию
            Session::put('locale', $locale);

            // Если пользователь авторизован - сразу обновляем его профиль
            // Это предотвращает конфликт с LanguageMiddleware, который приоритизирует профиль над сессией
            if (Auth::check()) {
                try {
                    Auth::user()->update(['preferred_locale' => $locale]);
                } catch (\Exception $e) {
                    // Логируем ошибку, но не прерываем процесс
                    Log::warning('Failed to update user preferred locale', [
                        'user_id' => Auth::id(),
                        'locale' => $locale,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        } else {
            Session::flash('error', __('common.error.invalid_language_selected'));
        }

        return redirect()->back();
    }
}
