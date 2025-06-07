# API Design для финансового модуля

## Принципы проектирования API

### 1. RESTful архитектура
- Стандартные HTTP методы (GET, POST, PUT, DELETE)
- Логичная структура URL
- Правильные HTTP статус коды
- Консистентный формат ответов

### 2. Асинхронность и производительность
- Неблокирующие операции для тяжелых операций
- Поддержка long polling для статусов платежей
- Efficient caching strategies
- Rate limiting для защиты от злоупотреблений

### 3. Безопасность
- Все эндпоинты требуют аутентификации
- CSRF защита
- Валидация всех входных данных
- Аудит всех операций

## Структура API эндпоинтов

### 📊 Баланс и кошелек

```http
GET    /api/finance/balance                    # Текущий баланс
POST   /api/finance/deposit                    # Пополнение баланса
GET    /api/finance/balance/history            # История изменений баланса
GET    /api/finance/balance/audit/{id}         # Детали операции
```

### 💳 Платежи

```http
GET    /api/finance/payments                   # История платежей
POST   /api/finance/payments                   # Создать платеж
GET    /api/finance/payments/{id}              # Детали платежа
GET    /api/finance/payments/{id}/status       # Статус платежа
POST   /api/finance/payments/{id}/cancel       # Отменить платеж
```

### 📋 Подписки

```http
GET    /api/finance/subscriptions              # Доступные подписки
GET    /api/finance/subscription/active        # Активная подписка
POST   /api/finance/subscription/purchase      # Купить подписку
POST   /api/finance/subscription/cancel        # Отменить подписку
GET    /api/finance/subscription/history       # История подписок
```

### 🎟️ Промокоды

```http
POST   /api/finance/promocode/validate         # Проверить промокод
POST   /api/finance/promocode/apply            # Применить промокод
GET    /api/finance/promocode/history          # История использования
```

### 🔔 Webhook'и (внешние)

```http
POST   /api/finance/webhook/tether             # Webhook от TetherUSDT
POST   /api/finance/webhook/pay2house          # Webhook от Pay2.House
```

## Детальные спецификации эндпоинтов

### Баланс пользователя

#### GET /api/finance/balance
**Описание**: Получить текущий баланс пользователя

**Заголовки**:
```http
Authorization: Bearer {token}
Accept: application/json
```

**Ответ**:
```json
{
    "success": true,
    "data": {
        "balance": 1250.50,
        "currency": "RUB",
        "last_updated": "2024-01-15T10:30:00Z",
        "version": 15
    },
    "meta": {
        "cached": false,
        "cache_expires_at": "2024-01-15T10:35:00Z"
    }
}
```

#### POST /api/finance/deposit
**Описание**: Инициировать пополнение баланса

**Тело запроса**:
```json
{
    "amount": 1000.00,
    "payment_method": "TetherUSDT",
    "description": "Пополнение баланса",
    "return_url": "https://example.com/payment/success",
    "idempotency_key": "unique-client-key-123"
}
```

**Ответ**:
```json
{
    "success": true,
    "data": {
        "payment_id": "pay_abc123def456",
        "amount": 1000.00,
        "status": "pending",
        "payment_url": "https://pay.tether.to/redirect/abc123",
        "expires_at": "2024-01-15T11:00:00Z"
    },
    "meta": {
        "estimated_completion": "5-10 минут",
        "support_contact": "support@example.com"
    }
}
```

### История баланса

#### GET /api/finance/balance/history
**Описание**: Получить историю изменений баланса

**Параметры запроса**:
```http
?page=1
&per_page=20
&date_from=2024-01-01
&date_to=2024-01-31
&operation_type=deposit,withdrawal,subscription
&sort=created_at
&order=desc
```

**Ответ**:
```json
{
    "success": true,
    "data": {
        "items": [
            {
                "id": 123,
                "amount": 500.00,
                "operation_type": "deposit",
                "description": "Пополнение через TetherUSDT",
                "balance_before": 750.50,
                "balance_after": 1250.50,
                "payment_id": "pay_abc123",
                "created_at": "2024-01-15T10:30:00Z",
                "metadata": {
                    "source": "TetherUSDT",
                    "transaction_hash": "0x123abc..."
                }
            }
        ],
        "pagination": {
            "current_page": 1,
            "total_pages": 5,
            "total_items": 95,
            "per_page": 20
        }
    }
}
```

### Создание платежа

#### POST /api/finance/payments
**Описание**: Создать новый платеж

