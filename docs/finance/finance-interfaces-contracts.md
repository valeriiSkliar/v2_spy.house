# Интерфейсы и контракты финансового модуля

## Основные принципы проектирования интерфейсов

### 1. Принцип единственной ответственности
Каждый интерфейс отвечает за конкретную область финансовых операций

### 2. Принцип инверсии зависимостей  
Основное приложение зависит от абстракций, а не от конкретных реализаций

### 3. Принцип открытости/закрытости
Интерфейсы открыты для расширения, но закрыты для модификации

## Главный интерфейс финансового модуля

```php
<?php

namespace App\Finance\Contracts;

use App\Finance\ValueObjects\PaymentResult;
use App\Finance\ValueObjects\SubscriptionInfo;
use Illuminate\Support\Collection;

/**
 * Главный интерфейс для всех финансовых операций
 * Единая точка входа для остального приложения
 */
interface FinanceManagerInterface
{
    // === ОПЕРАЦИИ С БАЛАНСОМ ===
    
    /**
     * Получить текущий баланс пользователя
     */
    public function getBalance(int $userId): float;
    
    /**
     * Пополнить баланс пользователя
     */
    public function depositFunds(
        int $userId, 
        float $amount, 
        string $paymentMethod, 
        array $metadata = []
    ): PaymentResult;
    
    /**
     * Списать средства с баланса
     */
    public function withdrawFunds(
        int $userId, 
        float $amount, 
        string $reason,
        array $metadata = []
    ): PaymentResult;
    
    // === УПРАВЛЕНИЕ ПОДПИСКАМИ ===
    
    /**
     * Получить активную подписку пользователя
     */
    public function getActiveSubscription(int $userId): ?SubscriptionInfo;
    
    /**
     * Купить подписку (прямая оплата)
     */
    public function purchaseSubscription(
        int $userId,
        int $subscriptionId,
        string $paymentMethod,
        ?string $promocode = null,
        array $metadata = []
    ): PaymentResult;
    
    /**
     * Купить подписку за счет баланса
     */
    public function purchaseSubscriptionFromBalance(
        int $userId,
        int $subscriptionId,
        ?string $promocode = null
    ): PaymentResult;
    
    /**
     * Отменить подписку
     */
    public function cancelSubscription(int $userId, string $reason = ''): bool;
    
    // === ПРОМОКОДЫ ===
    
    /**
     * Проверить валидность промокода
     */
    public function validatePromocode(string $promocode, int $userId): bool;
    
    /**
     * Применить промокод
     */
    public function applyPromocode(string $promocode, int $userId, int $paymentId): float;
    
    // === ИСТОРИЯ И АНАЛИТИКА ===
    
    /**
     * История платежей пользователя
     */
    public function getPaymentHistory(int $userId, array $filters = []): Collection;
    
    /**
     * История изменений баланса
     */
    public function getBalanceHistory(int $userId, array $filters = []): Collection;
}
```

## Специализированные интерфейсы

### Интерфейс платежных операций

```php
<?php

namespace App\Finance\Contracts;

use App\Finance\ValueObjects\PaymentResult;
use App\Finance\ValueObjects\PaymentData;

/**
 * Интерфейс для обработки платежей
 */
interface PaymentProcessorInterface
{
    /**
     * Инициировать платеж
     */
    public function initiatePayment(PaymentData $paymentData): PaymentResult;
    
    /**
     * Обработать webhook от платежной системы
     */
    public function processWebhook(string $paymentMethod, array $payload): PaymentResult;
    
    /**
     * Проверить статус платежа
     */
    public function checkPaymentStatus(string $paymentId): PaymentResult;
    
    /**
     * Отменить платеж
     */
    public function cancelPayment(string $paymentId): bool;
    
    /**
     * Сделать возврат
     */
    public function refundPayment(string $paymentId, float $amount): PaymentResult;
}
```

### Интерфейс управления балансом

