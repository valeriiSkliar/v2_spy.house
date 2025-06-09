<?php

/**
 * Примеры использования базовой системы платежей
 * 
 * Этот файл демонстрирует основные возможности системы платежей:
 * - Создание депозитных платежей
 * - Прямая покупка подписок  
 * - Работа со статусами платежей
 */

use App\Enums\Finance\PaymentMethod;
use App\Enums\Finance\PaymentStatus;
use App\Enums\Finance\PaymentType;
use App\Finance\Models\Payment;
use App\Finance\Models\Subscription;
use App\Models\User;

// Пример 1: Создание депозитного платежа
function createDepositPayment(User $user, float $amount, PaymentMethod $method): Payment
{
    return Payment::create([
        'user_id' => $user->id,
        'amount' => $amount,
        'payment_type' => PaymentType::DEPOSIT,
        'payment_method' => $method,
        'status' => PaymentStatus::PENDING,
    ]);
}

// Пример 2: Прямая покупка подписки
function createSubscriptionPayment(User $user, Subscription $subscription, PaymentMethod $method): Payment
{
    return Payment::create([
        'user_id' => $user->id,
        'amount' => $subscription->amount,
        'payment_type' => PaymentType::DIRECT_SUBSCRIPTION,
        'subscription_id' => $subscription->id,
        'payment_method' => $method,
        'status' => PaymentStatus::PENDING,
    ]);
}

// Пример 3: Обработка успешного платежа
function processSuccessfulPayment(Payment $payment, string $transactionNumber): void
{
    $payment->transaction_number = $transactionNumber;
    $payment->markAsSuccessful();

    echo "Платеж {$payment->getFormattedAmount()} успешно обработан\n";
}

// Пример 4: Получение платежей пользователя
function getUserPaymentsSummary(User $user): array
{
    return [
        'total_payments' => $user->payments()->count(),
        'successful_deposits' => $user->depositPayments()->successful()->sum('amount'),
        'pending_payments' => $user->pendingPayments()->count(),
        'subscription_payments' => $user->subscriptionPayments()->successful()->count(),
    ];
}

// Пример 5: Получение статистики по платежам
function getPaymentStatistics(): array
{
    return [
        'total_amount' => Payment::successful()->sum('amount'),
        'pending_count' => Payment::pending()->count(),
        'failed_count' => Payment::failed()->count(),
        'deposit_amount' => Payment::deposits()->successful()->sum('amount'),
        'subscription_revenue' => Payment::subscriptions()->successful()->sum('amount'),
    ];
}

// Пример использования:
/*
$user = User::find(1);
$subscription = Subscription::where('status', 'active')->first();

// Создаем депозитный платеж
$depositPayment = createDepositPayment($user, 100.00, PaymentMethod::USDT);

// Создаем платеж за подписку
$subscriptionPayment = createSubscriptionPayment($user, $subscription, PaymentMethod::PAY2_HOUSE);

// Обрабатываем успешный платеж
processSuccessfulPayment($depositPayment, 'TXN123456789');

// Получаем сводку по платежам пользователя
$userSummary = getUserPaymentsSummary($user);

// Получаем общую статистику
$statistics = getPaymentStatistics();
*/
