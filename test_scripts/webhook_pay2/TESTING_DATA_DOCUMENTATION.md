# Документация тестовых данных Pay2.House Webhook

## Обзор

Данная документация описывает тестовые данные для валидации webhook-уведомлений от платежной системы Pay2.House, включая обработку временных меток в формате Epoch.

## Структура тестовых данных

### Основной тестовый набор

```php
$test_signature = "NTM5OWVlODBiZDVhZWFiNzBmZjE4YmU3MGZjMGRlNGV8ZGYwYmY0MTI2ZTgxNzYwNjA1MjM0OTI5ODRmNzQ5NjE1YTMyOTQzNjNmMDQ3NDZmZmM2NDJlYWFmOTk0NzI4OXxZMm94YTJVd1ExWXpXbTUxT1hkUVRFVm1ORU5NTDFBMllqZFJNelp4VFhOcmJtcHliR0pDZVRRelZsTk1abFp2UW0wMVJsaDRRMFpQWm5GWmNXWlFUM0l5Y1dGRVUwbGtMM2R2TVdreVdqZElURkZQVkhNMGQwY3JVVGMyU210alVYa3ZjMGRqYTJObFpuTmFhRGRrYVZodmNITklla05aVjBwNE1IUjBVa04zU1dFdmVrWkhLMFpOVEZSTVVUTlFTRWd2WlU1WE5tMVpPVTlUZEVkVmEyWldSbXRWWWs5ek9XTnpNa2RYWkRJMWNERlVVRVJYYUZSclpHVlBNbTlRY0VadFFXVXdkR0kwZDJJMVdFcEhURlI1Um1oQ05EQmpVMFp0Ukc5eFFXWjNOSGhhWm0wNUx6WTJhUzlFV1UxRmN6ZEVLMHRwWlZwWmJubFFVM2RsU0RGc1F6Um9ia3RGY1RkRkwyMDNiekZzVUdGYVdqTmtNRUphVUZCeGNDOVVVRGxOYVZZemNETkJTa1Y0TW5OT05XVlRjME5FU1ZoMlRqbG5OMFE0TDNNPQ==";

$test_api_key = "YOUR_API_KEY_HERE";

$expected_payload = [
    "invoice_number" => "IN2212956367",
    "external_number" => "TN121750056778",
    "amount" => 1,
    "handling_fee" => 0,
    "currency_code" => "USD",
    "description" => "Оплата тарифа Start (month)",
    "status" => "paid"
];
```

## Временные метки Epoch

### Основные поля с временными метками

Pay2.House webhook может содержать следующие временные поля в формате Epoch (Unix timestamp):

- `created_at` - время создания инвойса (секунды с 1 января 1970 UTC)
- `paid_at` - время оплаты (секунды с 1 января 1970 UTC)
- `expires_at` - время истечения инвойса (секунды с 1 января 1970 UTC)

### Примеры конвертации Epoch

```php
// Конвертация Epoch в читаемую дату
$epoch_timestamp = 1703425200; // Пример: 24 декабря 2023, 15:00:00 UTC
$readable_date = date('Y-m-d H:i:s', $epoch_timestamp);
echo $readable_date; // 2023-12-24 15:00:00

// Конвертация текущего времени в Epoch
$current_epoch = time();
echo $current_epoch; // 1703425200 (пример)

// Проверка валидности Epoch timestamp
function is_valid_epoch($timestamp) {
    return is_numeric($timestamp) && $timestamp > 0;
}
```

### Тестовые данные с временными метками

```php
$webhook_with_timestamps = [
    "invoice_number" => "IN2212956367",
    "external_number" => "TN121750056778",
    "amount" => 1,
    "currency_code" => "USD",
    "status" => "paid",
    "created_at" => 1703425200,  // 24.12.2023 15:00:00 UTC
    "paid_at" => 1703425800,     // 24.12.2023 15:10:00 UTC
    "expires_at" => 1703511600   // 25.12.2023 15:00:00 UTC
];
```

## Использование тестовых скриптов

### 1. Базовое тестирование (official_webhook_test.php)

```bash
cd test_scripts/webhook_pay2/
php official_webhook_test.php
```

**Назначение**: Проверяет корректность официального алгоритма расшифровки Pay2.House

### 2. Полная валидация (final_webhook_validator.php)

```bash
cd test_scripts/webhook_pay2/
php final_webhook_validator.php
```

**Назначение**: Комплексная валидация webhook с детальным логированием

### 3. Интеграция в код

```php
require_once 'final_webhook_validator.php';

// Получение webhook данных
$signature = $_SERVER['HTTP_PAY2_HOUSE_SIGNATURE'] ?? '';
$payload = json_decode(file_get_contents('php://input'), true);
$api_key = 'ваш_api_ключ';

// Валидация
$result = validate_pay2_webhook($signature, $payload, $api_key, true);

if ($result['valid']) {
    // Обработка временных меток
    if (isset($result['webhook_data']['paid_at'])) {
        $paid_time = date('Y-m-d H:i:s', $result['webhook_data']['paid_at']);
        echo "Оплачено: $paid_time";
    }

    // Ваша бизнес-логика
    process_payment($result['webhook_data']);
} else {
    http_response_code(401);
    echo $result['error'];
}
```

## Безопасность тестирования

### ⚠️ Важные предупреждения

1. **Не используйте тестовые API ключи в продакшене**
2. **Тестовые данные содержат реальные структуры - не публикуйте их**
3. **Всегда валидируйте временные метки на разумность**

### Проверка временных меток

```php
function validate_timestamp($timestamp, $field_name) {
    if (!is_valid_epoch($timestamp)) {
        throw new Exception("Неверный формат времени в поле $field_name");
    }

    $current_time = time();
    $max_future = $current_time + (30 * 24 * 60 * 60); // 30 дней в будущем
    $min_past = $current_time - (365 * 24 * 60 * 60);   // 1 год назад

    if ($timestamp > $max_future || $timestamp < $min_past) {
        throw new Exception("Время $field_name вне допустимого диапазона");
    }

    return true;
}
```

## Отладка

### Логирование Epoch данных

```php
function log_epoch_data($webhook_data) {
    $epoch_fields = ['created_at', 'paid_at', 'expires_at'];

    foreach ($epoch_fields as $field) {
        if (isset($webhook_data[$field])) {
            $epoch = $webhook_data[$field];
            $readable = date('Y-m-d H:i:s', $epoch);
            echo "$field: $epoch ($readable)\n";
        }
    }
}
```

## Статусы платежей

| Статус      | Описание       | Epoch поля                 |
| ----------- | -------------- | -------------------------- |
| `pending`   | Ожидает оплаты | `created_at`, `expires_at` |
| `paid`      | Оплачен        | `created_at`, `paid_at`    |
| `expired`   | Истек          | `created_at`, `expires_at` |
| `cancelled` | Отменен        | `created_at`               |

---

_Последнее обновление: декабрь 2024_
_Версия API: Pay2.House v1_