```php
<?php

namespace App\Finance\Contracts;

use App\Finance\ValueObjects\BalanceOperation;

/**
 * Интерфейс для управления балансом пользователей
 */
interface BalanceManagerInterface
{
    /**
     * Получить баланс с блокировкой записи
     */
    public function getBalanceForUpdate(int $userId): float;
    
    /**
     * Добавить средства на баланс
     */
    public function addFunds(int $userId, BalanceOperation $operation): bool;
    
    /**
     * Списать средства с баланса
     */
    public function deductFunds(int $userId, BalanceOperation $operation): bool;
    
    /**
     * Проверить достаточность средств
     */
    public function hasSufficientFunds(int $userId, float $amount): bool;
    
    /**
     * Получить историю операций по балансу
     */
    public function getBalanceAuditTrail(int $userId): Collection;
}
```

### Интерфейс управления подписками

```php
<?php

namespace App\Finance\Contracts;

use App\Finance\ValueObjects\SubscriptionInfo;

/**
 * Интерфейс для управления подписками
 */
interface SubscriptionManagerInterface
{
    /**
     * Активировать подписку для пользователя
     */
    public function activateSubscription(
        int $userId,
        int $subscriptionId,
        int $paymentId
    ): SubscriptionInfo;
    
    /**
     * Деактивировать подписку
     */
    public function deactivateSubscription(int $userId): bool;
    
    /**
     * Продлить подписку
     */
    public function renewSubscription(int $userId): SubscriptionInfo;
    
    /**
     * Проверить истечение подписки
     */
    public function checkSubscriptionExpiration(int $userId): bool;
    
    /**
     * Получить информацию о доступных подписках
     */
    public function getAvailableSubscriptions(): Collection;
}
```

### Интерфейс безопасности

```php
<?php

namespace App\Finance\Contracts;

/**
 * Интерфейс для обеспечения безопасности финансовых операций
 */
interface FinanceSecurityInterface
{
    /**
     * Проверить лимиты транзакций для пользователя
     */
    public function checkTransactionLimits(int $userId, float $amount): bool;
    
    /**
     * Проверить подозрительную активность
     */
    public function detectSuspiciousActivity(int $userId, array $context): bool;
    
    /**
     * Валидировать webhook подпись
     */
    public function validateWebhookSignature(string $payload, string $signature, string $secret): bool;
    
    /**
     * Генерировать ключ идемпотентности
     */
    public function generateIdempotencyKey(array $data): string;
    
    /**
     * Проверить ключ идемпотентности
     */
    public function checkIdempotencyKey(string $key): bool;
}
```

## Value Objects

### PaymentResult

```php
<?php

namespace App\Finance\ValueObjects;

/**
 * Результат выполнения платежной операции
 */
readonly class PaymentResult
{
    public function __construct(
        public bool $success,
        public string $message,
        public ?string $paymentId = null,
        public ?string $externalId = null,
        public ?float $amount = null,
        public ?string $redirectUrl = null,
        public array $metadata = []
    ) {}
    
    public static function success(
        string $message = 'Payment successful',
        ?string $paymentId = null,
        ?string $externalId = null,
        ?float $amount = null,
        ?string $redirectUrl = null,
        array $metadata = []
    ): self {
        return new self(true, $message, $paymentId, $externalId, $amount, $redirectUrl, $metadata);
    }
    
    public static function failure(string $message, array $metadata = []): self
    {
        return new self(false, $message, metadata: $metadata);
    }
}
```

### PaymentData

```php
<?php

namespace App\Finance\ValueObjects;

/**
 * Данные для создания платежа
 */
readonly class PaymentData
{
    public function __construct(
        public int $userId,
        public float $amount,
        public string $currency,
        public string $paymentMethod,
        public string $type, // DEPOSIT, SUBSCRIPTION, etc.
        public ?int $subscriptionId = null,
        public ?string $promocode = null,
        public ?string $description = null,
        public array $metadata = []
    ) {}
}
```

### BalanceOperation

```php
<?php

namespace App\Finance\ValueObjects;

/**
 * Операция по изменению баланса
 */
readonly class BalanceOperation
{
    public function __construct(
        public float $amount,
        public string $type, // DEPOSIT, WITHDRAWAL, SUBSCRIPTION_PAYMENT
        public string $description,
        public ?int $paymentId = null,
        public ?int $subscriptionId = null,
        public array $metadata = []
    ) {}
}
```

### SubscriptionInfo

