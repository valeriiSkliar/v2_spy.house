<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NewsletterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class UnsubscribeController extends Controller
{
    private NewsletterService $newsletterService;

    public function __construct(NewsletterService $newsletterService)
    {
        $this->newsletterService = $newsletterService;
    }

    /**
     * Показать страницу подтверждения отписки
     */
    public function show(string $hash): View
    {
        $user = User::where('unsubscribe_hash', $hash)
            ->where('is_newsletter_subscribed', true)
            ->first();

        return view('unsubscribe.show', [
            'user' => $user,
            'hash' => $hash,
            'isValidHash' => $user !== null
        ]);
    }

    /**
     * Обработать отписку пользователя
     */
    public function unsubscribe(Request $request, string $hash): JsonResponse
    {
        try {
            $user = User::where('unsubscribe_hash', $hash)
                ->where('is_newsletter_subscribed', true)
                ->first();

            if (!$user) {
                Log::warning('Unsubscribe attempt with invalid hash', [
                    'hash' => $hash,
                    'ip' => $request->ip()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Неверная ссылка для отписки или пользователь уже отписан'
                ], 404);
            }

            // Выполняем отписку через сервис
            $result = $this->newsletterService->unsubscribeUser($user);

            if ($result['success']) {
                Log::info('User successfully unsubscribed from newsletter', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip' => $request->ip()
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Вы успешно отписались от рассылки',
                    'redirect' => route('unsubscribe.success')
                ]);
            }

            Log::error('Failed to unsubscribe user', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $result['error'] ?? 'Unknown error'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при отписке. Попробуйте позже.'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Exception during unsubscribe process', [
                'hash' => $hash,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Произошла внутренняя ошибка'
            ], 500);
        }
    }

    /**
     * Показать страницу успешной отписки
     */
    public function success(): View
    {
        return view('unsubscribe.success');
    }
}
