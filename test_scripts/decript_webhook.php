<?php

/**
 * Pay2.House Debug Decoder - ИСПРАВЛЕННАЯ ВЕРСИЯ по официальной документации
 */

// Ваши ключи из .env
$api_key = 'YOUR_API_KEY_HERE';
$merchant_id = 'SN2633804563';
$key_id = 'KN2508182990';

// Сигнатура из webhook
$signature = 'NTM5OWVlODBiZDVhZWFiNzBmZjE4YmU3MGZjMGRlNGV8ZGYwYmY0MTI2ZTgxNzYwNjA1MjM0OTI5ODRmNzQ5NjE1YTMyOTQzNjNmMDQ3NDZmZmM2NDJlYWFmOTk0NzI4OXxZMm94YTJVd1ExWXpXbTUxT1hkUVRFVm1ORU5NTDFBMllqZFJNelp4VFhOcmJtcHliR0pDZVRRelZsTk1abFp2UW0wMVJsaDRRMFpQWm5GWmNXWlFUM0l5Y1dGRVUwbGtMM2R2TVdreVdqZElURkZQVkhNMGQwY3JVVGMyU210alVYa3ZjMGRqYTJObFpuTmFhRGRrYVZodmNITklla05aVjBwNE1IUjBVa04zU1dFdmVrWkhLMFpOVEZSTVVUTlFTRWd2WlU1WE5tMVpPVTlUZEVkVmEyWldSbXRWWWs5ek9XTnpNa2RYWkRJMWNERlVVRVJYYUZSclpHVlBNbTlRY0VadFFXVXdkR0kwZDJJMVdFcEhURlI1Um1oQ05EQmpVMFp0Ukc5eFFXWjNOSGhhWm0wNUx6WTJhUzlFV1UxRmN6ZEVLMHRwWlZwWmJubFFVM2RsU0RGc1F6Um9ia3RGY1RkRkwyMDNiekZzVUdGYVdqTmtNRUphVUZCeGNDOVVVRGxOYVZZemNETkJTa1Y0TW5OT05XVlRjME5FU1ZoMlRqbG5OMFE0TDNNPQ==';

// Ожидаемая подпись
$expected_signature = 'df0bf4126e8176060523492984f749615a3294363f04746ffc642eaaf9947289';

echo "=== Pay2.House WEBHOOK DEBUG (по официальной документации) ===\n\n";

// ТОЧНЫЙ алгоритм из официальной документации Pay2.House
function decrypt_webhook_official($data, $secret_key)
{
    echo "Тестирование ключа: {$secret_key}\n";

    $decoded_data = base64_decode($data);
    if ($decoded_data === FALSE) {
        echo "❌ Ошибка декодирования base64\n";
        return FALSE;
    }

    $parts = explode('|', $decoded_data);
    if (count($parts) !== 3) {
        echo "❌ Неправильный формат данных\n";
        return FALSE;
    }

    list($iv, $signature, $encrypted_data) = $parts;

    // ИМЕННО ТАКОЙ алгоритм в документации
    $calculated_signature = hash_hmac('sha256', $iv . '|' . $encrypted_data, $secret_key);

    echo "  Полученная подпись:   {$signature}\n";
    echo "  Вычисленная подпись:  {$calculated_signature}\n";

    if (hash_equals($calculated_signature, $signature)) {
        echo "  ✅ ПОДПИСЬ СОВПАДАЕТ!\n";

        // Расшифровка по алгоритму из документации
        $decoded_encrypted_data = openssl_decrypt(
            base64_decode($encrypted_data),
            'AES-256-CBC',
            hex2bin(hash('sha256', $secret_key)),
            0,
            hex2bin(bin2hex(hex2bin($iv)))
        );

        if ($decoded_encrypted_data !== FALSE) {
            echo "  ✅ Данные расшифрованы: {$decoded_encrypted_data}\n";
            return $decoded_encrypted_data;
        } else {
            echo "  ❌ Ошибка расшифровки: " . openssl_error_string() . "\n";
        }
    } else {
        echo "  ❌ Подписи не совпадают\n";
    }

    echo "\n";
    return FALSE;
}

// Тестируем с разными ключами
$test_keys = [
    'API ключ' => $api_key,
    'Merchant ID' => $merchant_id,
    'Key ID' => $key_id,
    'API + Merchant' => $api_key . $merchant_id,
    'Merchant + API' => $merchant_id . $api_key,
    'Key ID + API' => $key_id . $api_key,
    'API + Key ID' => $api_key . $key_id,
];

echo "1. Тестирование различных ключей:\n";
echo "================================\n";

$success = false;
foreach ($test_keys as $key_name => $test_key) {
    echo "Тестирую: {$key_name}\n";
    $result = decrypt_webhook_official($signature, $test_key);
    if ($result !== FALSE) {
        $success = true;
        echo "🎉 НАЙДЕН ПРАВИЛЬНЫЙ КЛЮЧ: {$key_name}\n";
        echo "🔑 Ключ: {$test_key}\n";
        echo "📄 Расшифрованные данные: {$result}\n";
        break;
    }
}

if (!$success) {
    echo "\n2. Дополнительное тестирование:\n";
    echo "==============================\n";

    // Возможно ключ нужно модифицировать
    $additional_keys = [
        'API lowercase' => strtolower($api_key),
        'API uppercase' => strtoupper($api_key),
        'Первые 32 символа API' => substr($api_key, 0, 32),
        'Последние 32 символа API' => substr($api_key, -32),
        'MD5 от API' => md5($api_key),
        'SHA256 от API' => hash('sha256', $api_key),
    ];

    foreach ($additional_keys as $key_name => $test_key) {
        echo "Тестирую: {$key_name}\n";
        $result = decrypt_webhook_official($signature, $test_key);
        if ($result !== FALSE) {
            $success = true;
            echo "🎉 НАЙДЕН ПРАВИЛЬНЫЙ КЛЮЧ: {$key_name}\n";
            echo "🔑 Ключ: {$test_key}\n";
            echo "📄 Расшифрованные данные: {$result}\n";
            break;
        }
    }
}

if (!$success) {
    echo "\n❌ НИ ОДИН КЛЮЧ НЕ ПОДОШЕЛ\n";
    echo "\nВозможные причины:\n";
    echo "1. Нужен webhook secret key (отличается от API key)\n";
    echo "2. Ключ нужно получить в личном кабинете Pay2.House\n";
    echo "3. Обратитесь в техподдержку Pay2.House\n";
    echo "\nПроверьте в личном кабинете Pay2.House:\n";
    echo "- Настройки webhook'ов\n";
    echo "- Webhook secret key\n";
    echo "- Merchant settings\n";
}

echo "\n=== ИТОГОВЫЙ КОД ДЛЯ ИСПОЛЬЗОВАНИЯ ===\n";
