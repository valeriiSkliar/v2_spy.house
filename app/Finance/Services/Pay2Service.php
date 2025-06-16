<?php

namespace App\Finance\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            'test_mode' => $this->config['test_mode'],
        ]);

        try {
            $response = Http::timeout(30)->post($apiUrl.'/api/create_payment', $requestData);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('Pay2Service: Получен ответ от API', $result);

                // Проверяем статус в ответе API
                if (isset($result['status']) && $result['status'] === 'error') {
                    Log::error('Pay2Service: API вернул ошибку', $result);

                    return [
                        'success' => false,
                        'error' => 'API Error: '.($result['msg'] ?? $result['code'] ?? 'Unknown error'),
                    ];
                }

                Log::info('Pay2Service: Платеж создан успешно', $result);

                return [
                    'success' => true,
                    'data' => $result,
                ];
            } else {
                Log::error('Pay2Service: Ошибка HTTP запроса', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error' => 'HTTP Error '.$response->status().': '.$response->body(),
                ];
            }
        } catch (\Exception $e) {
            Log::error('Pay2Service: Исключение при создании платежа', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Payment creation error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Получение информации о платеже
     *
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
            $response = Http::timeout(30)->post($apiUrl.'/api/show_payment_details', $requestData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to get payment details: '.$response->body(),
                ];
            }
        } catch (\Exception $e) {
            Log::error('Pay2Service: Ошибка получения данных платежа', [
                'invoice_number' => $invoiceNumber,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Error getting payment details: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Создание JWT токена для аутентификации запросов
     *
     * @return string
     */
    protected function createSignToken(array $data)
    {
        $privateKeyPath = $this->config['private_key_path'];

        if (! file_exists($privateKeyPath)) {
            throw new \Exception("Private key file not found: {$privateKeyPath}");
        }

        $privateKey = file_get_contents($privateKeyPath);
        $currentTime = time();

        $payload = [
            'iss' => $this->config['key_id'], // YOUR_KEY_ID
            'iat' => $currentTime,
            'data' => $data,
        ];

        return JWT::encode($payload, openssl_pkey_get_private($privateKey), 'RS256');
    }

    /**
     * Проверка webhook подписи Pay2.House
     * Официальная документация: https://pay2.house/docs/api (Webhook Events)
     *
     * @return bool
     */
    public function verifyWebhookSignature(string $signature, array $data)
    {
        try {
            // В тестовом режиме используем тестовый API ключ
            $secretKey = $this->config['test_mode']
                ? $this->config['test_api_key']
                : $this->config['api_key'];

            Log::info('Pay2Service: Проверка webhook подписи', [
                'test_mode' => $this->config['test_mode'],
                'signature' => substr($signature, 0, 100).'...',
                'data' => $data,
            ]);

            // Декодируем base64
            $decoded_data = base64_decode($signature);
            if ($decoded_data === false) {
                Log::error('Pay2Service: Ошибка декодирования base64 подписи');

                return false;
            }

            // Разделяем на части: iv|signature|encrypted_data
            $parts = explode('|', $decoded_data);
            if (count($parts) !== 3) {
                Log::error('Pay2Service: Неверный формат подписи webhook', [
                    'parts_count' => count($parts),
                    'signature' => substr($signature, 0, 100).'...',
                ]);

                return false;
            }

            [$iv, $hmac_signature, $encrypted_data] = $parts;

            // Вычисляем HMAC подпись для проверки целостности (по официальной документации)
            $hmac_data = $iv.'|'.$encrypted_data;
            $calculated_signature = hash_hmac('sha256', $hmac_data, $secretKey);

            // Проверяем подпись
            if (! hash_equals($calculated_signature, $hmac_signature)) {
                Log::warning('Pay2Service: Подпись webhook не совпадает', [
                    'calculated' => $calculated_signature,
                    'received' => $hmac_signature,
                    'hmac_data' => substr($hmac_data, 0, 100).'...',
                ]);

                return false;
            }

            // Расшифровываем данные используя AES-256-CBC (по ОФИЦИАЛЬНОЙ документации)
            $decrypted_data = openssl_decrypt(
                base64_decode($encrypted_data),
                'AES-256-CBC',
                hex2bin(hash('sha256', $secretKey)),
                0,
                hex2bin(bin2hex(hex2bin($iv)))  // Точно как в официальном коде Pay2.House!
            );

            if ($decrypted_data === false) {
                Log::error('Pay2Service: Ошибка расшифровки AES-256-CBC');

                return false;
            }

            // Парсим JSON данные
            $webhook_data = json_decode($decrypted_data, true);
            if ($webhook_data === null) {
                Log::error('Pay2Service: Ошибка парсинга JSON из расшифрованных данных', [
                    'decrypted_data' => $decrypted_data,
                ]);

                return false;
            }

            Log::info('Pay2Service: Webhook подпись успешно проверена', [
                'webhook_data' => $webhook_data,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Pay2Service: Исключение при проверке webhook подписи', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Генерация уникального номера заказа
     *
     * @return string
     */
    public function generateExternalNumber(int $userId, int $tariffId)
    {
        // Префикс DP для депозитов, TN для тарифов/подписок
        $prefix = 'TN';

        return $prefix.$userId.$tariffId.time();
    }

    /**
     *  decrypt_webhook из final_webhook_validator.php
     */
    public function decrypt_webhook($data = null, $secret_key = null)
    {
        if (empty($data) || empty($secret_key)) {
            return false;
        }

        // Шаг 1: Декодирование Base64
        $decoded_data = base64_decode($data);
        if ($decoded_data === false) {
            return false;
        }

        // Шаг 2: Разделение на части iv|signature|encrypted_data
        $parts = explode('|', $decoded_data);
        if (count($parts) !== 3) {
            return false;
        }

        [$iv, $signature, $encrypted_data] = $parts;

        // Шаг 3: Проверка HMAC подписи
        $calculated_signature = hash_hmac('sha256', $iv.'|'.$encrypted_data, $secret_key);
        if (! hash_equals($calculated_signature, $signature)) {
            return false;
        }

        // Шаг 4: Расшифровка AES-256-CBC данных
        $decoded_encrypted_data = openssl_decrypt(
            base64_decode($encrypted_data),
            'AES-256-CBC',
            hex2bin(hash('sha256', $secret_key)),
            0,
            hex2bin(bin2hex(hex2bin($iv)))
        );

        if ($decoded_encrypted_data !== false) {
            return $decoded_encrypted_data;
        }

        return false;
    }

    /**
     * ТОЧНАЯ КОПИЯ validate_pay2_webhook из final_webhook_validator.php
     */
    public function validate_pay2_webhook($signature, $payload, $api_key, $debug = false)
    {
        $result = [
            'valid' => false,
            'payload_data' => null,
            'webhook_data' => null,
            'error' => null,
        ];

        try {
            if ($debug) {
                echo "🔍 Начинаю валидацию webhook Pay2.House\n";
                echo '📝 Подпись: '.substr($signature, 0, 50)."...\n";
                echo '📦 Payload: '.json_encode($payload)."\n";
                echo '🔑 API ключ: '.substr($api_key, 0, 20)."...\n\n";
            }

            // Проверяем наличие необходимых данных
            if (empty($signature)) {
                $result['error'] = 'Отсутствует подпись Pay2-House-Signature';

                return $result;
            }

            if (empty($api_key)) {
                $result['error'] = 'Отсутствует API ключ';

                return $result;
            }

            // Расшифровываем подпись
            if ($debug) {
                echo "🔐 Расшифровываю подпись...\n";
            }

            $decrypted_webhook = $this->decrypt_webhook($signature, $api_key);

            if ($decrypted_webhook === false) {
                $result['error'] = 'Не удалось расшифровать подпись webhook';
                if ($debug) {
                    echo "❌ Ошибка расшифровки подписи\n";
                }

                return $result;
            }

            if ($debug) {
                echo "✅ Подпись успешно расшифрована\n";
            }

            // Парсим JSON из подписи
            $webhook_data = json_decode($decrypted_webhook, true);
            if ($webhook_data === null) {
                $result['error'] = 'Неверный формат JSON в расшифрованной подписи';

                return $result;
            }

            if ($debug) {
                echo "📊 Данные из подписи:\n";
                foreach ($webhook_data as $key => $value) {
                    echo "  $key: $value\n";
                }
                echo "\n";
            }

            // Проверяем соответствие данных в подписи и payload
            $required_fields = ['invoice_number', 'external_number', 'amount', 'currency_code', 'status'];

            foreach ($required_fields as $field) {
                if (! isset($payload[$field]) || ! isset($webhook_data[$field])) {
                    $result['error'] = "Отсутствует обязательное поле: $field";

                    return $result;
                }

                if ($payload[$field] != $webhook_data[$field]) {
                    $result['error'] = "Несоответствие поля $field: payload={$payload[$field]}, webhook={$webhook_data[$field]}";

                    return $result;
                }
            }

            // Все проверки прошли успешно
            $result['valid'] = true;
            $result['payload_data'] = $payload;
            $result['webhook_data'] = $webhook_data;

            if ($debug) {
                echo "🎉 Webhook успешно валидирован!\n";
            }

            return $result;
        } catch (\Exception $e) {
            $result['error'] = 'Исключение при валидации: '.$e->getMessage();
            if ($debug) {
                echo '❌ Исключение: '.$e->getMessage()."\n";
            }

            return $result;
        }
    }
}
