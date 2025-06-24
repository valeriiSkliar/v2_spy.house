# CreativesService Architecture

## Обзор

`CreativesService` - это специализированный сервисный слой для управления креативами с расширенной логикой фильтрации, умным кэшированием и предобработкой данных. Служит промежуточным слоем между Vue компонентами, Pinia Store и API.

## Архитектурное решение

### Принципы проектирования

1. **Single Responsibility** - отвечает только за бизнес-логику креативов
2. **Dependency Inversion** - не зависит от конкретной реализации API
3. **Configuration-driven** - настраиваемое поведение через конфигурацию
4. **Type Safety** - полная типизация TypeScript
5. **Performance First** - оптимизация запросов и кэширования

### Место в архитектуре

```
Vue Components
    ↓ actions
Pinia Store (creatives)
    ↓ business logic
CreativesService ← (текущий этап)
    ↓ HTTP requests
CreativesApiService
    ↓ network
Backend API
```

## Основные возможности

### ✅ Реализовано в базовой версии

1. **Умная фильтрация**

   - Предобработка фильтров с очисткой пустых значений
   - Установка значений по умолчанию
   - Подсчет активных фильтров

2. **Интеллектуальное кэширование**

   - Разные TTL для поиска (30 сек) и фильтров (5 мин)
   - Уменьшенный TTL для сложных фильтров
   - Генерация уникальных ключей кэша

3. **Дедупликация запросов**

   - Предотвращение параллельных идентичных запросов
   - Отслеживание состояния загрузки
   - Автоматическая очистка состояний

4. **Постобработка данных**
   - Добавление computed свойств
   - Обогащение метаданными
   - Подготовка пагинации

### 🔄 Планируется к реализации

1. **Интеграция с API** (Этап 2)
2. **Расширенная логика фильтров** (Этап 3)
3. **Интеграция со Store** (Этап 4)

## Типизация

### Основные интерфейсы

```typescript
interface Creative {
  id: number;
  name: string;
  category: string;
  country: string;
  file_url: string;
  preview_url?: string;
  created_at: string;
  activity_date?: string;
  // ... дополнительные поля массивов фильтров
}

interface CreativesFilters {
  searchKeyword?: string;
  country?: string;
  dateCreation?: string;
  sortBy?: 'creation' | 'activity';
  // ... остальные фильтры
  page?: number;
  perPage?: number;
}

interface ProcessedCreativesData {
  items: Creative[];
  pagination: PaginationData;
  meta: {
    hasSearch: boolean;
    activeFiltersCount: number;
    cacheKey: string;
  };
}
```

### Конфигурация

```typescript
interface CreativesServiceConfig {
  defaultCacheTtl: number; // 5 минут по умолчанию
  searchCacheTtl: number; // 30 секунд для поиска
  debounceDelay: number; // 300ms debounce
  maxCacheKeyLength: number; // 20 символов для ключа
}
```

## API Reference

### Основные методы

```typescript
// Загрузка креативов с фильтрацией
async loadCreatives(filters?: CreativesFilters): Promise<ProcessedCreativesData>

// Проверка состояния загрузки
isLoading(filters?: CreativesFilters): boolean

// Отмена всех запросов
cancelAllRequests(): void

// Управление конфигурацией
getConfig(): CreativesServiceConfig
updateConfig(config: Partial<CreativesServiceConfig>): void
```

### Использование

```typescript
import { creativesService } from '@/services/CreativesService';

// Базовая загрузка
const data = await creativesService.loadCreatives();

// Загрузка с фильтрами
const filteredData = await creativesService.loadCreatives({
  searchKeyword: 'banner',
  country: 'US',
  sortBy: 'creation',
  page: 1,
});

// Проверка состояния
if (creativesService.isLoading()) {
  console.log('Загрузка...');
}
```

## Алгоритмы и логика

### Предобработка фильтров

```typescript
// Входные данные (пример)
const rawFilters = {
  searchKeyword: '', // пустая строка
  country: null, // null значение
  advertisingNetworks: ['', 'Google', null], // смешанный массив
  languages: [], // пустой массив
  sortBy: undefined, // undefined
};

// Результат после предобработки
const processedFilters = {
  page: 1, // значение по умолчанию
  perPage: 12, // значение по умолчанию
  sortBy: 'creation', // значение по умолчанию
  country: 'All Categories', // значение по умолчанию
  onlyAdult: false, // значение по умолчанию
  advertisingNetworks: ['Google'], // очищенный массив
};
```

