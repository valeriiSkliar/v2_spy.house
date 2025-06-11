# Текущая архитектура финансового модуля

## Фактическая реализация (по состоянию на текущий момент)

Финансовый модуль реализован как простая структура Laravel под `app/Finance/` со следующими компонентами:

### Контроллеры
- **FinanceController**: Отображение истории транзакций (только депозиты)
- **TariffController**: Обработка платежей за подписки через Pay2.House
- **WebhookController**: Заглушка для webhook'ов (пока не реализован)

### Модели
- **Payment**: Основная модель платежей с поддержкой енумов
- **Subscription**: Модель тарифных планов
- **Promocode**: Модель промокодов
- **PromocodeActivation**: Активации промокодов

### Сервисы
- **Pay2Service**: Интеграция с платежной системой Pay2.House
- **PromocodeService**: Логика работы с промокодами

## Текущая структура файлов

```
app/Finance/
├── Http/Controllers/
│   ├── FinanceController.php      # История депозитов
│   ├── TariffController.php       # Обработка платежей за тарифы
│   └── WebhookController.php      # Webhook'и (заглушка)
├── Models/
│   ├── Payment.php                # Платежи
│   ├── Subscription.php           # Тарифные планы
│   ├── Promocode.php              # Промокоды
│   └── PromocodeActivation.php    # Активации промокодов
└── Services/
    ├── Pay2Service.php            # Интеграция с Pay2.House
    └── PromocodeService.php       # Логика промокодов
```

## Текущий функционал

### ✅ Реализовано
- **Прямые платежи за подписки** через Pay2.House
- **Система промокодов** с валидацией и активацией
- **Модели платежей** с енумами для статусов и типов
- **История платежей** (базовое отображение)
- **Интеграция с Pay2.House** (создание платежей, получение деталей)

### ❌ НЕ реализовано (из документации)
- **Система баланса пользователей** 
- **API эндпоинты** для финансовых операций
- **Комплексные интерфейсы** (FinanceManagerInterface и т.д.)
- **Webhook обработка** (только заглушка)
- **Value Objects** и сложная архитектура
- **События и слушатели** для финансовых операций
- **Оплата с баланса** (только прямые платежи)

## Рекомендации по развитию архитектуры

```
app/
├── Services/
│   └── Finance/
│       ├── FinanceServiceInterface.php
│       ├── FinanceService.php
│       ├── PaymentService.php
│       ├── SubscriptionService.php
│       └── BalanceService.php
├── Models/
│   └── Finance/
│       ├── Payment.php
│       ├── Subscription.php
│       └── Balance.php
└── Http/
    └── Controllers/
        └── Finance/
            └── FinanceController.php
```

**Преимущества:**
- Минимальные изменения в существующей структуре
- Быстрая интеграция
- Соответствует текущим паттернам проекта

**Недостатки:**
- Менее четкое разделение
- Может привести к смешению логики

### 3. 🔌 Event-Driven Architecture

```
app/
├── Services/
│   └── Finance/
│       └── FinanceGateway.php     # Единая точка входа
├── Events/
│   └── Finance/
│       ├── PaymentRequested.php
│       ├── PaymentCompleted.php
│       └── SubscriptionChanged.php
└── Listeners/
    └── Finance/
        ├── ProcessPayment.php
        ├── UpdateBalance.php
        └── NotifyUser.php
```

**Преимущества:**
- Максимальная развязка
- Асинхронная обработка
- Легкость расширения

**Недостатки:**
- Сложность отладки
- Требует настройки очередей

### 4. 🎯 Domain-Driven Design (DDD)

```
app/
├── Finance/                    # Bounded Context
│   ├── Domain/
│   │   ├── Models/            # Domain Models
│   │   ├── Services/          # Domain Services
│   │   ├── Repositories/      # Repository Interfaces
│   │   └── Events/            # Domain Events
│   ├── Infrastructure/
│   │   ├── Repositories/      # Repository Implementations
│   │   └── Services/          # External Services
│   └── Application/
│       ├── Services/          # Application Services
│       └── UseCases/          # Use Cases
└── Shared/
    └── Contracts/
        └── FinanceContract.php # Контракт для внешнего использования
```

