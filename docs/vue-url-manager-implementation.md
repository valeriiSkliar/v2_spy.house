# URL Manager Implementation Plan с @vueuse/core для Creatives

## Обзор

Документация по внедрению централизованного URL Manager для Vue островков раздела креативов с использованием @vueuse/core и `useUrlSearchParams` композабла. Система обеспечит синхронизацию состояния фильтров и параметров поиска с URL параметрами.

## Архитектурное решение

### Выбор библиотеки: @vueuse/core

**Преимущества:**

- **Tree-shaking** - только используемые функции
- **Нативная реактивность Vue 3** - без дополнительных оберток
- **Zero config** - работает из коробки
- **Composable pattern** - идеально для островной архитектуры
- **Active maintenance** - активно развивается
- **TypeScript поддержка** из коробки
- **History API** встроенный
- **Минимальный размер** бандла

## Фазы внедрения

### Фаза 1: Установка и базовая настройка

#### 1.1 Установка зависимостей

```bash
npm install @vueuse/core
```

#### 1.2 Создание базового композабла

```
resources/js/composables/useUrlSync.ts
```

**Функционал:**

- Базовый `useUrlSync` композабл
- Синхронизация state ↔ URL параметры
- Debounce для производительности
- Поддержка префиксов для namespace
- Transform функции для сериализации
- TypeScript типизация

### Фаза 2: Расширенные возможности

#### 2.1 Создание специализированных композаблов

```
resources/js/composables/
├── useUrlSync.ts              # Базовый композабл
├── useCreativesUrlSync.ts     # Для креативов (search, country, sort)
├── useFilterUrlSync.ts        # Для фильтров
└── usePaginationUrlSync.ts    # Для пагинации
```

#### 2.2 URL Manager Service

```
resources/js/vue-islands/services/UrlManagerService.ts
```

**Функционал:**

- Централизованное управление URL состоянием
- Координация между множественными Vue островками
- URL validation и sanitization
- Конфликт resolution для параметров
- Event-driven архитектура

### Фаза 3: Интеграция с существующими системами

#### 3.1 Интеграция с Creatives системой

```
resources/js/stores/
├── creatives.ts              # Расширение существующего store
└── creatives-url.ts          # Новый URL специфичный store
```

**Интеграция с существующей архитектурой:**

- Расширение `useFiltersStore` с URL синхронизацией
- Обновление `FiltersComponent.vue` с URL управлением
- Координация с Vue Islands архитектурой

#### 3.2 Vue Islands Registry

```
resources/js/vue-islands/UrlIslandsRegistry.ts
```

**Функционал:**

- Регистрация Vue островков с URL требованиями
- Автоматическая синхронизация при mount/unmount
- Namespace management для избежания конфликтов
- Global state coordination

### Фаза 4: Продвинутые возможности

#### 4.1 URL History Management

```
resources/js/vue-islands/composables/useUrlHistory.ts
```

**Функционал:**

- Browser back/forward поддержка
- Custom history events
- State restoration
- Deep linking support

#### 4.2 URL Validation & Schema

```
resources/js/vue-islands/schemas/UrlParameterSchemas.ts
```

**Функционал:**

- Zod схемы для URL параметров
- Automatic validation
- Type safety на runtime
- Error handling для невалидных параметров

### Фаза 5: Performance & Optimization

#### 5.1 Optimization Layer

```
resources/js/vue-islands/optimizations/
├── UrlSyncOptimizer.ts     # Batch updates, caching
├── PerformanceMonitor.ts   # Мониторинг производительности
└── MemoryManager.ts        # Cleanup и memory management
```

#### 5.2 Bundle Optimization

- Tree-shaking configuration
- Code splitting для URL управления
- Lazy loading для больших схем
- Performance budgets

## Структура файлов

