# Система промокодов 🎫

## Обзор

Система промокодов позволяет пользователям получать скидки при оплате подписок и пополнении баланса.

## Основные компоненты

### Модели

- `Promocode` - основная модель промокода
- `PromocodeActivation` - активация промокода пользователем

### Сервисы

- `PromocodeService` - основная логика работы с промокодами

## Создание промокода

```php
use App\Finance\Services\PromocodeService;

$service = new PromocodeService();

$promocode = $service->createPromocode([
    'promocode' => 'SAVE20', // опционально, сгенерируется автоматически
    'discount' => 20.00,     // процент скидки
    'status' => PromocodeStatus::ACTIVE,
    'date_start' => now(),
    'date_end' => now()->addDays(30),
    'max_per_user' => 1,     // максимум использований на пользователя
], $createdByUserId);
```

## Валидация промокода

```php
try {
    $result = $service->validatePromocode('SAVE20', $userId, 100.00);

    // $result содержит:
    // - valid: true
    // - promocode_id: ID промокода
    // - discount_percentage: процент скидки
    // - discount_amount: сумма скидки
    // - original_amount: исходная сумма
    // - final_amount: финальная сумма после скидки

} catch (ValidationException $e) {
    // Промокод недействителен
    echo $e->getMessage();
}
```

## Применение промокода

```php
try {
    $result = $service->applyPromocode(
        'SAVE20',
        $userId,
        100.00,
        request()->ip(),
        request()->userAgent(),
        $paymentId // опционально
    );

    // Промокод успешно применен
    $activationId = $result['activation_id'];

} catch (ValidationException $e) {
    // Ошибка применения
    echo $e->getMessage();
}
```

## Статистика использования

```php
$stats = $service->getUserPromocodeStats($userId);

// Возвращает:
// - total_activations: общее количество активаций
// - total_saved: общая сумма сэкономленных средств
// - activations: список всех активаций пользователя
```

## Проверка на злоупотребления

```php
$isAbuse = $service->checkForAbuse($ipAddress, $userId);

if ($isAbuse) {
    // Подозрительная активность - много активаций с одного IP
}
```

## Статусы промокодов

- `ACTIVE` - активный промокод
- `INACTIVE` - неактивный промокод
- `EXPIRED` - истекший промокод
- `EXHAUSTED` - исчерпанный промокод

## Методы модели Promocode

```php
$promocode = Promocode::findByCode('SAVE20');

// Проверки
$promocode->isValid();                    // общая валидность
$promocode->canBeUsedByUser($userId);     // можно ли использовать пользователю

// Расчеты
$discountAmount = $promocode->calculateDiscountAmount(100.00);  // 20.00
$finalAmount = $promocode->calculateFinalAmount(100.00);        // 80.00

// Активация
$activation = $promocode->activateForUser($userId, $ip, $userAgent, $paymentId);

// Scopes
Promocode::active()->get();     // только активные
Promocode::valid()->get();      // валидные (активные + в пределах дат)

// Генерация уникального кода
$uniqueCode = Promocode::generateUniqueCode(6); // длина 6 символов
```

## Безопасность

1. **Уникальность активаций** - один промокод нельзя применить дважды к одному платежу
2. **Лимиты по пользователям** - ограничение количества использований на пользователя
3. **IP трекинг** - отслеживание IP для обнаружения злоупотреблений
4. **Логирование** - все операции логируются для аудита
5. **Временные ограничения** - промокоды могут иметь срок действия

## Интеграция с платежами

Промокоды автоматически связываются с таблицей `payments` через поле `promocode_id`, что позволяет:

- Отслеживать использование промокодов в платежах
- Анализировать эффективность промокампаний
- Предотвращать мошенничество
