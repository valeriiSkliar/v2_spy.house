<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmailVerificationRequest;
use App\Notifications\Auth\EmailVerifiedNotification;
use App\Traits\App\HasAntiFloodProtection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Resend\Laravel\Facades\Resend;

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
        if (! $canResend5min) {
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
            'canResend' => $canResend5min && $canResendDaily,
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
                'redirect' => route('profile.settings', absolute: false) . '?verified=1',
            ]);
        }

        // Получаем код из запроса
        $verificationCode = $request->getVerificationCode();

        // Получаем сохраненный код из кэша
        $cachedCode = Cache::get('email_verification_code:' . $user->id);

        if (! $cachedCode) {
            return response()->json([
                'success' => false,
                'message' => __('auth.email_verification.code_expired'),
            ], 422);
        }

        if ($verificationCode !== $cachedCode) {
            return response()->json([
                'success' => false,
                'message' => __('auth.email_verification.invalid_code'),
            ], 422);
        }

        if ($user->markEmailAsVerified()) {
            // Активируем триал период на 7 дней только если он еще не был использован
            if (!$user->hasActiveSubscription() && !$user->isTrialPeriod() && !$user->hasTrialPeriodBeenUsed()) {
                $user->activateTrialPeriod();
            }

            // Отправляем уведомление в приложение о подтверждении email
            $user->notify(new EmailVerifiedNotification([
                'verification_ip' => $request->ip(),
                'verification_method' => 'code',
                'verification_date' => now()->format('Y-m-d H:i:s'),
            ]));

            // Add or update user in Resend audience
            try {
                $unsubscribeHash = $user->unsubscribe_hash ?? Str::random(32);
                $audienceId = config('services.resend.audience_id');

                // Check if user has an existing contact ID from previous email
                // This handles email change scenarios where old contact needs to be deleted
                if ($user->email_contact_id && $user->is_newsletter_subscribed) {
                    try {
                        // Try to get the existing contact by the stored contact ID
                        $existingContactById = Resend::contacts()->get(
                            $audienceId,
                            $user->email_contact_id
                        );

                        // If the existing contact has a different email than current user email,
                        // it means this is an email change verification - delete the old contact
                        if (
                            $existingContactById &&
                            isset($existingContactById['email']) &&
                            $existingContactById['email'] !== $user->email
                        ) {

                            Log::info('Email change detected during verification - deleting old contact', [
                                'user_id' => $user->id,
                                'old_email' => $existingContactById['email'],
                                'new_email' => $user->email,
                                'old_contact_id' => $user->email_contact_id,
                            ]);

                            // Delete the old contact
                            Resend::contacts()->remove(
                                $audienceId,
                                $user->email_contact_id
                            );

                            Log::info('Old contact deleted during email verification', [
                                'user_id' => $user->id,
                                'old_email' => $existingContactById['email'],
                                'old_contact_id' => $user->email_contact_id,
                            ]);

                            // Reset contact ID so we create a new one below
                            $user->email_contact_id = null;
                        }
                    } catch (\Exception $e) {
                        Log::debug('Could not find existing contact by ID during verification', [
                            'user_id' => $user->id,
                            'contact_id' => $user->email_contact_id,
                            'error' => $e->getMessage(),
                        ]);
                        // Reset contact ID if we can't find the old contact
                        $user->email_contact_id = null;
                    }
                }

                // Now check if contact exists by current email
                $existingContact = null;
                try {
                    $existingContact = Resend::contacts()->get(
                        $audienceId,
                        $user->email
                    );
                } catch (\Exception $e) {
                    // Contact doesn't exist, which is fine - we'll create it
                    Log::debug('Contact not found in Resend audience', [
                        'user_id' => $user->id,
                        'email' => $user->email,
                    ]);
                }

                if ($existingContact && isset($existingContact['id'])) {
                    // Update existing contact
                    $response = Resend::contacts()->update(
                        $audienceId,
                        $user->email,
                        [
                            'first_name' => $user->name ?? $user->login ?? '',
                            'unsubscribed' => false,
                        ]
                    );

                    if (isset($response['id'])) {
                        $user->update([
                            'email_contact_id' => $response['id'],
                            'is_newsletter_subscribed' => true,
                            'unsubscribe_hash' => $unsubscribeHash,
                        ]);

                        Log::info('User contact updated in Resend audience', [
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'contact_id' => $response['id'],
                        ]);
                    }
                } else {
                    // Create new contact
                    $response = Resend::contacts()->create(
                        $audienceId,
                        [
                            'email' => $user->email,
                            'first_name' => $user->login ?? $user->name ?? '',
                            'last_name' => $unsubscribeHash,
                            'unsubscribed' => false,
                        ]
                    );

                    if (isset($response['id'])) {
                        $user->update([
                            'email_contact_id' => $response['id'],
                            'is_newsletter_subscribed' => true,
                            'unsubscribe_hash' => $unsubscribeHash,
                        ]);

                        Log::info('User successfully added to Resend audience', [
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'contact_id' => $response['id'],
                        ]);
                    }
                }
            } catch (\Exception $e) {
                // Log error but don't fail the verification process
                Log::warning('Failed to add/update user in Resend audience after email verification', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }

            Cache::forget('email_verification_code:' . $user->id);
        }

        return response()->json([
            'success' => true,
            'message' => __('auth.email_verification.success'),
            'redirect' => route('profile.settings', absolute: false) . '?verified=1',
        ]);
    }
}