### Генерация ключей кэша

```typescript
// Алгоритм генерации ключа:
// 1. Сортировка ключей объекта для детерминированности
// 2. JSON.stringify для сериализации
// 3. Простой хэш алгоритм (djb2)
// 4. Преобразование в base36 и обрезка

const filters = { searchKeyword: 'test', country: 'US' };
const cacheKey = 'creatives-abc123xyz'; // результат
```

### Логика TTL кэширования

```typescript
function determineCacheTtl(filters: CreativesFilters): number {
  if (hasSearchKeyword(filters)) {
    return 30 * 1000; // 30 секунд для поиска
  }

  if (hasComplexFilters(filters)) {
    return 150 * 1000; // 2.5 минуты для сложных фильтров
  }

  return 300 * 1000; // 5 минут для простых фильтров
}
```

## Производительность

### Оптимизации

1. **Дедупликация запросов**

   - Блокировка параллельных идентичных запросов
   - Map для отслеживания состояний загрузки

2. **Умное кэширование**

   - Разный TTL в зависимости от типа фильтров
   - Детерминированная генерация ключей

3. **Эффективная обработка**
   - Минимальные операции с данными
   - Ленивая инициализация computed свойств

### Метрики

- **Размер бандла**: ~8KB (минифицированный)
- **Memory footprint**: Минимальный (только активные запросы)
- **Cache hit ratio**: Ожидается 60-80% для типичного использования

## Тестирование

### Покрытие тестами

```bash
npm test -- tests/frontend/services/CreativesService.test.js
```

**Тестируемые сценарии:**

- ✅ Конфигурация сервиса
- ✅ Предобработка фильтров
- ✅ Генерация ключей кэша
- ✅ Отслеживание состояния загрузки
- ✅ Обработка ошибок

### Примеры тестов

```typescript
it('должен устанавливать значения по умолчанию', async () => {
  const result = await creativesService.loadCreatives({});

  expect(result.meta.activeFiltersCount).toBe(0);
  expect(result.pagination.perPage).toBe(12);
});

it('должен предотвращать дублирование запросов', async () => {
  const filters = { searchKeyword: 'test' };

  const promise1 = creativesService.loadCreatives(filters);
  const promise2 = creativesService.loadCreatives(filters);

  await expect(promise2).rejects.toThrow('Запрос уже выполняется');
});
```

## Следующие этапы развития

### Этап 2: Интеграция с API

```typescript
// Интеграция с creativesApiService
private async makeApiRequest(filters: CreativesFilters, cacheConfig: any) {
  return await creativesApiService.get('/creatives', {
    params: filters,
    ...cacheConfig
  });
}
```

### Этап 3: Расширенная логика

- Retry механизм для failed запросов
- Optimistic updates для UI
- Background refresh для stale данных
- Advanced фильтрация с валидацией

### Этап 4: Store интеграция

- Watcher для автоматических запросов
- Reactive состояние загрузки
- URL синхронизация через композаблы

## Конфигурационные возможности

### Кастомизация для разных окружений

```typescript
// Development
const devService = new CreativesService({
  defaultCacheTtl: 10 * 1000, // 10 секунд для разработки
  searchCacheTtl: 5 * 1000, // 5 секунд
  debounceDelay: 100, // Быстрее для разработки
});

// Production
const prodService = new CreativesService({
  defaultCacheTtl: 10 * 60 * 1000, // 10 минут
  searchCacheTtl: 2 * 60 * 1000, // 2 минуты
  debounceDelay: 500, // Больше для production
});
```

## Заключение

`CreativesService` представляет собой гибкий и производительный слой для управления креативами с акцентом на:

- **Type Safety** - полная типизация интерфейсов
- **Performance** - умное кэширование и дедупликация
- **Maintainability** - четкая архитектура и тестирование
- **Flexibility** - конфигурируемое поведение

Готов к расширению функционала и интеграции с существующими системами проекта.

---

**Статус**: ✅ Базовая архитектура готова  
**Версия**: 1.0.0  
**Следующий этап**: Интеграция с API сервисом
