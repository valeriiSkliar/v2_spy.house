# Тестовая среда SpyHouse

Этот документ описывает настройку и использование тестовой среды проекта с использованием SQLite базы данных.

## Обзор

Тестовая среда настроена для работы с SQLite базой данных в памяти (`:memory:`), что обеспечивает:

- Быстрое выполнение тестов
- Изоляцию между тестами
- Отсутствие влияния на production базу данных
- Автоматический сброс данных между тестами

## Конфигурация

### PHPUnit Configuration

Конфигурация тестов находится в `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="DB_FOREIGN_KEYS" value="true"/>
```

### TestCase

Базовый класс `Tests\TestCase` автоматически:

- Использует `RefreshDatabase` trait для сброса БД между тестами
- Настраивает тестовое окружение
- Отключает автоматический сидинг (можно включить через `$seed = true`)

## Использование

### Запуск тестов

```bash
# Запуск всех тестов
php artisan test

# Запуск конкретного теста
php artisan test --filter DatabaseTestingExampleTest

# Параллельное выполнение тестов
php artisan test --parallel

# Запуск с покрытием кода
php artisan test --coverage
```

### Настройка тестовой среды

```bash
# Быстрая настройка
php artisan test:setup

# С пересозданием таблиц
php artisan test:setup --fresh

# С тестовыми данными
php artisan test:setup --fresh --seed
```

## Работа с базой данных в тестах

### RefreshDatabase Trait

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase;

    public function test_something(): void
    {
        // База данных автоматически сбрасывается
        $this->assertDatabaseCount('users', 0);
    }
}
```

### Сидинг данных

```php
// Автоматический сидинг для всех тестов класса
class MyTest extends TestCase
{
    protected $seed = true; // Запускает DatabaseSeeder

    // Или конкретный сидер
    protected $seeder = TestingSeeder::class;
}

// Сидинг в конкретном тесте
public function test_with_data(): void
{
    $this->seed(TestingSeeder::class);
    // ... тест с данными
}
```

### Ассерты базы данных

```php
// Проверка количества записей
$this->assertDatabaseCount('users', 5);

// Проверка наличия записи
$this->assertDatabaseHas('users', [
    'email' => 'test@example.com'
]);

// Проверка отсутствия записи
$this->assertDatabaseMissing('users', [
    'email' => 'deleted@example.com'
]);

// Проверка пустой таблицы
$this->assertDatabaseEmpty('users');

// Проверка существования модели
$user = User::factory()->create();
$this->assertModelExists($user);

// Проверка отсутствия модели
$user->delete();
$this->assertModelMissing($user);
```

### Фабрики моделей

```php
// Создание одной модели
$user = User::factory()->create();

// Создание с атрибутами
$user = User::factory()->create([
    'email' => 'specific@example.com'
]);

// Создание нескольких моделей
$users = User::factory()->count(3)->create();

// Создание без сохранения в БД
$user = User::factory()->make();
```

## Тестовые данные

### TestingSeeder

Специальный сидер `TestingSeeder` содержит минимальные тестовые данные:

- Тестовый пользователь (`test@example.com`)
- Тестовый админ (`admin@example.com`)
- Базовые типы уведомлений

### Использование тестовых данных

```php
public function test_with_test_users(): void
{
    $this->seed(TestingSeeder::class);

    $testUser = User::where('email', 'test@example.com')->first();
    $this->assertNotNull($testUser);
}
```

## Лучшие практики

### 1. Изоляция тестов

```php
// ✅ Правильно - используй RefreshDatabase
use RefreshDatabase;

// ❌ Неправильно - полагаться на порядок тестов
```

### 2. Минимальные тестовые данные

```php
// ✅ Правильно - создавай только нужные данные
$user = User::factory()->create();

// ❌ Неправильно - лишние данные замедляют тесты
$this->seed(DatabaseSeeder::class); // Слишком много данных
```

### 3. Читаемые тесты

```php
// ✅ Правильно - понятные ассерты
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);

// ❌ Неправильно - сложная логика в тестах
```

### 4. Тестирование транзакций

```php
public function test_rollback_on_error(): void
{
    try {
        DB::transaction(function () {
            User::create(['email' => 'test@example.com']);
            throw new Exception('Force rollback');
        });
    } catch (Exception $e) {
        // Игнорируем ошибку
    }

    $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
}
```

## Отладка тестов

### Вывод SQL запросов

```php
public function test_with_query_logging(): void
{
    DB::enableQueryLog();

    User::factory()->create();

    $queries = DB::getQueryLog();
    dump($queries); // Показать выполненные запросы
}
```

### Проверка состояния базы

```php
public function test_debug_database(): void
{
    $this->seed(TestingSeeder::class);

    // Показать все записи в таблице
    $users = DB::table('users')->get();
    dump($users->toArray());
}
```

## Производительность

- Используй `:memory:` базу для максимальной скорости
- Минимизируй количество сидинга
- Используй `--parallel` для параллельного выполнения
- Отключай ненужные сервисы в тестах (Telescope, Pulse)

## Troubleshooting

### База данных не сбрасывается

Убедись что используешь `RefreshDatabase` trait:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase; // Обязательно!
}
```

### Ошибки foreign key

Проверь что включены foreign key constraints:

```php
// В phpunit.xml
<env name="DB_FOREIGN_KEYS" value="true"/>
```

### Медленные тесты

- Используй `:memory:` вместо файловой БД
- Уменьши количество сидинга
- Отключи ненужные сервисы
- Используй параллельное выполнение
