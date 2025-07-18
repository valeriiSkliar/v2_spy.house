<?php

namespace App\Finance\Services;

use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\PaymentType;
use App\Finance\Models\Payment;
use App\Finance\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BalanceService
{
    /**
     * Проверить достаточность средств на балансе пользователя
     */
    public function hasInsufficientBalance(User $user, float $amount): bool
    {
        return $user->available_balance < $amount;
    }

    /**
     * Оплатить подписку с баланса пользователя
     */
    public function processSubscriptionPaymentFromBalance(User $user, Subscription $subscription, string $billingType = 'month', ?string $idempotencyKey = null): array
    {
        // Генерируем идемпотентный ключ если не передан
        if (!$idempotencyKey) {
            $idempotencyKey = Str::uuid();
        }

        // Проверяем на дублирование платежа по idempotency_key
        $existingPayment = Payment::where('idempotency_key', $idempotencyKey)
            ->where('user_id', $user->id)
            ->first();

        if ($existingPayment) {
            Log::warning('BalanceService: Обнаружен дубликат платежа по idempotency_key', [
                'user_id' => $user->id,
                'idempotency_key' => $idempotencyKey,
                'existing_payment_id' => $existingPayment->id,
                'existing_payment_status' => $existingPayment->status,
            ]);

            return [
                'success' => false,
                'error' => 'Платеж с таким идентификатором уже обработан',
                'payment_id' => $existingPayment->id,
            ];
        }

        // Проверяем на недавние платежи (защита от двойных кликов)
        $recentPayment = Payment::where('user_id', $user->id)
            ->where('subscription_id', $subscription->id)
            ->where('payment_method', PaymentMethod::USER_BALANCE)
            ->where('created_at', '>=', now()->subSeconds(10))
            ->where('status', '!=', PaymentStatus::FAILED)
            ->first();

        if ($recentPayment) {
            Log::warning('BalanceService: Обнаружен недавний платеж', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'recent_payment_id' => $recentPayment->id,
                'recent_payment_time' => $recentPayment->created_at,
                'seconds_ago' => $recentPayment->created_at->diffInSeconds(now()),
            ]);

            return [
                'success' => false,
                'error' => 'Платеж уже обрабатывается. Пожалуйста, подождите.',
                'payment_id' => $recentPayment->id,
            ];
        }

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
            'current_balance' => $user->available_balance,
            'idempotency_key' => $idempotencyKey,
        ]);

        // Выполняем операцию в транзакции с optimistic locking
        try {
            return DB::transaction(function () use ($user, $subscription, $amount, $billingType, $idempotencyKey) {
                // Перезагружаем пользователя с блокировкой для чтения
                $userForUpdate = User::where('id', $user->id)
                    ->where('balance_version', $user->balance_version)
                    ->lockForUpdate()
                    ->first();

                if (! $userForUpdate) {
                    throw new \Exception('Баланс был изменен другой операцией. Повторите попытку.');
                }

                // Проверяем достаточность средств после получения блокировки
                if ($this->hasInsufficientBalance($userForUpdate, $amount)) {
                    Log::warning('BalanceService: Недостаточно средств на балансе', [
                        'user_id' => $userForUpdate->id,
                        'required_amount' => $amount,
                        'available_balance' => $userForUpdate->available_balance,
                    ]);

                    throw new \Exception('Недостаточно средств на балансе. Требуется: $' . number_format($amount, 2) . ', доступно: $' . number_format($userForUpdate->available_balance, 2));
                }

                // Создаем платеж с переданным idempotency_key
                $payment = $this->createBalancePayment($userForUpdate, $subscription, $amount, $billingType, $idempotencyKey);

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
                    'new_balance' => $userForUpdate->available_balance,
                ]);

                return [
                    'success' => true,
                    'payment' => $payment,
                    'message' => 'Подписка успешно оплачена с баланса',
                ];
            });
        } catch (\Exception $e) {
            Log::error('BalanceService: Ошибка при обработке платежа с баланса', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Создать запись о платеже с баланса
     */
    protected function createBalancePayment(User $user, Subscription $subscription, float $amount, string $billingType, string $idempotencyKey): Payment
    {
        return Payment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
            'subscription_id' => $subscription->id,
            'payment_method' => PaymentMethod::USER_BALANCE,
            'status' => PaymentStatus::PENDING,
            'external_number' => 'TN' . $user->id . $subscription->id . time(),
            'invoice_number' => 'IN' . strtoupper(Str::random(10)),
            'webhook_token' => Str::random(64),
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    /**
     * Списать средства с баланса пользователя (с optimistic locking)
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
                'balance_version' => $newVersion,
            ]);

        if (! $updated) {
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
            'new_version' => $newVersion,
        ]);

        return true;
    }

    /**
     * Активировать подписку пользователя
     */
    protected function activateSubscription(User $user, Subscription $subscription, string $billingType): void
    {
        $isRenewal = $this->isRenewal($user, $subscription);
        $compensatedTime = 0;

        // ВАЖНО: Рассчитываем компенсацию времени ПЕРЕД изменением подписки пользователя
        if (! $isRenewal && $user->subscription_id && ! $subscription->isEnterprise()) {
            Log::info('BalanceService: Проверка условий для компенсации времени', [
                'user_id' => $user->id,
                'has_subscription_id' => (bool) $user->subscription_id,
                'subscription_id' => $user->subscription_id,
                'is_enterprise' => $subscription->isEnterprise(),
                'subscription_name' => $subscription->name,
            ]);

            Log::info('BalanceService: Начинаем расчет компенсации времени', [
                'user_id' => $user->id,
                'current_subscription_id' => $user->subscription_id,
                'new_subscription_id' => $subscription->id,
            ]);

            $compensatedTime = $this->calculateTimeCompensation($user, $subscription);

            Log::info('BalanceService: Результат расчета компенсации', [
                'user_id' => $user->id,
                'compensated_time' => $compensatedTime,
            ]);
        } else {
            Log::info('BalanceService: Компенсация времени пропущена', [
                'user_id' => $user->id,
                'is_renewal' => $isRenewal,
                'has_subscription_id' => (bool) $user->subscription_id,
                'is_enterprise' => $subscription->isEnterprise(),
                'reason' => $isRenewal ? 'renewal' : (! $user->subscription_id ? 'no_current_subscription' : 'enterprise_tariff'),
            ]);
        }

        // Проверяем является ли пользователь в триале
        $wasInTrial = $user->isTrialPeriod();

        // Определяем время активации
        if ($isRenewal) {
            // Продление: добавляем к существующему времени окончания
            $startTime = $user->subscription_time_end ?? now();
            $endTime = $billingType === 'year'
                ? $startTime->copy()->addYear()
                : $startTime->copy()->addMonth();
        } else {
            // Новая подписка или апгрейд: начинаем с текущего момента
            // ВАЖНО: Если пользователь был в триале, время подписки начинается с момента покупки
            $startTime = now();
            $endTime = $billingType === 'year'
                ? $startTime->copy()->addYear()
                : $startTime->copy()->addMonth();

            // Добавляем компенсированное время только если пользователь НЕ был в триале
            // Время триала НЕ должно прибавляться к общему сроку подписки
            if ($compensatedTime > 0 && !$wasInTrial) {
                $endTime->addSeconds($compensatedTime);
                Log::info('BalanceService: Применена компенсация времени', [
                    'user_id' => $user->id,
                    'compensated_seconds' => $compensatedTime,
                    'compensated_days' => round($compensatedTime / 86400, 2),
                    'new_end_time' => $endTime->toDateTimeString(),
                ]);
            } elseif ($wasInTrial) {
                Log::info('BalanceService: Компенсация времени пропущена - пользователь был в триале', [
                    'user_id' => $user->id,
                    'was_in_trial' => $wasInTrial,
                    'trial_end_time' => $user->subscription_time_end ? $user->subscription_time_end->toDateTimeString() : null,
                ]);
            }
        }

        // Обновляем подписку пользователя
        $updateData = [
            'subscription_id' => $subscription->id,
            'subscription_time_start' => $startTime,
            'subscription_time_end' => $endTime,
            'subscription_is_expired' => false,
            'queued_subscription_id' => null,
        ];

        // Сбрасываем флаг триала при покупке подписки
        if ($wasInTrial) {
            $updateData['is_trial_period'] = false;
            Log::info('BalanceService: Сброс флага триала при активации подписки', [
                'user_id' => $user->id,
                'was_in_trial' => $wasInTrial,
            ]);
        }

        $user->update($updateData);

        Log::info('BalanceService: Подписка активирована', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'subscription_name' => $subscription->name,
            'billing_type' => $billingType,
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $endTime->toDateTimeString(),
            'is_renewal' => $isRenewal,
            'compensated_time_seconds' => $compensatedTime,
            'was_in_trial' => $wasInTrial,
            'trial_flag_reset' => $wasInTrial,
        ]);
    }

    /**
     * Рассчитать компенсацию времени при апгрейде тарифа
     *
     * @return int Компенсированное время в секундах
     */
    protected function calculateTimeCompensation(User $user, Subscription $newSubscription): int
    {
        // Принудительно перезагружаем пользователя из базы для актуальных данных
        $user->refresh();

        Log::info('BalanceService: Вход в calculateTimeCompensation', [
            'user_id' => $user->id,
            'user_subscription_id' => $user->subscription_id,
            'user_subscription_time_end' => $user->subscription_time_end ? $user->subscription_time_end->toDateTimeString() : null,
            'new_subscription_id' => $newSubscription->id,
            'new_subscription_name' => $newSubscription->name,
        ]);

        // Проверяем наличие текущей подписки
        if (! $user->subscription_id || ! $user->subscription_time_end) {
            Log::info('BalanceService: Компенсация пропущена - нет текущей подписки или времени окончания', [
                'user_id' => $user->id,
                'has_subscription_id' => (bool) $user->subscription_id,
                'has_subscription_time_end' => (bool) $user->subscription_time_end,
            ]);

            return 0;
        }

        // Не рассчитываем компенсацию если пользователь в триале
        if ($user->isTrialPeriod()) {
            Log::info('BalanceService: Компенсация пропущена - пользователь в триале', [
                'user_id' => $user->id,
                'trial_end_time' => $user->subscription_time_end->toDateTimeString(),
            ]);

            return 0;
        }

        $currentSubscription = $user->subscription;
        if (! $currentSubscription) {
            Log::info('BalanceService: Компенсация пропущена - не удалось загрузить модель текущей подписки', [
                'user_id' => $user->id,
                'subscription_id' => $user->subscription_id,
            ]);

            return 0;
        }

        // Проверяем что это апгрейд (не понижение)
        $isHigherTier = $newSubscription->isHigherTierThan($currentSubscription);
        Log::info('BalanceService: Проверка типа смены тарифа', [
            'user_id' => $user->id,
            'current_subscription' => $currentSubscription->name,
            'new_subscription' => $newSubscription->name,
            'is_higher_tier' => $isHigherTier,
        ]);

        if (! $isHigherTier) {
            Log::info('BalanceService: Пропуск компенсации - не апгрейд', [
                'user_id' => $user->id,
                'current_subscription' => $currentSubscription->name,
                'new_subscription' => $newSubscription->name,
            ]);

            return 0;
        }

        // Вычисляем оставшееся время текущей подписки
        $now = now();
        $timeLeft = $now->diffInSeconds($user->subscription_time_end, false);

        Log::info('BalanceService: Расчет оставшегося времени', [
            'user_id' => $user->id,
            'now' => $now->toDateTimeString(),
            'subscription_end' => $user->subscription_time_end->toDateTimeString(),
            'time_left_seconds' => $timeLeft,
            'time_left_days' => round($timeLeft / 86400, 2),
        ]);

        if ($timeLeft <= 0) {
            Log::info('BalanceService: Пропуск компенсации - нет оставшегося времени', [
                'user_id' => $user->id,
                'time_left' => $timeLeft,
            ]);

            return 0;
        }

        // Используем единую логику расчета компенсации из модели Subscription
        Log::info('BalanceService: Вызов расчета компенсации через модель Subscription', [
            'user_id' => $user->id,
            'time_left_to_compensate' => $timeLeft,
        ]);

        $compensatedTime = $newSubscription->calculateTimeCompensation($currentSubscription, $timeLeft);

        Log::info('BalanceService: Получен результат расчета компенсации', [
            'user_id' => $user->id,
            'compensated_time' => $compensatedTime,
        ]);

        Log::info('BalanceService: Расчет компенсации времени', [
            'user_id' => $user->id,
            'current_subscription' => $currentSubscription->name,
            'new_subscription' => $newSubscription->name,
            'time_left_seconds' => $timeLeft,
            'time_left_days' => round($timeLeft / 86400, 2),
            'compensated_time_seconds' => $compensatedTime,
            'compensated_time_days' => round($compensatedTime / 86400, 2),
        ]);

        return $compensatedTime;
    }

    /**
     * Проверить является ли это продлением текущей подписки
     */
    protected function isRenewal(User $user, Subscription $subscription): bool
    {
        return $user->subscription_id === $subscription->id
            && $user->subscription_time_end
            && $user->subscription_time_end > now();
    }

    /**
     * Пополнить баланс пользователя (для будущего использования)
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

                if (! $userForUpdate) {
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
                        'balance_version' => $newVersion,
                    ]);

                if (! $updated) {
                    throw new \Exception('Не удалось обновить баланс');
                }

                Log::info('BalanceService: Баланс пополнен', [
                    'user_id' => $userForUpdate->id,
                    'old_balance' => $oldBalance,
                    'new_balance' => $newBalance,
                    'added_amount' => $amount,
                    'description' => $description,
                    'old_version' => $oldVersion,
                    'new_version' => $newVersion,
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('BalanceService: Ошибка пополнения баланса', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Активировать подписку пользователя (публичный метод для webhook'ов)
     */
    public function activateSubscriptionPublic(User $user, Subscription $subscription, string $billingType): void
    {
        $this->activateSubscription($user, $subscription, $billingType);
    }
}
