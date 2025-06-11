<?php

namespace App\Finance\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class Pay2Service
{
    protected $config;

    public function __construct()
    {
        $this->config = Config::get('pay2');
    }

    /**
     * Создание платежа в системе Pay2.House
     *
     * @param array $paymentData
     * @return array
     */
    public function createPayment(array $paymentData)
    {
        $apiUrl = $this->config['test_mode']
            ? $this->config['test_api_url']
            : $this->config['api_url'];

        $requestData = [
            'external_number' => $paymentData['external_number'],
            'amount' => $paymentData['amount'],
            'currency_code' => $paymentData['currency_code'] ?? $this->config['default_currency'],
            'merchant_id' => $this->config['test_mode']
                ? $this->config['test_merchant_id']
                : $this->config['merchant_id'],
            'description' => $paymentData['description'],
            'api_key' => $this->config['test_mode']
                ? $this->config['test_api_key']
                : $this->config['api_key'],
            'return_url' => $paymentData['return_url'] ?? $this->config['return_url'],
            'cancel_url' => $paymentData['cancel_url'] ?? $this->config['cancel_url'],
            'payer_email' => $paymentData['payer_email'] ?? null,
            'deadline_seconds' => $this->config['payment_deadline_seconds'],
            'payment_method' => $paymentData['payment_method'] ?? 'ALL',
            'handling_fee' => $paymentData['handling_fee'] ?? 0,
        ];

        // Добавляем подпись для данных
        $requestData['sign_token'] = $this->createSignToken($requestData);

        Log::info('Pay2Service: Создание платежа', [
            'external_number' => $requestData['external_number'],
            'amount' => $requestData['amount'],
            'api_url' => $apiUrl,
            'test_mode' => $this->config['test_mode']
        ]);

        try {
            $response = Http::timeout(30)->post($apiUrl . '/api/create_payment', $requestData);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('Pay2Service: Платеж создан успешно', $result);
                return [
                    'success' => true,
                    'data' => $result
                ];
            } else {
                Log::error('Pay2Service: Ошибка создания платежа', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [
                    'success' => false,
                    'error' => 'Payment creation failed: ' . $response->body()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Pay2Service: Исключение при создании платежа', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => 'Payment creation error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Получение информации о платеже
     *
     * @param string $invoiceNumber
     * @return array
     */
    public function getPaymentDetails(string $invoiceNumber)
    {
        $apiUrl = $this->config['test_mode']
            ? $this->config['test_api_url']
            : $this->config['api_url'];

        $requestData = [
            'invoice_number' => $invoiceNumber,
            'merchant_id' => $this->config['test_mode']
                ? $this->config['test_merchant_id']
                : $this->config['merchant_id'],
            'api_key' => $this->config['test_mode']
                ? $this->config['test_api_key']
                : $this->config['api_key'],
        ];

        try {
            $response = Http::timeout(30)->post($apiUrl . '/api/show_payment_details', $requestData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to get payment details: ' . $response->body()
                ];
            }
        } catch (\Exception $e) {
            Log::error('Pay2Service: Ошибка получения данных платежа', [
                'invoice_number' => $invoiceNumber,
                'message' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => 'Error getting payment details: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Создание токена подписи для данных
     *
     * @param array $data
     * @return string
     */
    protected function createSignToken(array $data)
    {
        $secretKey = $this->config['test_mode']
            ? $this->config['test_secret_key']
            : $this->config['api_key']; // В продакшене используем api_key

        // Исключаем sign_token из подписи
        unset($data['sign_token']);

        $jsonData = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return hash_hmac('sha256', $jsonData, $secretKey);
    }

    /**
     * Проверка webhook подписи
     *
     * @param string $signature
     * @param array $data
     * @return bool
     */
    public function verifyWebhookSignature(string $signature, array $data)
    {
        $secretKey = $this->config['test_mode']
            ? $this->config['test_secret_key']
            : $this->config['api_key'];

        $expectedSignature = $this->createSignToken($data);

        return hash_equals($signature, $expectedSignature);
    }

    /**
     * Генерация уникального номера заказа
     *
     * @param int $userId
     * @param int $tariffId
     * @return string
     */
    public function generateExternalNumber(int $userId, int $tariffId)
    {
        // Префикс DP для депозитов, TN для тарифов/подписок
        $prefix = $tariffId === 0 ? 'DP' : 'TN';
        return $prefix . $userId . $tariffId . time();
    }
}
