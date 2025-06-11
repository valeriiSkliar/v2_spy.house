<?php

namespace App\Finance\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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
        $payment_method = match ($paymentData['payment_method']) {
            'USDT' => 'USDT_TRC20',
            'PAY2.HOUSE' => 'PAY2_HOUSE',
            'CARDS' => 'CARDS',
            default => 'ALL',
        };

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
            'return_url' => $paymentData['return_url'] ?? $this->config['return_url'],
            'cancel_url' => $paymentData['cancel_url'] ?? $this->config['cancel_url'],
            'payer_email' => $paymentData['payer_email'] ?? null,
            'deadline_seconds' => $this->config['payment_deadline_seconds'],
            'payment_method' => $payment_method,
            'handling_fee' => $paymentData['handling_fee'] ?? 0,
        ];

        // Добавляем API ключ (требуется в обоих режимах)
        $requestData['api_key'] = $this->config['test_mode']
            ? $this->config['test_api_key']
            : $this->config['api_key'];

        // Создаем JWT токен для данных (исключая sign_token)
        $dataForJWT = $requestData;
        unset($dataForJWT['sign_token']); // Исключаем sign_token из JWT payload

        $requestData['sign_token'] = $this->createSignToken($dataForJWT);

        Log::info('Pay2Service: Создание платежа', [
            'external_number' => $requestData['external_number'],
            'amount' => $requestData['amount'],
            'api_url' => $apiUrl,
            'merchant_id' => $this->config['merchant_id'],
            'test_mode' => $this->config['test_mode']
        ]);

        try {
            $response = Http::timeout(30)->post($apiUrl . '/api/create_payment', $requestData);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('Pay2Service: Получен ответ от API', $result);

                // Проверяем статус в ответе API
                if (isset($result['status']) && $result['status'] === 'error') {
                    Log::error('Pay2Service: API вернул ошибку', $result);
                    return [
                        'success' => false,
                        'error' => 'API Error: ' . ($result['msg'] ?? $result['code'] ?? 'Unknown error')
                    ];
                }

                Log::info('Pay2Service: Платеж создан успешно', $result);
                return [
                    'success' => true,
                    'data' => $result
                ];
            } else {
                Log::error('Pay2Service: Ошибка HTTP запроса', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return [
                    'success' => false,
                    'error' => 'HTTP Error ' . $response->status() . ': ' . $response->body()
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
     * Создание JWT токена для аутентификации запросов
     *
     * @param array $data
     * @return string
     */
    protected function createSignToken(array $data)
    {
        $privateKeyPath = $this->config['private_key_path'];

        if (!file_exists($privateKeyPath)) {
            throw new \Exception("Private key file not found: {$privateKeyPath}");
        }

        $privateKey = file_get_contents($privateKeyPath);
        $currentTime = time();

        $payload = [
            'iss' => $this->config['key_id'], // YOUR_KEY_ID
            'iat' => $currentTime,
            'data' => $data
        ];

        return JWT::encode($payload, openssl_pkey_get_private($privateKey), 'RS256');
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
