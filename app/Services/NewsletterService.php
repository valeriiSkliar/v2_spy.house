<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Resend\Laravel\Facades\Resend;

class NewsletterService
{
    /**
     * Отписать пользователя от рассылки
     */
    public function unsubscribeUser(User $user): array
    {
        try {
            DB::beginTransaction();

            $result = $this->processUnsubscribe($user);

            if ($result['success']) {
                DB::commit();

                return $result;
            }

            DB::rollBack();

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Exception in unsubscribeUser', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Подписать пользователя на рассылку
     */
    public function subscribeUser(User $user): array
    {
        try {
            if ($user->is_newsletter_subscribed) {
                return [
                    'success' => true,
                    'message' => 'User already subscribed',
                ];
            }

            $audienceId = config('services.resend.audience_id');

            // Создаем контакт в Resend
            $response = Resend::contacts()->create(
                $audienceId,
                [
                    'email' => $user->email,
                    'first_name' => $user->login ?? $user->name ?? '',
                    'last_name' => $user->unsubscribe_hash ?? '',
                    'unsubscribed' => false,
                ]
            );

            if (isset($response['id'])) {
                $user->update([
                    'email_contact_id' => $response['id'],
                    'is_newsletter_subscribed' => true,
                ]);

                Log::info('User subscribed to newsletter', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'contact_id' => $response['id'],
                ]);

                return [
                    'success' => true,
                    'contact_id' => $response['id'],
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to create contact in Resend',
            ];
        } catch (\Exception $e) {
            Log::error('Exception in subscribeUser', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Обновить данные пользователя в Resend
     */
    public function updateUserInResend(User $user, array $data): array
    {
        try {
            if (! $user->email_contact_id) {
                return [
                    'success' => false,
                    'error' => 'User has no contact ID',
                ];
            }

            $audienceId = config('services.resend.audience_id');

            $response = Resend::contacts()->update(
                $audienceId,
                $user->email,
                $data
            );

            if (isset($response['id'])) {
                Log::info('User contact updated in Resend', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'contact_id' => $response['id'],
                ]);

                return [
                    'success' => true,
                    'contact_id' => $response['id'],
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to update contact in Resend',
            ];
        } catch (\Exception $e) {
            Log::error('Exception in updateUserInResend', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Обработать отписку пользователя
     */
    private function processUnsubscribe(User $user): array
    {
        $errors = [];
        $steps = [];

        // Шаг 1: Удаляем из Resend audience
        if ($user->email_contact_id) {
            try {
                $audienceId = config('services.resend.audience_id');

                Resend::contacts()->remove(
                    $audienceId,
                    $user->email_contact_id
                );

                $steps[] = 'Removed from Resend audience';
                Log::info('User contact removed from Resend audience', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'contact_id' => $user->email_contact_id,
                ]);
            } catch (\Exception $e) {
                $error = 'Failed to remove from Resend: '.$e->getMessage();
                $errors[] = $error;

                Log::warning('Failed to remove user from Resend audience', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'contact_id' => $user->email_contact_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Шаг 2: Обновляем состояние в БД
        try {
            $user->update([
                'is_newsletter_subscribed' => false,
                'email_contact_id' => null,
            ]);

            $steps[] = 'Updated database state';
            Log::info('User newsletter subscription disabled in database', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        } catch (\Exception $e) {
            $error = 'Failed to update database: '.$e->getMessage();
            $errors[] = $error;

            Log::error('Failed to update user subscription status in database', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }

        // Определяем результат
        if (empty($errors)) {
            return [
                'success' => true,
                'steps' => $steps,
            ];
        }

        // Если есть ошибки, но БД обновилась - считаем частичным успехом
        if (in_array('Updated database state', $steps)) {
            Log::warning('Partial unsubscribe success - database updated but Resend failed', [
                'user_id' => $user->id,
                'steps' => $steps,
                'errors' => $errors,
            ]);

            return [
                'success' => true, // Основная цель достигнута - пользователь отписан в БД
                'partial' => true,
                'steps' => $steps,
                'errors' => $errors,
            ];
        }

        return [
            'success' => false,
            'errors' => $errors,
        ];
    }
}
