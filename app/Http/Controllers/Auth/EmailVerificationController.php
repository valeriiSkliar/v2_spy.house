<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailVerificationRequest;
use App\Notifications\Auth\EmailVerifiedNotification;
use App\Traits\App\HasAntiFloodProtection;
use Resend\Laravel\Facades\Resend;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
    public function verify(EmailVerificationRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'success' => true,
                'message' => __('auth.email_verification.already_verified'),
                'redirect' => route('profile.settings', absolute: false) . '?verified=1'
            ]);
        }

        // Получаем код из запроса
        $verificationCode = $request->getVerificationCode();

        // Получаем сохраненный код из кэша
        $cachedCode = Cache::get('email_verification_code:' . $user->id);

        if (!$cachedCode) {
            return response()->json([
                'success' => false,
                'message' => __('auth.email_verification.code_expired')
            ], 422);
        }

        if ($verificationCode !== $cachedCode) {
            return response()->json([
                'success' => false,
                'message' => __('auth.email_verification.invalid_code')
            ], 422);
        }

        if ($user->markEmailAsVerified()) {
            // Отправляем уведомление в приложение о подтверждении email
            $user->notify(new EmailVerifiedNotification([
                'verification_ip' => $request->ip(),
                'verification_method' => 'code',
                'verification_date' => now()->format('Y-m-d H:i:s')
            ]));

            // Add user to Resend audience
            try {
                $unsubscribeHash = Hash::make($user->id ?? $user->login ?? '', ['rounds' => 12]);
                $response = Resend::contacts()->create(
                    config('services.resend.audience_id'),
                    [
                        'email' => $user->email,
                        'first_name' => $user->id ?? $user->login ?? '',
                        'last_name' => $unsubscribeHash,
                        'unsubscribed' => false,
                    ]
                );

                // Update user with contact ID and newsletter subscription status
                if (isset($response['id'])) {
                    $user->update([
                        'email_contact_id' => $response['id'],
                        'is_newsletter_subscribed' => true,
                        'unsubscribe_hash' => $unsubscribeHash, // Generate unique unsubscribe hash
                    ]);

                    Log::info('User successfully added to Resend audience', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'contact_id' => $response['id']
                    ]);
                }
            } catch (\Exception $e) {
                // Log error but don't fail the verification process
                Log::warning('Failed to add user to Resend audience after email verification', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage()
                ]);
            }

            Cache::forget('email_verification_code:' . $user->id);
        }

        return response()->json([
            'success' => true,
            'message' => __('auth.email_verification.success'),
            'redirect' => route('profile.settings', absolute: false) . '?verified=1'
        ]);
    }
}
