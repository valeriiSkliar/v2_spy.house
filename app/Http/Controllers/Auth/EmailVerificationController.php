<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\Auth\EmailVerifiedNotification;
use App\Traits\App\HasAntiFloodProtection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    use HasAntiFloodProtection;

    /**
     * Display the email verification prompt or redirect if already verified.
     */
    public function show(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('profile.settings', absolute: false) . '?verified=1');
        }

        $userId = $user->id;
        $unblockTime = null;

        // Проверяем текущие записи AntiFlood без инкремента
        $current5min = $this->getAntiFloodRecord($userId, 'resend_verification_5min');
        $currentDaily = $this->getAntiFloodRecord($userId, 'resend_verification_daily');

        $canResend5min = ($current5min === null || $current5min < 1);
        $canResendDaily = ($currentDaily === null || $currentDaily < 5);

        // Если нарушен 5-минутный лимит, вычисляем время разблокировки
        if (!$canResend5min) {
            // Получаем время первого запроса в текущем окне
            $firstRequestTime = $this->getAntiFloodTimestamp($userId, 'resend_verification_5min');

            if ($firstRequestTime) {
                // Время разблокировки = время первого запроса + 5 минут
                $unblockTime = ($firstRequestTime + 300) * 1000; // В миллисекундах для JS
            } else {
                // Если нет данных о первом запросе, блокируем на 5 минут от текущего времени
                $unblockTime = (time() + 300) * 1000;
            }
        }

        return view('pages.profile.verify-your-account', [
            'unblockTime' => $unblockTime,
            'canResend' => $canResend5min && $canResendDaily
        ]);
    }

    /**
     * Handle email verification with code via POST request
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
            // Отправляем уведомление в приложение о подтверждении email
            $user->notify(new EmailVerifiedNotification([
                'verification_ip' => $request->ip(),
                'verification_method' => 'code',
                'verification_date' => now()->format('Y-m-d H:i:s')
            ]));

            Cache::forget('email_verification_code:' . $user->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email успешно подтвержден',
            'redirect' => route('profile.settings', absolute: false) . '?verified=1'
        ]);
    }
}