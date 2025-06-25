# Практический план валидации креативов

## Обзор

Минималистичный план валидации параметров с интеграцией в существующую архитектуру без breaking changes.

## Анализ текущего состояния

### Существующая инфраструктура
- **Frontend/CreativesController**: Структурированные фильтры с `$defaultFilters`, методы API для опций
- **Test/CreativesController**: Базовая валидация табов, API с пагинацией  
- **BaseRequest**: Готовый `sanitizeInput()` метод
- **Vue Store**: Типизированная система фильтров в `creatives.ts`

### Критические пробелы
- Отсутствие FormRequest классов
- Минимальная валидация параметров (`in_array()` только для табов)
- Нет синхронизации типов TS ↔ PHP

## Практическая реализация

### Этап 1: Минимальная валидация (3 дня)

#### 1.1 Единый CreativeFilterRequest
```
app/Http/Requests/Creatives/CreativeFilterRequest.php
```

**Только критические правила валидации:**
```php
// Базовые параметры из существующих контроллеров
'tab' => 'nullable|string|in:push,inpage,facebook,tiktok',
'searchKeyword' => 'nullable|string|max:255',
'page' => 'nullable|integer|min:1|max:1000', 
'per_page' => 'nullable|integer|in:12,24,48,96',

// Фильтры массивов с ограничениями
'advertisingNetworks' => 'nullable|array|max:20',
'advertisingNetworks.*' => 'integer',
'languages' => 'nullable|array|max:50',
'operatingSystems' => 'nullable|array|max:10',
'browsers' => 'nullable|array|max:20',
'devices' => 'nullable|array|max:5'
```

### Этап 2: Интеграция без ломки API (2 дня)

#### 2.1 Обновление точек входа
- `Test/CreativesController::apiIndex()` - заменить `Request` на `CreativeFilterRequest`
- `Frontend/CreativesController::index()` - добавить опциональную валидацию

#### 2.2 Сохранение существующих форматов ответов
Никаких изменений в response format - только добавление валидации входящих параметров.

### Этап 3: Контракты TS ↔ PHP (2 дня)

#### 3.1 Зеркалирование типов
Создать точное соответствие между существующими типами в `creatives.ts` и правилами валидации PHP.

## Готовые к реализации компоненты

### 1. CreativeFilterRequest - Минимальная реализация

```php
<?php
namespace App\Http\Requests\Creatives;

use App\Http\Requests\BaseRequest;

class CreativeFilterRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Параметры из текущих контроллеров
            'tab' => 'nullable|string|in:push,inpage,facebook,tiktok',
            'tabs' => 'nullable|string|in:push,inpage,facebook,tiktok', // Test controller
            'searchKeyword' => 'nullable|string|max:255',
            'search' => 'nullable|string|max:255', // Test controller
            'page' => 'nullable|integer|min:1|max:1000',
            'per_page' => 'nullable|integer|in:12,24,48,96',
            
            // Фильтры из Frontend controller
            'advertisingNetworks' => 'nullable|array|max:20',
            'advertisingNetworks.*' => 'integer',
            'languages' => 'nullable|array|max:50',
            'operatingSystems' => 'nullable|array|max:10',
            'browsers' => 'nullable|array|max:20',
            'devices' => 'nullable|array|max:5',
            'imageSizes' => 'nullable|array|max:10',
            
            // Простые фильтры
            'country' => 'nullable|string|max:10',
            'sortBy' => 'nullable|string|in:creation,activity',
            'onlyAdult' => 'nullable|boolean',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Используем существующий sanitizeInput из BaseRequest
        if ($this->has('searchKeyword')) {
            $this->merge(['searchKeyword' => $this->sanitizeInput($this->searchKeyword)]);
        }
        if ($this->has('search')) {
            $this->merge(['search' => $this->sanitizeInput($this->search)]);
        }
    }
}
```

### 2. Механизм фиксации контрактов Frontend ↔ Backend

#### 2.1 Расширение существующих типов в creatives.ts

```typescript
// Добавить в resources/js/types/creatives.d.ts
export interface CreativesApiFilters extends FilterState {
  // Параметры API совместимые с бекендом  
  tab?: 'push' | 'inpage' | 'facebook' | 'tiktok';
  search?: string; // алиас для searchKeyword в Test controller
  page?: number;
  per_page?: 12 | 24 | 48 | 96;
}

// Валидация на фронтенде перед отправкой
export interface ApiValidation {
  isValid: boolean;
  errors: Record<string, string>;
}
```

