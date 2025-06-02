<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\User;
use App\Services\SecurityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

trait ProfileVerificationTrait
{
    protected function handleSecureAction(
        User $user,
        string $action,
        callable $callback,
        array $data = []
    ): array|JsonResponse|RedirectResponse {
        try {
            $result = $callback($user, $data);

            if (is_array($result) && isset($result['success']) && $result['success']) {
                Log::info("Secure action initiated: {$action}", [
                    'user_id' => $user->id,
                    'action' => $action,
                ]);

                return $this->formatResponse($result, $action);
            }

            return $this->formatErrorResponse("Failed to initiate {$action}");

        } catch (\Exception $e) {
            Log::error("Error in secure action: {$action}", [
                'user_id' => $user->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return $this->formatErrorResponse($e->getMessage());
        }
    }

    protected function handleSecureConfirmation(
        User $user,
        string $action,
        string $code,
        callable $callback
    ): array|JsonResponse|RedirectResponse {
        try {
            $result = $callback($user, $code);

            if ($result) {
                Log::info("Secure action confirmed: {$action}", [
                    'user_id' => $user->id,
                    'action' => $action,
                ]);

                return $this->formatSuccessResponse($action);
            }

            return $this->formatErrorResponse(__('profile.errors.invalid_code'));

        } catch (\Exception $e) {
            Log::error("Error confirming secure action: {$action}", [
                'user_id' => $user->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return $this->formatErrorResponse($e->getMessage());
        }
    }

    protected function formatResponse(array $result, string $action): array|JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __("profile.success.{$action}_initiated"),
                'data' => $result,
            ]);
        }

        return redirect()->back()->with([
            'success' => __("profile.success.{$action}_initiated"),
            'confirmation_method' => $result['confirmation_method'] ?? null,
        ]);
    }

    protected function formatSuccessResponse(string $action): array|JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => __("profile.success.{$action}_completed"),
            ]);
        }

        return redirect()->route('profile.settings')->with([
            'success' => __("profile.success.{$action}_completed"),
        ]);
    }

    protected function formatErrorResponse(string $message): array|JsonResponse|RedirectResponse
    {
        if (request()->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 422);
        }

        return redirect()->back()->withErrors(['error' => $message]);
    }

    protected function getConfirmationMethod(User $user): string
    {
        return app(SecurityService::class)->getConfirmationMethod($user);
    }

    protected function isOperationPending(User $user, string $operationType): bool
    {
        return app(\App\Services\ProfileService::class)->isOperationPending($user, $operationType);
    }

    protected function cancelOperation(User $user, string $operationType): bool
    {
        return app(\App\Services\ProfileService::class)->cancelPendingOperation($user, $operationType);
    }

    protected function sendVerificationCode(User $user, string $method, string $code): bool
    {
        return app(SecurityService::class)->sendVerificationCode($user, $method, $code);
    }

    protected function verifyUserCode(User $user, string $code, string $method): bool
    {
        return app(SecurityService::class)->verifyCode($user, $code, $method);
    }

    protected function renderProfileView(string $view, array $data = []): \Illuminate\View\View
    {
        $defaultData = [
            'user' => auth()->user(),
            'activeTab' => request()->query('tab', 'personal'),
        ];

        return view($view, array_merge($defaultData, $data));
    }

    protected function validateSecurityAction(User $user, string $action): bool
    {
        // Add security validations here
        switch ($action) {
            case 'password_change':
                return ! $this->isOperationPending($user, 'password');
            case 'email_change':
                return ! $this->isOperationPending($user, 'email');
            case '2fa_disable':
                return $user->google_2fa_enabled;
            default:
                return true;
        }
    }

    protected function logSecurityEvent(User $user, string $event, array $context = []): void
    {
        Log::info("Security event: {$event}", array_merge([
            'user_id' => $user->id,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $context));
    }
}
