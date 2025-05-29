<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Rules\Recaptcha;
use App\Services\Frontend\Toast;
use App\Services\SecurityAuditService;
use App\Models\User;
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
    public function store(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        // Проверяем, является ли запрос AJAX
        if ($request->ajax() || $request->wantsJson()) {
            try {
                $request->validate([
                    'email' => ['required', 'email'],
                    'g-recaptcha-response' => ['required', new Recaptcha()],
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors(),
                    'message' => __('validation.failed')
                ], 422);
            }

            // Проверяем, прошло ли 24 часа после последнего успешного сброса
            $user = User::where('email', $request->email)->first();

            if ($user && $user->last_password_reset_at) {
                $hoursSinceLastReset = now()->diffInHours($user->last_password_reset_at);
                if ($hoursSinceLastReset < 24) {
                    SecurityAuditService::logPasswordResetEvent(
                        $request->email,
                        'password_reset_throttled_24h',
                        $request->ip(),
                        ['hours_since_last_reset' => $hoursSinceLastReset]
                    );

                    $remainingHours = 24 - $hoursSinceLastReset;
                    return response()->json([
                        'success' => false,
                        'message' => __('validation.password_reset_throttled', ['hours' => ceil($remainingHours)]),
                        'errors' => ['email' => [__('validation.password_reset_throttled', ['hours' => ceil($remainingHours)])]]
                    ], 422);
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

                return response()->json([
                    'success' => true,
                    'message' => __($status)
                ]);
            } else {
                SecurityAuditService::logPasswordResetEvent(
                    $request->email,
                    'password_reset_link_failed',
                    $request->ip(),
                    ['status' => $status]
                );

                return response()->json([
                    'success' => false,
                    'message' => __($status),
                    'errors' => ['email' => [__($status)]]
                ], 422);
            }
        }

        // Стандартная обработка для не-AJAX запросов
        $request->validate([
            'email' => ['required', 'email'],
            'g-recaptcha-response' => ['required', new Recaptcha()],
        ]);

        // Проверяем, прошло ли 24 часа после последнего успешного сброса
        $user = User::where('email', $request->email)->first();

        if ($user && $user->last_password_reset_at) {
            $hoursSinceLastReset = now()->diffInHours($user->last_password_reset_at);
            if ($hoursSinceLastReset < 24) {
                SecurityAuditService::logPasswordResetEvent(
                    $request->email,
                    'password_reset_throttled_24h',
                    $request->ip(),
                    ['hours_since_last_reset' => $hoursSinceLastReset]
                );

                $remainingHours = 24 - $hoursSinceLastReset;
                Toast::error(__('validation.password_reset_throttled', ['hours' => ceil($remainingHours)]));
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
