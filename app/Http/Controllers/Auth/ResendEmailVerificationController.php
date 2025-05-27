<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\App\HasAntiFloodProtection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResendEmailVerificationController extends Controller
{
    use HasAntiFloodProtection;

    /**
     * Resend email verification link
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        // Проверяем, что пользователь аутентифицирован
        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 401);
        }

        // Проверяем, что email еще не подтвержден
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'error' => 'Email already verified'
            ], 422);
        }

        $userId = $user->id;

        // Проверяем лимит 1 раз в 5 минут
        if (!$this->checkAntiFlood($userId, 'resend_verification_5min', 1, 300)) {
            $firstRequestTime = $this->getAntiFloodTimestamp($userId, 'resend_verification_5min');
            $retryAfter = $firstRequestTime ? max(0, 300 - (time() - $firstRequestTime)) : 300;

            return response()->json([
                'error' => 'Too frequent requests',
                'message' => 'Слишком частые запросы, попробуйте через 5 минут',
                'retry_after' => $retryAfter,
                'unblock_time' => $firstRequestTime ? ($firstRequestTime + 300) * 1000 : (time() + 300) * 1000
            ], 429);
        }

        // Проверяем дневной лимит (5 раз в сутки)
        if (!$this->checkAntiFlood($userId, 'resend_verification_daily', 5, 86400)) {
            return response()->json([
                'error' => 'Daily limit exceeded',
                'message' => 'Дневной лимит исчерпан. Попробуйте завтра'
            ], 429);
        }

        // Отправляем ссылку для подтверждения
        $user->sendEmailVerificationNotification();

        // Получаем время первого запроса для расчета времени разблокировки
        $firstRequestTime = $this->getAntiFloodTimestamp($userId, 'resend_verification_5min');
        $unblockTime = $firstRequestTime ? ($firstRequestTime + 300) * 1000 : (time() + 300) * 1000;

        return response()->json([
            'success' => true,
            'message' => 'Ссылка отправлена на ваш email',
            'block_duration' => 300000, // 5 минут в миллисекундах
            'server_time' => time() * 1000,
            'unblock_time' => $unblockTime
        ]);
    }
}
