<?php

/**
 * ТОЧНАЯ КОПИЯ официального алгоритма Pay2.House из документации
 */

// Официальная функция из документации Pay2.House
function decrypt_webhook($data = NULL, $secret_key = NULL)
{
    $decoded_data = base64_decode($data);

    if ($decoded_data === FALSE) {
        return FALSE;
    }

    list($iv, $signature, $encrypted_data) = explode('|', $decoded_data);
    $calculated_signature = hash_hmac('sha256', $iv . '|' . $encrypted_data, $secret_key);

    if (hash_equals($calculated_signature, $signature)) {
        $decoded_encrypted_data = openssl_decrypt(
            base64_decode($encrypted_data),
            'AES-256-CBC',
            hex2bin(hash('sha256', $secret_key)),
            0,
            hex2bin(bin2hex(hex2bin($iv)))
        );
        if ($decoded_encrypted_data !== FALSE) {
            return $decoded_encrypted_data;
        }
    }

    return FALSE;
}

// Реальные данные
$signature = "NTM5OWVlODBiZDVhZWFiNzBmZjE4YmU3MGZjMGRlNGV8ZGYwYmY0MTI2ZTgxNzYwNjA1MjM0OTI5ODRmNzQ5NjE1YTMyOTQzNjNmMDQ3NDZmZmM2NDJlYWFmOTk0NzI4OXxZMm94YTJVd1ExWXpXbTUxT1hkUVRFVm1ORU5NTDFBMllqZFJNelp4VFhOcmJtcHliR0pDZVRRelZsTk1abFp2UW0wMVJsaDRRMFpQWm5GWmNXWlFUM0l5Y1dGRVUwbGtMM2R2TVdreVdqZElURkZQVkhNMGQwY3JVVGMyU210alVYa3ZjMGRqYTJObFpuTmFhRGRrYVZodmNITklla05aVjBwNE1IUjBVa04zU1dFdmVrWkhLMFpOVEZSTVVUTlFTRWd2WlU1WE5tMVpPVTlUZEVkVmEyWldSbXRWWWs5ek9XTnpNa2RYWkRJMWNERlVVRVJYYUZSclpHVlBNbTlRY0VadFFXVXdkR0kwZDJJMVdFcEhURlI1Um1oQ05EQmpVMFp0Ukc5eFFXWjNOSGhhWm0wNUx6WTJhUzlFV1UxRmN6ZEVLMHRwWlZwWmJubFFVM2RsU0RGc1F6Um9ia3RGY1RkRkwyMDNiekZzVUdGYVdqTmtNRUphVUZCeGNDOVVVRGxOYVZZemNETkJTa1Y0TW5OT05XVlRjME5FU1ZoMlRqbG5OMVE0TDNNPQ==";
$api_key = $_ENV['PAY2_API_KEY'] ?? "YOUR_API_KEY_HERE";

// Ожидаемые данные после расшифровки
$expected_payload = [
    "invoice_number" => "IN2212956367",
    "external_number" => "TN121750056778",
    "amount" => 1,
    "handling_fee" => 0,
    "currency_code" => "USD",
    "description" => "Оплата тарифа Start (month)",
    "status" => "paid"
];

echo "🔥 ТЕСТ ОФИЦИАЛЬНОГО АЛГОРИТМА PAY2.HOUSE\n";
echo "==========================================\n\n";

echo "📝 Данные:\n";
echo "Подпись: " . substr($signature, 0, 50) . "...\n";
echo "API ключ: " . substr($api_key, 0, 20) . "...\n\n";

echo "📊 Ожидаемые данные:\n";
echo json_encode($expected_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "🧪 Запускаю официальную функцию decrypt_webhook()...\n";

$result = decrypt_webhook($signature, $api_key);

if ($result !== FALSE) {
    echo "🎉 УСПЕХ! Официальный алгоритм работает!\n";
    echo "📄 Расшифрованные данные: $result\n\n";

    $json_data = json_decode($result, true);
    if ($json_data) {
        echo "📊 Структурированные данные:\n";
        foreach ($json_data as $key => $value) {
            echo "  $key: $value\n";
        }

        // Сравнение с ожидаемыми данными
        echo "\n🔍 Проверка соответствия ожидаемым данным:\n";
        $matches = true;
        foreach ($expected_payload as $key => $expected_value) {
            if (!isset($json_data[$key]) || $json_data[$key] != $expected_value) {
                echo "  ❌ $key: ожидалось '$expected_value', получено '" . ($json_data[$key] ?? 'отсутствует') . "'\n";
                $matches = false;
            } else {
                echo "  ✅ $key: совпадает\n";
            }
        }

        if ($matches) {
            echo "\n🎯 ВСЕ ДАННЫЕ СОВПАДАЮТ! Тест прошел успешно!\n";
        } else {
            echo "\n⚠️  Обнаружены расхождения в данных\n";
        }
    } else {
        echo "❌ Ошибка парсинга JSON\n";
    }
} else {
    echo "❌ Официальный алгоритм не смог расшифровать данные\n";
    echo "🔍 Возможные причины:\n";
    echo "  - Неверный API ключ\n";
    echo "  - Данные повреждены\n";
    echo "  - Несоответствие алгоритма\n";
    echo "  - Подпись не соответствует ожидаемому payload\n";
}

echo "\n🏁 Тест завершен\n";