#### 2.2 Простая система контрактов

```typescript
// resources/js/composables/useCreativesValidation.ts
export function useCreativesValidation() {
  function validateFilters(filters: Partial<CreativesApiFilters>): ApiValidation {
    const errors: Record<string, string> = {};

    // Проверки соответствующие PHP validation rules
    if (filters.searchKeyword && filters.searchKeyword.length > 255) {
      errors.searchKeyword = 'Search too long';
    }
    
    if (filters.advertisingNetworks && filters.advertisingNetworks.length > 20) {
      errors.advertisingNetworks = 'Too many networks';
    }

    if (filters.page && (filters.page < 1 || filters.page > 1000)) {
      errors.page = 'Invalid page number';
    }

    return {
      isValid: Object.keys(errors).length === 0,
      errors
    };
  }

  return { validateFilters };
}
```

#### 2.3 Интеграция в существующий Store

```typescript
// Обновить в resources/js/stores/creatives.ts метод mapFiltersToCreativesFilters
function mapFiltersToCreativesFilters(): CreativesFilters {
  // Добавить клиентскую валидацию перед отправкой
  const { validateFilters } = useCreativesValidation();
  const validation = validateFilters(filters);
  
  if (!validation.isValid) {
    console.warn('Validation errors before API call:', validation.errors);
  }

  return {
    // существующий код маппинга...
  };
}
```

### 3. Пошаговая интеграция в проект

#### 3.1 День 1: Создание CreativeFilterRequest

```bash
# Создать Request класс
php artisan make:request Creatives/CreativeFilterRequest

# Интегрировать в Test/CreativesController::apiIndex()
# Заменить: public function apiIndex(Request $request)
# На:       public function apiIndex(CreativeFilterRequest $request)
```

#### 3.2 День 2-3: Обновление контроллеров

```php
// В Test/CreativesController
public function apiIndex(CreativeFilterRequest $request)
{
    // Существующий код остается без изменений
    $tab = $request->validated()['tab'] ?? 'push';
    $perPage = $request->validated()['per_page'] ?? '12';
    // ... остальная логика
}

// В Frontend/CreativesController - опциональная валидация
public function index(CreativeFilterRequest $request = null)
{
    // Если Request передан - используем валидацию, иначе как раньше
    $request = $request ?? request();
    // ... существующая логика
}
```

#### 3.3 День 4-5: Frontend контракты

```typescript
// 1. Добавить useCreativesValidation.ts
// 2. Расширить types/creatives.d.ts  
// 3. Интегрировать в stores/creatives.ts
```

## Контроль качества реализации

### Минимальные критерии успеха

#### Backend:
- ✅ `CreativeFilterRequest` используется в `Test/CreativesController::apiIndex()`
- ✅ Валидация не ломает существующие API ответы
- ✅ `sanitizeInput()` применяется к поисковым запросам

#### Frontend:
- ✅ Типы `CreativesApiFilters` соответствуют PHP validation rules
- ✅ Клиентская валидация предотвращает заведомо невалидные запросы
- ✅ Существующий Vue Store работает без изменений

### Механизм контроля контрактов

#### 1. Автоматическая проверка при разработке

```bash
# Скрипт для проверки синхронизации
# scripts/validate-contracts.sh

#!/bin/bash
# Проверить что PHP rules соответствуют TS типам
php artisan route:list | grep creatives
npm run type-check
```

#### 2. Простые тесты интеграции

```php
// tests/Feature/CreativesContractTest.php
/** @test */
public function api_accepts_valid_filters()
{
    $response = $this->postJson('/api/creatives', [
        'tab' => 'push',
        'page' => 1,
        'per_page' => 12
    ]);
    
    $response->assertStatus(200); // Не 422
}
```

## Итоговый план: 1 неделя вместо 4

### Последовательность шагов:

1. **День 1**: Создать `CreativeFilterRequest` с минимальными правилами
2. **День 2**: Интегрировать в `Test/CreativesController`  
3. **День 3**: Добавить TypeScript типы и валидацию
4. **День 4**: Простые тесты контрактов
5. **День 5**: Проверка и документирование

### Принципы:
- **Максимальная простота**: Один класс, минимум правил
- **Обратная совместимость**: Ничего не ломаем
- **Практичность**: Сразу решаем реальные проблемы валидации

**Результат**: Базовая система валидации, готовая к расширению по мере необходимости.