**Тело запроса**:
```json
{
    "type": "subscription_purchase",
    "subscription_id": 3,
    "payment_method": "balance",
    "promocode": "SAVE20",
    "idempotency_key": "payment-123-456"
}
```

**Ответ**:
```json
{
    "success": true,
    "data": {
        "payment_id": "pay_xyz789",
        "type": "subscription_purchase",
        "amount": 800.00,
        "original_amount": 1000.00,
        "discount": 200.00,
        "promocode": "SAVE20",
        "status": "processing",
        "payment_method": "balance",
        "created_at": "2024-01-15T10:30:00Z",
        "estimated_completion": "Мгновенно"
    }
}
```

### Статус платежа

#### GET /api/finance/payments/{id}/status
**Описание**: Получить актуальный статус платежа

**Ответ**:
```json
{
    "success": true,
    "data": {
        "payment_id": "pay_xyz789",
        "status": "completed",
        "amount": 800.00,
        "completed_at": "2024-01-15T10:31:15Z",
        "subscription": {
            "id": 3,
            "name": "Premium",
            "active_until": "2024-02-15T10:31:15Z"
        }
    }
}
```

### Активная подписка

#### GET /api/finance/subscription/active
**Описание**: Получить информацию об активной подписке

**Ответ**:
```json
{
    "success": true,
    "data": {
        "subscription": {
            "id": 3,
            "name": "Premium",
            "description": "Премиум подписка с расширенными возможностями",
            "price": 1000.00,
            "currency": "RUB",
            "features": [
                "10000 API запросов в месяц",
                "500 поисковых запросов",
                "Приоритетная поддержка",
                "Расширенная аналитика"
            ],
            "started_at": "2024-01-15T10:31:15Z",
            "expires_at": "2024-02-15T10:31:15Z",
            "days_left": 31,
            "auto_renewal": false,
            "usage": {
                "api_requests_used": 1250,
                "api_requests_limit": 10000,
                "search_requests_used": 45,
                "search_requests_limit": 500
            }
        }
    }
}
```

### Доступные подписки

#### GET /api/finance/subscriptions
**Описание**: Получить список доступных подписок

**Ответ**:
```json
{
    "success": true,
    "data": {
        "subscriptions": [
            {
                "id": 1,
                "name": "Basic",
                "description": "Базовая подписка для начинающих",
                "price": 300.00,
                "currency": "RUB",
                "period": "monthly",
                "features": [
                    "1000 API запросов в месяц",
                    "50 поисковых запросов",
                    "Email поддержка"
                ],
                "is_popular": false,
                "discount_info": null
            },
            {
                "id": 2,
                "name": "Pro",
                "description": "Профессиональная подписка",
                "price": 600.00,
                "currency": "RUB",
                "period": "monthly",
                "features": [
                    "5000 API запросов в месяц",
                    "200 поисковых запросов",
                    "Приоритетная поддержка"
                ],
                "is_popular": true,
                "discount_info": {
                    "type": "early_bird",
                    "discount_percent": 15,
                    "expires_at": "2024-02-01T00:00:00Z"
                }
            }
        ]
    }
}
```

### Покупка подписки

#### POST /api/finance/subscription/purchase
**Описание**: Купить подписку

**Тело запроса**:
```json
{
    "subscription_id": 2,
    "payment_method": "TetherUSDT",
    "promocode": "NEWUSER2024",
    "return_url": "https://example.com/subscription/success",
    "idempotency_key": "sub-purchase-789"
}
```

**Ответ**:
```json
{
    "success": true,
    "data": {
        "payment_id": "pay_sub_456",
        "subscription_id": 2,
        "amount": 510.00,
        "original_amount": 600.00,
        "discount": 90.00,
        "promocode_discount": 60.00,
        "early_bird_discount": 30.00,
        "payment_method": "TetherUSDT",
        "payment_url": "https://pay.tether.to/redirect/sub456",
        "status": "pending",
        "expires_at": "2024-01-15T11:00:00Z"
    }
}
```

### Валидация промокода

#### POST /api/finance/promocode/validate
**Описание**: Проверить валидность промокода

**Тело запроса**:
```json
{
    "promocode": "SAVE20",
    "subscription_id": 2,
    "amount": 600.00
}
```

