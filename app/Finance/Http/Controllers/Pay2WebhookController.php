<?php

namespace App\Finance\Http\Controllers;

use App\Finance\Services\Pay2Service;
use App\Finance\Models\Payment;
use App\Models\User;
use App\Http\Controllers\Controller;
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
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        Log::info('Pay2WebhookController: Получен webhook', [
            'headers' => $request->headers->all(),
            'body' => $request->all()
        ]);

        // Получаем подпись из заголовков
        $signature = $request->header('Pay2-House-Signature');

        if (!$signature) {
            Log::warning('Pay2WebhookController: Отсутствует подпись в webhook');
            return response()->json(['error' => 'Missing signature'], 400);
        }

        // Получаем данные webhook
        $webhookData = $request->all();

        // Проверяем подпись (в тестовом режиме можем пропустить проверку)
        if (!config('pay2.test_mode')) {
            if (!$this->pay2Service->verifyWebhookSignature($signature, $webhookData)) {
                Log::warning('Pay2WebhookController: Некорректная подпись webhook', [
                    'signature' => $signature,
                    'data' => $webhookData
                ]);
                return response()->json(['error' => 'Invalid signature'], 401);
            }
        }

        // Обрабатываем webhook данные
        $this->processWebhookData($webhookData);

        Log::info('Pay2WebhookController: Webhook обработан успешно', [
            'invoice_number' => $webhookData['invoice_number'] ?? 'unknown',
            'status' => $webhookData['status'] ?? 'unknown'
        ]);

        return response()->json(['status' => 'success']);
    }

    /**
     * Process webhook data
     *
     * @param array $data
     * @return void
     */
    protected function processWebhookData(array $data)
    {
        $invoiceNumber = $data['invoice_number'] ?? null;
        $externalNumber = $data['external_number'] ?? null;
        $status = $data['status'] ?? null;
        $amount = $data['amount'] ?? null;

        if (!$invoiceNumber || !$status) {
            Log::warning('Pay2WebhookController: Неполные данные webhook', $data);
            return;
        }

        Log::info('Pay2WebhookController: Обработка платежа', [
            'invoice_number' => $invoiceNumber,
            'external_number' => $externalNumber,
            'status' => $status,
            'amount' => $amount
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
                    'data' => $data
                ]);
        }
    }

    /**
     * Handle paid payment
     *
     * @param array $data
     * @return void
     */
    protected function handlePaidPayment(array $data)
    {
        Log::info('Pay2WebhookController: Обработка успешного платежа', $data);

        $invoiceNumber = $data['invoice_number'];

        // Находим платеж по invoice_number
        $payment = Payment::where('invoice_number', $invoiceNumber)->first();

        if (!$payment) {
            Log::warning('Pay2WebhookController: Платеж не найден', [
                'invoice_number' => $invoiceNumber
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
            'amount' => $payment->amount
        ]);

        // Обрабатываем в зависимости от типа платежа
        if ($payment->isDeposit()) {
            $this->processDepositPayment($payment, $data);
        } elseif ($payment->isDirectSubscription()) {
            $this->activateUserSubscription($payment, $data);
        }

        // TODO: Отправить уведомление пользователю
    }

    /**
     * Handle cancelled payment
     *
     * @param array $data
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
                'payment_id' => $payment->id
            ]);
        }

        // TODO: Отправить уведомление пользователю об отмене
    }

    /**
     * Handle error payment
     *
     * @param array $data
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
                'payment_id' => $payment->id
            ]);
        }

        // TODO: Отправить уведомление пользователю об ошибке
    }

    /**
     * Activate user subscription after successful payment
     *
     * @param Payment $payment
     * @param array $webhookData
     * @return void
     */
    protected function activateUserSubscription(Payment $payment, array $webhookData): void
    {
        $user = $payment->user;
        $subscription = $payment->subscription;

        if (!$user || !$subscription) {
            Log::warning('Pay2WebhookController: Не удалось активировать подписку - отсутствуют данные', [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id,
                'subscription_id' => $payment->subscription_id
            ]);
            return;
        }

        // Определяем период подписки из описания платежа
        $description = $webhookData['description'] ?? '';
        $billingType = str_contains($description, '(year)') ? 'year' : 'month';

        // Рассчитываем время начала и окончания подписки
        $startTime = now();
        $endTime = $billingType === 'year'
            ? $startTime->copy()->addYear()
            : $startTime->copy()->addMonth();

        // Обновляем данные пользователя
        $user->update([
            'subscription_id' => $subscription->id,
            'subscription_time_start' => $startTime,
            'subscription_time_end' => $endTime,
            'subscription_is_expired' => false,
            'queued_subscription_id' => null, // Очищаем очередь
        ]);

        Log::info('Pay2WebhookController: Подписка активирована', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'subscription_name' => $subscription->name,
            'billing_type' => $billingType,
            'start_time' => $startTime->toDateTimeString(),
            'end_time' => $endTime->toDateTimeString(),
            'payment_id' => $payment->id
        ]);
    }

    /**
     * Process deposit payment - increase user balance
     *
     * @param Payment $payment
     * @param array $webhookData
     * @return void
     */
    protected function processDepositPayment(Payment $payment, array $webhookData): void
    {
        $user = $payment->user;

        if (!$user) {
            Log::warning('Pay2WebhookController: Не удалось обработать депозит - пользователь не найден', [
                'payment_id' => $payment->id,
                'user_id' => $payment->user_id
            ]);
            return;
        }

        // Увеличиваем баланс пользователя
        $previousBalance = $user->available_balance;
        $newBalance = $previousBalance + $payment->amount;

        $user->update([
            'available_balance' => $newBalance
        ]);

        Log::info('Pay2WebhookController: Баланс пользователя пополнен', [
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'deposit_amount' => $payment->amount,
            'previous_balance' => $previousBalance,
            'new_balance' => $newBalance,
            'invoice_number' => $payment->invoice_number
        ]);
    }
}