```
resources/js/vue-islands/
├── composables/
│   ├── useUrlSync.ts                 # Базовый URL sync
│   ├── useCreativesUrlSync.ts        # Creatives специфичный
│   ├── useFilterUrlSync.ts           # Фильтры
│   ├── usePaginationUrlSync.ts       # Пагинация
│   └── useUrlHistory.ts              # History management
├── services/
│   ├── UrlManagerService.ts          # Центральный сервис
│   └── UrlValidationService.ts       # Validation логика
├── schemas/
│   ├── UrlParameterSchemas.ts        # Zod схемы
│   └── CreativesUrlSchema.ts         # Creatives URL схема
├── optimizations/
│   ├── UrlSyncOptimizer.ts           # Performance optimization
│   └── MemoryManager.ts              # Memory management
├── types/
│   ├── UrlSyncTypes.ts               # TypeScript типы
│   └── CreativesUrlTypes.ts          # Creatives URL типы
└── UrlIslandsRegistry.ts             # Islands registry
```

## API Design

### Базовый useUrlSync композабл

```typescript
interface UseUrlSyncOptions<T> {
  debounce?: number;
  prefix?: string;
  transform?: {
    serialize?: (value: any) => string;
    deserialize?: (value: string) => any;
  };
  validation?: ZodSchema<T>;
  history?: boolean;
}

function useUrlSync<T extends Record<string, any>>(
  initialState: T,
  options?: UseUrlSyncOptions<T>
): {
  state: DeepReadonly<T>;
  updateState: (updates: Partial<T>) => void;
  resetState: () => void;
  isLoading: Ref<boolean>;
  errors: Ref<Record<string, string>>;
};
```

### Creatives специфичный композабл

```typescript
interface CreativesUrlState {
  searchKeyword?: string;
  country?: string;
  dateCreation?: string;
  sortBy?: string;
  periodDisplay?: string;
  advertisingNetworks?: string[];
  languages?: string[];
  operatingSystems?: string[];
  browsers?: string[];
  devices?: string[];
  imageSizes?: string[];
  onlyAdult?: boolean;
  page?: number;
}

function useCreativesUrlSync(initialState?: Partial<CreativesUrlState>): {
  state: DeepReadonly<CreativesUrlState>;
  updateSearch: (search: string) => void;
  updateCountry: (country: string) => void;
  updateSort: (sort: string) => void;
  updatePage: (page: number) => void;
  updateFilters: (filters: Partial<CreativesUrlState>) => void;
  resetFilters: () => void;
};
```

## Интеграция с существующими системами

### Интеграция с Filters Store

```typescript
// Расширение stores/creatives.ts
import { useCreativesUrlSync } from '../vue-islands/composables/useCreativesUrlSync';

export const useFiltersStore = defineStore('filters', () => {
  // ... существующий код ...

  // Новые URL методы
  let urlState: ReturnType<typeof useCreativesUrlSync> | null = null;

  function initUrlSync() {
    urlState = useCreativesUrlSync({
      searchKeyword: filters.searchKeyword,
      country: filters.country,
      sortBy: filters.sortBy,
      onlyAdult: filters.onlyAdult,
    });

    // Синхронизация URL -> Store
    watch(
      urlState.state,
      newState => {
        updateFromUrl(newState);
      },
      { deep: true }
    );
  }

  function updateFromUrl(urlState: CreativesUrlState) {
    if (urlState.searchKeyword !== filters.searchKeyword) {
      setSearchKeyword(urlState.searchKeyword || '');
    }
    if (urlState.country !== filters.country) {
      setCountry(urlState.country || 'All Categories');
    }
    // ... остальные синхронизации
  }

  return {
    // ... существующий API ...
    initUrlSync,
    urlState: readonly(urlState),
  };
});
```

### Интеграция с FiltersComponent

```vue
<!-- Обновление vue-components/creatives/FiltersComponent.vue -->
<script setup lang="ts">
import { onMounted } from 'vue';
import { useFiltersStore } from '../../stores/creatives';

// ... существующий код ...

onMounted(() => {
  const storeInstance = initStore();

  // Инициализируем URL синхронизацию
  storeInstance.initUrlSync();

  // Применяем начальные фильтры
  if (props.initialFilters && Object.keys(props.initialFilters).length > 0) {
    storeInstance.initializeFromProps(props.initialFilters);
  }

  // ... остальной код инициализации
});
</script>
```

