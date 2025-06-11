<?php

namespace App\Http\Controllers\Api;

use App\Finance\Services\Pay2Service;
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

        // TODO: Обновить статус платежа в базе данных
        // TODO: Активировать подписку пользователя
        // TODO: Отправить уведомление пользователю

        // Парсим external_number для получения user_id и tariff_id
        $externalNumber = $data['external_number'];
        if (preg_match('/tariff_(\d+)_(\d+)_\d+/', $externalNumber, $matches)) {
            $userId = $matches[1];
            $tariffId = $matches[2];

            Log::info('Pay2WebhookController: Извлечены данные из external_number', [
                'user_id' => $userId,
                'tariff_id' => $tariffId,
                'external_number' => $externalNumber
            ]);

            // TODO: Активировать подписку для пользователя
        }
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

        // TODO: Обновить статус платежа в базе данных
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

        // TODO: Обновить статус платежа в базе данных
        // TODO: Отправить уведомление пользователю об ошибке
    }
}
