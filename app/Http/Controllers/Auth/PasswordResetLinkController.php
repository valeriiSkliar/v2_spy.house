<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\Recaptcha;
use App\Services\Frontend\Toast;
use App\Services\SecurityAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('pages.profile.password_recovery');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'g-recaptcha-response' => ['required', new Recaptcha()],
        ]);

        // Проверяем, прошло ли 24 часа после последнего успешного сброса
        $lastReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->whereNotNull('last_successful_reset_at')
            ->orderBy('last_successful_reset_at', 'desc')
            ->first();

        if ($lastReset && $lastReset->last_successful_reset_at) {
            $hoursSinceLastReset = now()->diffInHours($lastReset->last_successful_reset_at);
            if ($hoursSinceLastReset < 24) {
                SecurityAuditService::logPasswordResetEvent(
                    $request->email,
                    'password_reset_throttled_24h',
                    $request->ip(),
                    ['hours_since_last_reset' => $hoursSinceLastReset]
                );

                Toast::error(__('Вы можете запросить восстановление пароля только через 24 часа после последнего успешного сброса.'));
                return back()->withInput($request->only('email'));
            }
        }

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Сохраняем IP адрес запроса
        if ($status == Password::RESET_LINK_SENT) {
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->update(['request_ip' => $request->ip()]);

            SecurityAuditService::logPasswordResetEvent(
                $request->email,
                'password_reset_link_sent',
                $request->ip()
            );

            Toast::success(__($status));
            return back();
        } else {
            SecurityAuditService::logPasswordResetEvent(
                $request->email,
                'password_reset_link_failed',
                $request->ip(),
                ['status' => $status]
            );

            Toast::error(__($status));
            return back()->withInput($request->only('email'));
        }
    }
}