## Конфигурация и настройки

### TypeScript конфигурация

```typescript
// resources/js/vue-islands/config/UrlSyncConfig.ts
export const URL_SYNC_CONFIG = {
  defaultDebounce: 300,
  maxHistoryEntries: 50,
  enableValidation: true,
  enablePerformanceMonitoring: process.env.NODE_ENV === 'development',
  prefixes: {
    creatives: 'cr',
    filter: 'f',
    pagination: 'p',
  },
} as const;
```

### Validation схемы

```typescript
// resources/js/vue-islands/schemas/CreativesUrlSchema.ts
import { z } from 'zod';

export const CreativesUrlSchema = z.object({
  searchKeyword: z.string().max(100).optional(),
  country: z.string().optional(),
  dateCreation: z.string().optional(),
  sortBy: z.enum(['creation', 'activity']).optional(),
  periodDisplay: z.string().optional(),
  advertisingNetworks: z.array(z.string()).optional(),
  languages: z.array(z.string()).optional(),
  operatingSystems: z.array(z.string()).optional(),
  browsers: z.array(z.string()).optional(),
  devices: z.array(z.string()).optional(),
  imageSizes: z.array(z.string()).optional(),
  onlyAdult: z.boolean().optional(),
  page: z.number().int().min(1).max(1000).optional(),
});

export type CreativesUrlState = z.infer<typeof CreativesUrlSchema>;
```

## Performance соображения

### Optimization стратегии

1. **Debouncing**: 300ms debounce для URL updates
2. **Batch updates**: Группировка множественных изменений фильтров
3. **Selective sync**: Только измененные параметры
4. **Memory cleanup**: Автоматическая очистка при unmount
5. **Lazy validation**: Validation только при необходимости

### Bundle size optimization

```javascript
// Webpack/Vite конфигурация
export default {
  resolve: {
    alias: {
      '@vueuse/core': '@vueuse/core/index.mjs', // Tree-shaking friendly
    },
  },
  build: {
    rollupOptions: {
      external: ['@vueuse/core'], // Для библиотечных проектов
    },
  },
};
```

## Testing стратегия

### Unit тесты

```
tests/frontend/vue-islands/
├── composables/
│   ├── useUrlSync.test.ts
│   └── useCreativesUrlSync.test.ts
├── services/
│   └── UrlManagerService.test.ts
└── integration/
    └── url-creatives-integration.test.ts
```

### E2E тесты

```
tests/frontend/e2e/
├── url-sync/
│   ├── creatives-url-navigation.test.ts
│   ├── browser-history.test.ts
│   └── deep-linking.test.ts
```

## Migration план

### Этап 1: Подготовка (1-2 дня)

- Установка @vueuse/core
- Создание базовых композаблов
- TypeScript типы и схемы

### Этап 2: Core интеграция (2-3 дня)

- Интеграция с creatives системой
- URL sync для основных фильтров
- Testing базового функционала

### Этап 3: Расширенный функционал (3-4 дня)

- History management
- Validation layer
- Performance optimizations

### Этап 4: Production готовность (1-2 дня)

- E2E тестирование
- Performance audit
- Documentation finalization

## Риски и mitigation

### Потенциальные риски

1. **Browser compatibility** - History API поддержка
2. **Performance impact** - Частые URL updates при изменении фильтров
3. **State conflicts** - Множественные Vue островки на странице креативов
4. **SEO implications** - JavaScript-driven URL changes

### Mitigation стратегии

1. **Polyfills** для старых браузеров
2. **Debouncing и batching** для performance
3. **Namespace prefixes** для конфликтов (cr*, f*, p\_)
4. **Server-side sync** для SEO

## Заключение

URL Manager с @vueuse/core для раздела креативов обеспечит:

- **Унифицированный подход** к URL синхронизации фильтров
- **Type-safe** интерфейсы с существующей архитектурой
- **Performance optimization** из коробки
- **Seamless интеграцию** с существующим FiltersComponent
- **Готовность к масштабированию** для новых фильтров

Система спроектирована для поэтапного внедрения без breaking changes в существующем коде креативов.
