<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

class Recaptcha implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Проверяем, включена ли reCAPTCHA
        if (!config('captcha.enabled', true)) {
            return; // Пропускаем валидацию если reCAPTCHA отключена
        }

        // Если в режиме разработки и установлена переменная для отключения
        if (app()->environment('local') && !config('captcha.enabled_in_local', true)) {
            return;
        }

        if (empty($value)) {
            $fail('Пожалуйста, подтвердите, что вы не робот.');
            return;
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('captcha.secret'),
            'response' => $value,
            'remoteip' => request()->ip(),
        ]);

        if (!$response->successful()) {
            $fail('Ошибка проверки reCAPTCHA. Попробуйте еще раз.');
            return;
        }

        $result = $response->json();

        if (!$result['success']) {
            $fail('Проверка reCAPTCHA не пройдена. Попробуйте еще раз.');
        }
    }
}