**Преимущества:**
- Максимальная инкапсуляция бизнес-логики
- Четкие границы домена
- Высокая тестируемость

**Недостатки:**
- Высокая сложность
- Требует глубокого понимания DDD

## Рекомендуемое решение: Гибридный подход

### Основная структура

```
app/
└── Finance/
    ├── Contracts/
    │   ├── FinanceManagerInterface.php
    │   ├── PaymentGatewayInterface.php
    │   └── SubscriptionManagerInterface.php
    ├── Services/
    │   ├── FinanceManager.php          # Фасад для всех операций
    │   ├── PaymentService.php          # Обработка платежей
    │   ├── SubscriptionService.php     # Управление подписками
    │   ├── BalanceService.php          # Управление балансом
    │   └── SecurityService.php         # Безопасность транзакций
    ├── Models/
    │   ├── Payment.php
    │   ├── Subscription.php
    │   ├── BalanceAudit.php
    │   └── WebhookLog.php
    ├── Events/
    │   ├── PaymentProcessed.php
    │   └── SubscriptionChanged.php
    ├── Providers/
    │   └── FinanceServiceProvider.php
    └── Http/
        └── Controllers/
            ├── PaymentController.php
            └── WebhookController.php
```

### Публичный интерфейс для приложения

```php
// app/Finance/Contracts/FinanceManagerInterface.php
interface FinanceManagerInterface
{
    // Операции с балансом
    public function getBalance(int $userId): float;
    public function addFunds(int $userId, float $amount, string $source): PaymentResult;
    
    // Управление подписками
    public function purchaseSubscription(int $userId, int $subscriptionId, ?string $promocode = null): PaymentResult;
    public function cancelSubscription(int $userId): bool;
    
    // Информация
    public function getPaymentHistory(int $userId): Collection;
    public function getActiveSubscription(int $userId): ?Subscription;
}
```

### Использование в приложении

```php
// В любом контроллере приложения
class SomeController extends Controller 
{
    public function __construct(
        private FinanceManagerInterface $finance
    ) {}
    
    public function purchaseSubscription(Request $request)
    {
        $result = $this->finance->purchaseSubscription(
            userId: auth()->id(),
            subscriptionId: $request->subscription_id,
            promocode: $request->promocode
        );
        
        return response()->json($result);
    }
}
```

## Ключевые принципы интеграции

### 1. Единый интерфейс (Single Interface)
- Все финансовые операции через один интерфейс `FinanceManagerInterface`
- Простой и понятный API для остального приложения
- Скрытие сложности внутренней реализации

### 2. Event-Driven взаимодействие
- Финансовые операции генерируют события
- Остальное приложение подписывается на нужные события
- Асинхронная обработка побочных эффектов

### 3. Безопасность по дизайну
- Все критичные операции логируются
- Защита от дублирования транзакций
- Аудит всех изменений баланса

### 4. Конфигурируемость
- Легкое переключение между тестовым и продакшн режимами
- Возможность подключения разных платежных систем
- Гибкие настройки безопасности

## Поэтапный план внедрения

### Фаза 1: Базовая инфраструктура
1. Создание контрактов и базовых сервисов
2. Настройка service provider
3. Создание базовых моделей

### Фаза 2: Основной функционал
1. Реализация операций с балансом
2. Система управления подписками
3. Интеграция с платежными системами

### Фаза 3: Безопасность и аудит
1. Система логирования и аудита
2. Защита от мошенничества
3. Мониторинг и алерты

### Фаза 4: Оптимизация
1. Кэширование
2. Оптимизация производительности
3. Масштабирование

## Преимущества рекомендуемого подхода

1. **Простота использования**: Единый интерфейс для всех финансовых операций
2. **Гибкость**: Возможность изменения внутренней реализации без влияния на остальное приложение
3. **Безопасность**: Встроенные механизмы безопасности и аудита
4. **Тестируемость**: Четкое разделение ответственности и возможность мокирования
5. **Масштабируемость**: Модульная архитектура позволяет легко расширять функционал

Этот подход обеспечивает баланс между простотой интеграции и возможностями для будущего развития системы.