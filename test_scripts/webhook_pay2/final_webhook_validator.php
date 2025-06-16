<?php

/**
 * ФИНАЛЬНЫЙ РАБОЧИЙ ВАЛИДАТОР WEBHOOK PAY2.HOUSE
 * 
 * ✅ Использует официальный алгоритм из документации
 * ✅ Готов к использованию в продакшене
 * ✅ Подробное логирование для отладки
 */

/**
 * Официальная функция декодирования webhook из документации Pay2.House
 * 
 * @param string|null $data Base64 закодированная подпись из заголовка Pay2-House-Signature
 * @param string|null $secret_key API ключ мерчанта
 * @return string|false Расшифрованные JSON данные или false при ошибке
 */
function decrypt_webhook($data = NULL, $secret_key = NULL)
{
    if (empty($data) || empty($secret_key)) {
        return FALSE;
    }

    // Шаг 1: Декодирование Base64
    $decoded_data = base64_decode($data);
    if ($decoded_data === FALSE) {
        return FALSE;
    }

    // Шаг 2: Разделение на части iv|signature|encrypted_data
    $parts = explode('|', $decoded_data);
    if (count($parts) !== 3) {
        return FALSE;
    }

    list($iv, $signature, $encrypted_data) = $parts;

    // Шаг 3: Проверка HMAC подписи
    $calculated_signature = hash_hmac('sha256', $iv . '|' . $encrypted_data, $secret_key);
    if (!hash_equals($calculated_signature, $signature)) {
        return FALSE;
    }

    // Шаг 4: Расшифровка AES-256-CBC данных
    $decoded_encrypted_data = openssl_decrypt(
        base64_decode($encrypted_data),
        'AES-256-CBC',
        hex2bin(hash('sha256', $secret_key)),
        0,
        hex2bin(bin2hex(hex2bin($iv)))  // Точно как в официальном коде!
    );

    if ($decoded_encrypted_data !== FALSE) {
        return $decoded_encrypted_data;
    }

    return FALSE;
}

/**
 * Полный валидатор webhook с логированием
 * 
 * @param string $signature Подпись из заголовка Pay2-House-Signature
 * @param array $payload Данные из тела запроса
 * @param string $api_key API ключ мерчанта
 * @param bool $debug Включить отладочный вывод
 * @return array Результат валидации
 */
function validate_pay2_webhook($signature, $payload, $api_key, $debug = false)
{
    $result = [
        'valid' => false,
        'payload_data' => null,
        'webhook_data' => null,
        'error' => null
    ];

    try {
        if ($debug) {
            echo "🔍 Начинаю валидацию webhook Pay2.House\n";
            echo "📝 Подпись: " . substr($signature, 0, 50) . "...\n";
            echo "📦 Payload: " . json_encode($payload) . "\n";
            echo "🔑 API ключ: " . substr($api_key, 0, 20) . "...\n\n";
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
        if ($debug) echo "🔐 Расшифровываю подпись...\n";

        $decrypted_webhook = decrypt_webhook($signature, $api_key);

        if ($decrypted_webhook === FALSE) {
            $result['error'] = 'Не удалось расшифровать подпись webhook';
            if ($debug) echo "❌ Ошибка расшифровки подписи\n";
            return $result;
        }

        if ($debug) echo "✅ Подпись успешно расшифрована\n";

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
            if (!isset($payload[$field]) || !isset($webhook_data[$field])) {
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

        if ($debug) echo "🎉 Webhook успешно валидирован!\n";

        return $result;
    } catch (Exception $e) {
        $result['error'] = 'Исключение при валидации: ' . $e->getMessage();
        if ($debug) echo "❌ Исключение: " . $e->getMessage() . "\n";
        return $result;
    }
}

/**
 * Пример использования валидатора
 */
function example_webhook_usage()
{

    // Симулируем входящий webhook запрос
    $headers = [
        'Pay2-House-Signature' => 'NTM5OWVlODBiZDVhZWFiNzBmZjE4YmU3MGZjMGRlNGV8ZGYwYmY0MTI2ZTgxNzYwNjA1MjM0OTI5ODRmNzQ5NjE1YTMyOTQzNjNmMDQ3NDZmZmM2NDJlYWFmOTk0NzI4OXxZMm94YTJVd1ExWXpXbTUxT1hkUVRFVm1ORU5NTDFBMllqZFJNelp4VFhOcmJtcHliR0pDZVRRelZsTk1abFp2UW0wMVJsaDRRMFpQWm5GWmNXWlFUM0l5Y1dGRVUwbGtMM2R2TVdreVdqZElURkZQVkhNMGQwY3JVVGMyU210alVYa3ZjMGRqYTJObFpuTmFhRGRrYVZodmNITklla05aVjBwNE1IUjBVa04zU1dFdmVrWkhLMFpOVEZSTVVUTlFTRWd2WlU1WE5tMVpPVTlUZEVkVmEyWldSbXRWWWs5ek9XTnpNa2RYWkRJMWNERlVVRVJYYUZSclpHVlBNbTlRY0VadFFXVXdkR0kwZDJJMVdFcEhURlI1Um1oQ05EQmpVMFp0Ukc5eFFXWjNOSGhhWm0wNUx6WTJhUzlFV1UxRmN6ZEVLMHRwWlZwWmJubFFVM2RsU0RGc1F6Um9ia3RGY1RkRkwyMDNiekZzVUdGYVdqTmtNRUphVUZCeGNDOVVVRGxOYVZZemNETkJTa1Y0TW5OT05XVlRjME5FU1ZoMlRqbG5OMVE0TDNNPQ=='
    ];

    $payload = [
        'invoice_number' => 'IN2212956367',
        'external_number' => 'TN121750056778',
        'amount' => 1,
        'handling_fee' => 0,
        'currency_code' => 'USD',
        'description' => 'Оплата тарифа Start (month)',
        'status' => 'paid'
    ];

    $api_key = $_ENV['PAY2_API_KEY'] ?? 'YOUR_API_KEY_HERE';

    echo "📥 Входящий webhook:\n";
    echo "🔑 Подпись: " . substr($headers['Pay2-House-Signature'], 0, 50) . "...\n";
    echo "📦 Данные: " . json_encode($payload, JSON_UNESCAPED_UNICODE) . "\n\n";

    // Валидируем webhook
    $validation_result = validate_pay2_webhook(
        $headers['Pay2-House-Signature'],
        $payload,
        $api_key,
        true  // включаем отладку
    );

    if ($validation_result['valid']) {
        echo "✅ WEBHOOK ВАЛИДИРОВАН УСПЕШНО\n";
        echo "💰 Платеж подтвержден: {$validation_result['webhook_data']['invoice_number']}\n";
        echo "💵 Сумма: {$validation_result['webhook_data']['amount']} {$validation_result['webhook_data']['currency_code']}\n";

        // Здесь ваша бизнес-логика:
        // - Обновление статуса в БД
        // - Активация услуг
        // - Отправка уведомлений

        return ['status' => 200, 'response' => 'OK'];
    } else {
        echo "❌ ОШИБКА ВАЛИДАЦИИ: {$validation_result['error']}\n";
        return ['status' => 401, 'response' => 'Invalid webhook'];
    }
}

// Если запускается как скрипт
if (php_sapi_name() === 'cli') {
    echo "🔥 ВАЛИДАТОР WEBHOOK PAY2.HOUSE\n";
    echo "==============================\n\n";

    example_webhook_usage();

    echo "\n📝 ГОТОВО К ИСПОЛЬЗОВАНИЮ!\n";
}
