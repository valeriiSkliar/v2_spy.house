<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\Recaptcha;
use App\Services\Frontend\Toast;
use App\Services\SecurityAuditService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        // Сохраняем IP-адрес доступа к странице сброса
        if ($request->has('token') && $request->has('email')) {
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->update(['access_ip' => $request->ip()]);

            // Проверяем несовпадение IP
            $ipMismatch = SecurityAuditService::checkIpMismatch($request->email);
            if ($ipMismatch) {
                SecurityAuditService::logPasswordResetEvent(
                    $request->email,
                    'password_reset_ip_mismatch',
                    $request->ip(),
                    $ipMismatch
                );
            }
        }

        return view('pages.profile.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        // Проверяем, является ли запрос AJAX
        if ($request->ajax() || $request->wantsJson()) {
            try {
                $request->validate([
                    'token' => ['required'],
                    'email' => ['required', 'email'],
                    'password' => ['required', 'confirmed', 'min:8', 'max:64'],
                    'g-recaptcha-response' => ['required', new Recaptcha()],
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                return response()->json([
                    'success' => false,
                    'errors' => $e->errors(),
                    'message' => __('validation.failed')
                ], 422);
            }

            // Here we will attempt to reset the user's password. If it is successful we
            // will update the password on an actual user model and persist it to the
            // database. Otherwise we will parse the error and return the response.
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user) use ($request) {
                    $user->forceFill([
                        'password' => Hash::make($request->password),
                        'remember_token' => Str::random(60),
                        'last_password_reset_at' => now(),
                    ])->save();

                    // Отмечаем время использования токена
                    DB::table('password_reset_tokens')
                        ->where('email', $user->email)
                        ->update([
                            'used_at' => now(),
                        ]);

                    SecurityAuditService::logPasswordResetEvent(
                        $user->email,
                        'password_reset_successful',
                        $request->ip()
                    );

                    event(new PasswordReset($user));
                }
            );

            // If the password was successfully reset, we will redirect the user back to
            // the application's home authenticated view. If there is an error we can
            // redirect them back to where they came from with their error message.
            if ($status == Password::PASSWORD_RESET) {
                return response()->json([
                    'success' => true,
                    'message' => __($status),
                    'redirect' => route('login')
                ]);
            } else {
                SecurityAuditService::logPasswordResetEvent(
                    $request->email,
                    'password_reset_failed',
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
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8', 'max:64'],
            'g-recaptcha-response' => ['required', new Recaptcha()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                    'last_password_reset_at' => now(),
                ])->save();

                // Отмечаем время использования токена
                DB::table('password_reset_tokens')
                    ->where('email', $user->email)
                    ->update([
                        'used_at' => now(),
                    ]);

                SecurityAuditService::logPasswordResetEvent(
                    $user->email,
                    'password_reset_successful',
                    $request->ip()
                );

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        if ($status == Password::PASSWORD_RESET) {
            Toast::success(__($status));
            return redirect()->route('login');
        } else {
            SecurityAuditService::logPasswordResetEvent(
                $request->email,
                'password_reset_failed',
                $request->ip(),
                ['status' => $status]
            );

            Toast::error(__($status));
            return back()->withInput($request->only('email'));
        }
    }
}
