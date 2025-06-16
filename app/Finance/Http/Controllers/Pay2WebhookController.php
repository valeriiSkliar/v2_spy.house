<?php

namespace App\Finance\Http\Controllers;

use App\Finance\Models\Payment;
use App\Finance\Services\Pay2Service;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Pay2WebhookController extends Controller
{
    protected $pay2Service;

    public function __construct(Pay2Service $pay2Service)
    {
        $this->pay2Service = $pay2Service;
    }

    /**
     * Handle Pay2.House webhook notifications
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        Log::info('Pay2WebhookController: Получен webhook', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        // Получаем подпись из заголовков
        $signature = $request->header('Pay2-House-Signature');

        if (! $signature) {
            Log::warning('Pay2WebhookController: Отсутствует подпись в webhook');

            return response()->json(['error' => 'Missing signature'], 400);
        }

        // Получаем данные webhook
        $webhookData = $request->all();

        // Проверяем подпись (в тестовом режиме можем пропустить проверку)
        if (! config('pay2.test_mode')) {
            if (! $this->pay2Service->verifyWebhookSignature($signature, $webhookData)) {
                Log::warning('Pay2WebhookController: Некорректная подпись webhook', [
                    'signature' => $signature,
                    'data' => $webhookData,
                ]);

                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        // Обрабатываем webhook данные
        $this->processWebhookData($webhookData);

        Log::info('Pay2WebhookController: Webhook обработан успешно', [
            'invoice_number' => $webhookData['invoice_number'] ?? 'unknown',
            'status' => $webhookData['status'] ?? 'unknown',
        ]);

        return response()->json(['status' => 'success']);
    }

    /**
     * Process webhook data
     *
     * @return void
     */
    protected function processWebhookData(array $data)
    {
        $invoiceNumber = $data['invoice_number'] ?? null;
        $externalNumber = $data['external_number'] ?? null;
        $status = $data['status'] ?? null;
        $amount = $data['amount'] ?? null;

        if (! $invoiceNumber || ! $status) {
            Log::warning('Pay2WebhookController: Неполные данные webhook', $data);

            return;
        }

        Log::info('Pay2WebhookController: Обработка платежа', [
            'invoice_number' => $invoiceNumber,
            'external_number' => $externalNumber,
            'status' => $status,
            'amount' => $amount,
        ]);

        switch ($status) {
            case 'paid':
                $this->handlePaidPayment($data);
                break;
            case 'cancelled':
                $this->handleCancelledPayment($data);
                break;
            case 'error':
                $this->handleErrorPayment($data);
                break;
            default:
                Log::info('Pay2WebhookController: Неизвестный статус платежа', [
                    'status' => $status,
                    'data' => $data,
                ]);
        }
    }

    /**
     * Handle paid payment
     *
     * @return void
     */
    protected function handlePaidPayment(array $data)
    {
        Log::info('Pay2WebhookController: Обработка успешного платежа', $data);

        $invoiceNumber = $data['invoice_number'];

        // Находим платеж по invoice_number
        $payment = Payment::where('invoice_number', $invoiceNumber)->first();

        if (! $payment) {
            Log::warning('Pay2WebhookController: Платеж не найден', [
                'invoice_number' => $invoiceNumber,
            ]);

            return;
        }

        // Обновляем статус платежа
        $payment->markAsSuccessful();

        Log::info('Pay2WebhookController: Статус платежа обновлен', [
            'payment_id' => $payment->id,
            'user_id' => $payment->user_id,
            'payment_type' => $payment->payment_type->value,
            'subscription_id' => $payment->subscription_id,
            'amount' => $payment->amount,
        ]);

        // Обрабатываем в зависимости от типа платежа
        if ($payment->payment_type === \App\Enums\Finance\PaymentType::DEPOSIT) {
            $this->processDepositPayment($payment, $data);
        } elseif ($payment->payment_type === \App\Enums\Finance\PaymentType::DIRECT_SUBSCRIPTION) {
            $this->activateUserSubscription($payment, $data);
        }

        // TODO: Отправить уведомление пользователю
    }

    /**
     * Handle cancelled payment
     *
     * @return void
     */
    protected function handleCancelledPayment(array $data)
    {
        Log::info('Pay2WebhookController: Обработка отмененного платежа', $data);

        $invoiceNumber = $data['invoice_number'];

        // Находим платеж по invoice_number
        $payment = Payment::where('invoice_number', $invoiceNumber)->first();

        if ($payment) {
            $payment->markAsFailed();
            Log::info('Pay2WebhookController: Платеж отменен', [
                'payment_id' => $payment->id,
            ]);
        }

        // TODO: Отправить уведомление пользователю об отмене
    }

    /**
     * Handle error payment
     *
     * @return void
     */
    protected function handleErrorPayment(array $data)
    {
        Log::error('Pay2WebhookController: Ошибка платежа', $data);

        $invoiceNumber = $data['invoice_number'];

        // Находим платеж по invoice_number
        $payment = Payment::where('invoice_number', $invoiceNumber)->first();

        if ($payment) {
            $payment->markAsFailed();
            Log::error('Pay2WebhookController: Платеж завершился ошибкой', [
                'payment_id' => $payment->id,
            ]);
        }

        // TODO: Отправить уведомление пользователю об ошибке
    }

    /**
     * Process deposit payment by adding to user balance
     */
    protected function processDepositPayment(Payment $payment, array $webhookData): void
    {
        $user = $payment->user;

        if (! $user) {
            Log::warning('Pay2WebhookController: Не найден пользователь для депозита', [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
            ]);

            return;
        }

        // Добавляем сумму к балансу пользователя через BalanceService
        $balanceService = app(\App\Finance\Services\BalanceService::class);
        $result = $balanceService->addToBalance(
            $user,
            $payment->amount,
            "Пополнение баланса (платеж #{$payment->id})"
        );

        if ($result) {
            Log::info('Pay2WebhookController: Депозит успешно зачислен на баланс', [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'amount' => $payment->amount,
                'new_balance' => $user->fresh()->available_balance,
            ]);
        } else {
            Log::error('Pay2WebhookController: Ошибка зачисления депозита', [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'amount' => $payment->amount,
                'error' => 'Неизвестная ошибка',
            ]);
        }
    }

    /**
     * Activate user subscription after successful payment
     */
    protected function activateUserSubscription(Payment $payment, array $webhookData): void
    {
        $user = $payment->user;
        $subscription = $payment->subscription;

        if (! $user || ! $subscription) {
            Log::warning('Pay2WebhookController: Не удалось активировать подписку - отсутствуют данные', [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'subscription_id' => $payment->subscription_id,
            ]);

            return;
        }

        // Определяем период подписки из описания платежа
        $description = $webhookData['description'] ?? '';
        $billingType = str_contains($description, '(year)') ? 'year' : 'month';

        Log::info('Pay2WebhookController: Начало активации подписки', [
            'payment_id' => $payment->id,
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'billing_type' => $billingType,
        ]);

        try {
            // Используем BalanceService для активации подписки с поддержкой пересчета времени
            $balanceService = app(\App\Finance\Services\BalanceService::class);

            // Вызываем публичный метод активации подписки
            $balanceService->activateSubscriptionPublic($user, $subscription, $billingType);

            Log::info('Pay2WebhookController: Подписка активирована через BalanceService', [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'subscription_name' => $subscription->name,
                'billing_type' => $billingType,
            ]);
        } catch (\Exception $e) {
            Log::error('Pay2WebhookController: Ошибка активации подписки', [
                'payment_id' => $payment->id,
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
