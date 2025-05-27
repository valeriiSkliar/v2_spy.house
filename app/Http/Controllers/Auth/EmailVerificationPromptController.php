<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Traits\App\HasAntiFloodProtection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    use HasAntiFloodProtection;

    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('profile.settings', absolute: false));
        }

        $userId = $request->user()->id;
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
}