**Ответ**:
```json
{
    "success": true,
    "data": {
        "valid": true,
        "promocode": "SAVE20",
        "discount_type": "percentage",
        "discount_value": 20,
        "discount_amount": 120.00,
        "final_amount": 480.00,
        "conditions": {
            "min_amount": 500.00,
            "max_uses": 100,
            "uses_left": 45,
            "expires_at": "2024-03-01T00:00:00Z",
            "first_time_users_only": false
        },
        "applicable_to": ["subscription_purchase", "balance_deposit"]
    }
}
```

## Форматы ошибок

### Стандартный формат ошибки
```json
{
    "success": false,
    "error": {
        "code": "INSUFFICIENT_FUNDS",
        "message": "Недостаточно средств на балансе",
        "details": {
            "required_amount": 1000.00,
            "available_balance": 250.50,
            "missing_amount": 749.50
        }
    },
    "meta": {
        "request_id": "req_abc123",
        "timestamp": "2024-01-15T10:30:00Z"
    }
}
```

### Коды ошибок

#### Общие ошибки
- `VALIDATION_ERROR` - Ошибка валидации входных данных
- `UNAUTHORIZED` - Пользователь не авторизован
- `FORBIDDEN` - Недостаточно прав доступа
- `RATE_LIMIT_EXCEEDED` - Превышен лимит запросов
- `INTERNAL_ERROR` - Внутренняя ошибка сервера

#### Ошибки баланса
- `INSUFFICIENT_FUNDS` - Недостаточно средств
- `BALANCE_LOCKED` - Баланс заблокирован
- `INVALID_AMOUNT` - Некорректная сумма

#### Ошибки платежей
- `PAYMENT_NOT_FOUND` - Платеж не найден
- `PAYMENT_EXPIRED` - Платеж истек
- `PAYMENT_ALREADY_PROCESSED` - Платеж уже обработан
- `PAYMENT_FAILED` - Платеж не удался
- `DUPLICATE_PAYMENT` - Дублирующий платеж

#### Ошибки подписок
- `SUBSCRIPTION_NOT_FOUND` - Подписка не найдена
- `SUBSCRIPTION_ALREADY_ACTIVE` - Подписка уже активна
- `SUBSCRIPTION_EXPIRED` - Подписка истекла
- `SUBSCRIPTION_CANCELLED` - Подписка отменена

#### Ошибки промокодов
- `PROMOCODE_INVALID` - Промокод недействителен
- `PROMOCODE_EXPIRED` - Промокод истек
- `PROMOCODE_ALREADY_USED` - Промокод уже использован
- `PROMOCODE_LIMIT_EXCEEDED` - Превышен лимит использования

## Long Polling для статусов

### Эндпоинт для long polling
```http
GET /api/finance/payments/{id}/status/poll?timeout=30&version=1
```

**Параметры**:
- `timeout` - Максимальное время ожидания (секунды)
- `version` - Версия статуса для отслеживания изменений

**Ответ при изменении статуса**:
```json
{
    "success": true,
    "data": {
        "payment_id": "pay_xyz789",
        "status": "completed",
        "version": 2,
        "updated_at": "2024-01-15T10:31:15Z"
    }
}
```

**Ответ при таймауте**:
```json
{
    "success": true,
    "data": {
        "payment_id": "pay_xyz789",
        "status": "pending",
        "version": 1,
        "timeout": true
    }
}
```

## Rate Limiting

### Лимиты по эндпоинтам
```
GET запросы:     60 в минуту
POST платежи:    10 в минуту  
Валидация кодов: 30 в минуту
Webhook'и:       Без лимитов (с IP whitelist)
```

### Заголовки rate limiting
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1642248600
Retry-After: 30
```

## Кэширование

### Кэшируемые эндпоинты
- Доступные подписки (5 минут)
- Курсы валют (1 минута)
- Информация о промокодах (10 минут)

### Заголовки кэширования
```http
Cache-Control: public, max-age=300
ETag: "abc123def456"
Last-Modified: Mon, 15 Jan 2024 10:30:00 GMT
```

## Безопасность

### Аутентификация
```http
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...
```

### CSRF защита
```http
X-CSRF-TOKEN: abc123def456ghi789
```

### Идемпотентность
```http
Idempotency-Key: unique-client-operation-123
```

### IP whitelist для webhook'ов
```php
// Разрешенные IP для webhook'ов
'webhook_allowed_ips' => [
    '185.71.76.0/27',     // TetherUSDT
    '195.133.197.0/24',   // Pay2.House
]
```

Это API обеспечивает полную функциональность финансового модуля с акцентом на безопасность, производительность и удобство использования.