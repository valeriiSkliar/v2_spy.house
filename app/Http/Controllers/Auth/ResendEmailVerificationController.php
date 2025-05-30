<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Events\User\AccountConfirmationCodeRequested;
use App\Traits\App\HasAntiFloodProtection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ResendEmailVerificationController extends Controller
{
    use HasAntiFloodProtection;

    /**
     * Resend email verification code
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        // Проверяем, что email еще не подтвержден
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email уже подтвержден'
            ], 422);
        }

        $userId = $user->id;

        // Проверяем лимит 1 раз в 5 минут
        if (!$this->checkAntiFlood($userId, 'resend_verification_5min', 1, 300)) {
            $firstRequestTime = $this->getAntiFloodTimestamp($userId, 'resend_verification_5min');
            $retryAfter = $firstRequestTime ? max(0, 300 - (time() - $firstRequestTime)) : 300;

            return response()->json([
                'success' => false,
                'message' => 'Слишком частые запросы, попробуйте через 5 минут',
                'retry_after' => $retryAfter,
                'unblock_time' => $firstRequestTime ? ($firstRequestTime + 300) * 1000 : (time() + 300) * 1000
            ], 429);
        }

        // Проверяем дневной лимит (5 раз в сутки)
        if (!$this->checkAntiFlood($userId, 'resend_verification_daily', 5, 86400)) {
            return response()->json([
                'success' => false,
                'message' => 'Превышен дневной лимит отправки кода. Попробуйте завтра.'
            ], 429);
        }

        // Генерируем код верификации
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Сохраняем код в кэш на 15 минут
        Cache::put('email_verification_code:' . $user->id, $code, now()->addMinutes(15));

        // Генерируем событие запроса кода
        AccountConfirmationCodeRequested::dispatch($user, $code, [
            'request_ip' => $request->ip(),
            'resend_count' => $this->getAntiFloodRecord($userId, 'resend_verification_daily') ?? 0
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Код подтверждения отправлен на ваш email',
            'unblock_time' => (time() + 300) * 1000,
            'server_time' => time() * 1000
        ]);
    }
}
