<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class VerifyEmailController extends Controller
{
    /**
     * Show verification page or handle GET request
     */
    public function show(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('profile.settings', absolute: false) . '?verified=1');
        }

        // Показываем страницу верификации
        return redirect()->route('verify.account');
    }

    /**
     * Handle verification with code via POST request
     */
    public function verify(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => 'Email уже подтвержден',
                'redirect' => route('profile.settings', absolute: false) . '?verified=1'
            ]);
        }

        // Валидация кода
        $validator = Validator::make($request->all(), [
            'code' => 'required|array|size:6',
            'code.*' => 'required|string|size:1|regex:/^[0-9]$/'
        ], [
            'code.required' => 'Код подтверждения обязателен',
            'code.array' => 'Неверный формат кода',
            'code.size' => 'Код должен состоять из 6 цифр',
            'code.*.required' => 'Все поля кода должны быть заполнены',
            'code.*.regex' => 'Код должен содержать только цифры'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Получаем код из запроса и объединяем в строку
        $verificationCode = implode('', $request->input('code'));

        // Получаем сохраненный код из кэша
        $cachedCode = Cache::get('email_verification_code:' . $user->id);

        if (!$cachedCode) {
            return response()->json([
                'success' => false,
                'message' => 'Код подтверждения истек. Пожалуйста, запросите новый код.'
            ], 422);
        }

        if ($verificationCode !== $cachedCode) {
            return response()->json([
                'success' => false,
                'message' => 'Неверный код подтверждения'
            ], 422);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            Cache::forget('email_verification_code:' . $user->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email успешно подтвержден',
            'redirect' => route('profile.settings', absolute: false) . '?verified=1'
        ]);
    }
}
