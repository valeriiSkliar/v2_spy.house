<?php

namespace App\Finance\Services;

use App\Models\User;
use App\Finance\Models\Payment;
use App\Finance\Models\Subscription;
use App\Enums\Finance\PaymentType;
use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BalanceService
{
    /**
     * Проверить достаточность средств на балансе пользователя
     *
     * @param User $user
     * @param float $amount
     * @return bool
     */
    public function hasInsufficientBalance(User $user, float $amount): bool
    {
        return $user->available_balance < $amount;
    }

    /**
     * Оплатить подписку с баланса пользователя
     *
     * @param User $user
     * @param Subscription $subscription
     * @param string $billingType
     * @return array
     */
    public function processSubscriptionPaymentFromBalance(User $user, Subscription $subscription, string $billingType = 'month'): array
    {
        // Вычисляем сумму
        $amount = $subscription->amount;
        if ($billingType === 'year') {
            $amount = $subscription->amount * 12 * 0.8; // 20% скидка
        }

        Log::info('BalanceService: Начало обработки платежа с баланса', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'billing_type' => $billingType,
            'current_balance' => $user->available_balance
        ]);

        // Проверяем достаточность средств
        if ($this->hasInsufficientBalance($user, $amount)) {
            Log::warning('BalanceService: Недостаточно средств на балансе', [
                'user_id' => $user->id,
                'required_amount' => $amount,
                'available_balance' => $user->available_balance
            ]);

            return [
                'success' => false,
                'error' => 'Недостаточно средств на балансе. Требуется: $' . number_format($amount, 2) . ', доступно: $' . number_format($user->available_balance, 2)
            ];
        }

        // Выполняем операцию в транзакции с optimistic locking
        try {
            return DB::transaction(function () use ($user, $subscription, $amount, $billingType) {
                // Перезагружаем пользователя с блокировкой для чтения
                $userForUpdate = User::where('id', $user->id)
                    ->where('balance_version', $user->balance_version)
                    ->lockForUpdate()
                    ->first();

                if (!$userForUpdate) {
                    throw new \Exception('Баланс был изменен другой операцией. Повторите попытку.');
                }

                // Повторная проверка баланса после блокировки
                if ($this->hasInsufficientBalance($userForUpdate, $amount)) {
                    throw new \Exception('Недостаточно средств на балансе после блокировки');
                }

                // Создаем платеж
                $payment = $this->createBalancePayment($userForUpdate, $subscription, $amount, $billingType);

                // Списываем средства с баланса
                $this->deductFromBalance($userForUpdate, $amount);

                // Сразу помечаем платеж как успешный (внутренний платеж)
                $payment->markAsSuccessful();

                // Активируем подписку
                $this->activateSubscription($userForUpdate, $subscription, $billingType);

                Log::info('BalanceService: Платеж с баланса выполнен успешно', [
                    'user_id' => $userForUpdate->id,
                    'payment_id' => $payment->id,
                    'subscription_id' => $subscription->id,
                    'amount' => $amount,
                    'new_balance' => $userForUpdate->available_balance
                ]);

                return [
                    'success' => true,
                    'payment' => $payment,
                    'message' => 'Подписка успешно оплачена с баланса'
                ];
            });
        } catch (\Exception $e) {
            Log::error('BalanceService: Ошибка при обработке платежа с баланса', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Создать запись о платеже с баланса
     *
     * @param User $user
     * @param Subscription $subscription
     * @param float $amount
     * @param string $billingType
     * @return Payment
     */
    protected function createBalancePayment(User $user, Subscription $subscription, float $amount, string $billingType): Payment
    {
        return Payment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
            'subscription_id' => $subscription->id,
            'payment_method' => PaymentMethod::USER_BALANCE,
            'status' => PaymentStatus::PENDING,
            'external_number' => 'TN' . $user->id . $subscription->id . time(),
            'invoice_number' => 'BALANCE_' . Str::random(10),
            'webhook_token' => Str::random(64),
            'idempotency_key' => Str::uuid(),
        ]);
    }

    /**
     * Списать средства с баланса пользователя (с optimistic locking)
     *
     * @param User $user
     * @param float $amount
     * @return bool
     */
    protected function deductFromBalance(User $user, float $amount): bool
    {
        $oldBalance = $user->available_balance;
        $newBalance = $oldBalance - $amount;
        $oldVersion = $user->balance_version;
        $newVersion = $oldVersion + 1;

        // Обновляем баланс с проверкой версии
        $updated = User::where('id', $user->id)
            ->where('balance_version', $oldVersion)
            ->update([
                'available_balance' => $newBalance,
                'balance_version' => $newVersion
            ]);

        if (!$updated) {
            throw new \Exception('Не удалось обновить баланс - версия изменена');
        }

        // Обновляем объект в памяти
        $user->available_balance = $newBalance;
        $user->balance_version = $newVersion;

        Log::info('BalanceService: Баланс обновлен', [
            'user_id' => $user->id,
            'old_balance' => $oldBalance,
            'new_balance' => $newBalance,
            'deducted_amount' => $amount,
            'old_version' => $oldVersion,
            'new_version' => $newVersion
        ]);

        return true;
    }

    /**
     * Активировать подписку пользователя
     *
     * @param User $user
     * @param Subscription $subscription
     * @param string $billingType
     * @return void
     */
    protected function activateSubscription(User $user, Subscription $subscription, string $billingType): void
    {
        // Определяем время активации
        if ($this->isRenewal($user, $subscription)) {
            // Продление: добавляем к существующему времени окончания
            $startTime = $user->subscription_time_end ?? now();
            $endTime = $billingType === 'year'
                ? $startTime->copy()->addYear()
                : $startTime->copy()->addMonth();
        } else {
            // Новая подписка или апгрейд: начинаем с текущего момента
            $startTime = now();
            $endTime = $billingType === 'year'
                ? $startTime->copy()->addYear()
                : $startTime->copy()->addMonth();
        }

        // Обновляем подписку пользователя
        $user->update([
            'subscription_id' => $subscription->id,
            'subscription_time_start' => $startTime,
            'subscription_time_end' => $endTime,
            'subscription_is_expired' => false,
            'queued_subscription_id' => null,
        ]);

        Log::info('BalanceService: Подписка активирована', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'subscription_name' => $subscription->name,
            'billing_type' => $billingType,
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $endTime->toDateTimeString(),
            'is_renewal' => $this->isRenewal($user, $subscription)
        ]);
    }

    /**
     * Проверить является ли это продлением текущей подписки
     *
     * @param User $user
     * @param Subscription $subscription
     * @return bool
     */
    protected function isRenewal(User $user, Subscription $subscription): bool
    {
        return $user->subscription_id === $subscription->id
            && $user->subscription_time_end
            && $user->subscription_time_end > now();
    }

    /**
     * Пополнить баланс пользователя (для будущего использования)
     *
     * @param User $user
     * @param float $amount
     * @param string $description
     * @return bool
     */
    public function addToBalance(User $user, float $amount, string $description = ''): bool
    {
        try {
            return DB::transaction(function () use ($user, $amount, $description) {
                // Перезагружаем пользователя с блокировкой
                $userForUpdate = User::where('id', $user->id)
                    ->where('balance_version', $user->balance_version)
                    ->lockForUpdate()
                    ->first();

                if (!$userForUpdate) {
                    throw new \Exception('Пользователь не найден или баланс был изменен');
                }

                $oldBalance = $userForUpdate->available_balance;
                $newBalance = $oldBalance + $amount;
                $oldVersion = $userForUpdate->balance_version;
                $newVersion = $oldVersion + 1;

                // Обновляем баланс
                $updated = User::where('id', $userForUpdate->id)
                    ->where('balance_version', $oldVersion)
                    ->update([
                        'available_balance' => $newBalance,
                        'balance_version' => $newVersion
                    ]);

                if (!$updated) {
                    throw new \Exception('Не удалось обновить баланс');
                }

                Log::info('BalanceService: Баланс пополнен', [
                    'user_id' => $userForUpdate->id,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'added_amount' => $amount,
                    'description' => $description,
                    'old_version' => $oldVersion,
                    'new_version' => $newVersion
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('BalanceService: Ошибка пополнения баланса', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }
}