```php
<?php

namespace App\Finance\ValueObjects;

use Carbon\Carbon;

/**
 * Информация о подписке пользователя
 */
readonly class SubscriptionInfo
{
    public function __construct(
        public int $subscriptionId,
        public string $name,
        public float $price,
        public Carbon $startDate,
        public Carbon $endDate,
        public bool $isActive,
        public bool $isExpired,
        public int $apiRequestsLeft,
        public int $searchRequestsLeft,
        public array $features = []
    ) {}
}
```

## События для интеграции с остальным приложением

```php
<?php

namespace App\Finance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Событие успешного пополнения баланса
class FundsDeposited
{
    use Dispatchable, SerializesModels;
    
    public function __construct(
        public int $userId,
        public float $amount,
        public string $paymentId
    ) {}
}

// Событие покупки подписки
class SubscriptionPurchased
{
    use Dispatchable, SerializesModels;
    
    public function __construct(
        public int $userId,
        public int $subscriptionId,
        public string $paymentId,
        public ?string $promocode = null
    ) {}
}

// Событие истечения подписки
class SubscriptionExpired
{
    use Dispatchable, SerializesModels;
    
    public function __construct(
        public int $userId,
        public int $subscriptionId
    ) {}
}
```

## Примеры использования в приложении

### В контроллере

```php
<?php

namespace App\Http\Controllers;

use App\Finance\Contracts\FinanceManagerInterface;
use Illuminate\Http\Request;

class UserDashboardController extends Controller
{
    public function __construct(
        private FinanceManagerInterface $finance
    ) {}
    
    public function dashboard()
    {
        $userId = auth()->id();
        
        return view('dashboard', [
            'balance' => $this->finance->getBalance($userId),
            'subscription' => $this->finance->getActiveSubscription($userId),
            'recentPayments' => $this->finance->getPaymentHistory($userId, ['limit' => 5])
        ]);
    }
    
    public function purchaseSubscription(Request $request)
    {
        $result = $this->finance->purchaseSubscription(
            userId: auth()->id(),
            subscriptionId: $request->subscription_id,
            paymentMethod: $request->payment_method,
            promocode: $request->promocode
        );
        
        if ($result->success) {
            return response()->json([
                'success' => true,
                'redirect_url' => $result->redirectUrl
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => $result->message
        ], 400);
    }
}
```

### В middleware

```php
<?php

namespace App\Http\Middleware;

use App\Finance\Contracts\FinanceManagerInterface;
use Closure;

class CheckSubscription
{
    public function __construct(
        private FinanceManagerInterface $finance
    ) {}
    
    public function handle($request, Closure $next)
    {
        $subscription = $this->finance->getActiveSubscription(auth()->id());
        
        if (!$subscription || $subscription->isExpired) {
            return redirect()->route('subscription.plans');
        }
        
        return $next($request);
    }
}
```

### В сервисах приложения

```php
<?php

namespace App\Services;

use App\Finance\Contracts\FinanceManagerInterface;

class ApiRequestService
{
    public function __construct(
        private FinanceManagerInterface $finance
    ) {}
    
    public function canMakeRequest(int $userId): bool
    {
        $subscription = $this->finance->getActiveSubscription($userId);
        
        return $subscription && 
               !$subscription->isExpired && 
               $subscription->apiRequestsLeft > 0;
    }
}
```

## Регистрация в Service Provider

```php
<?php

namespace App\Finance\Providers;

use App\Finance\Contracts\FinanceManagerInterface;
use App\Finance\Services\FinanceManager;
use Illuminate\Support\ServiceProvider;

class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FinanceManagerInterface::class, FinanceManager::class);
        
        // Другие привязки интерфейсов...
    }
    
    public function boot(): void
    {
        // Загрузка миграций, роутов и т.д.
    }
}
```

## Ключевые преимущества такого подхода

1. **Простота использования**: Остальное приложение работает только с простыми интерфейсами
2. **Гибкость**: Можно менять реализацию без влияния на остальной код
3. **Тестируемость**: Легко создавать mock объекты для тестирования
4. **Безопасность**: Вся финансовая логика инкапсулирована в модуле
5. **Масштабируемость**: Модуль можно выделить в отдельный сервис при необходимости

Этот набор интерфейсов обеспечивает максимальную развязку между финансовым модулем и остальным приложением, при этом предоставляя простой и понятный API для всех финансовых операций.