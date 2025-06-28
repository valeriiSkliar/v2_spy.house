# URL Sync Composables

Композаблы для синхронизации состояния Vue компонентов с URL параметрами, используя @vueuse/core.

## Основные файлы

- `useUrlSync.ts` - Базовый композабл для URL синхронизации
- `useUrlSyncExample.ts` - Примеры использования и готовые композаблы

## Возможности

### ✅ Базовый функционал

- ✅ Синхронизация state ↔ URL параметры
- ✅ Debounce для производительности (300ms по умолчанию)
- ✅ Поддержка префиксов для namespace
- ✅ Transform функции для сериализации
- ✅ TypeScript типизация
- ✅ Автоматическая очистка при unmount

### ✅ Продвинутые возможности

- ✅ History API поддержка
- ✅ Обработка массивов, объектов, чисел, булевых значений
- ✅ Валидация с Zod схемами (опционально)
- ✅ Утилитарные функции для трансформации
- ✅ Обработка ошибок и состояния загрузки

## Быстрый старт

### Базовое использование

```typescript
import { useUrlSync } from './useUrlSync';

// Определяем интерфейс состояния
interface FiltersState {
  search: string;
  category: string;
  page: number;
}

// Начальное состояние
const initialState: FiltersState = {
  search: '',
  category: 'all',
  page: 1,
};

// В Vue компоненте
export default defineComponent({
  setup() {
    const { state, updateState, resetState } = useUrlSync(initialState);

    // Обновление состояния
    const setSearch = (search: string) => updateState({ search });
    const setCategory = (category: string) => updateState({ category });

    return {
      filters: state,
      setSearch,
      setCategory,
      resetFilters: resetState,
    };
  },
});
```

### Использование с префиксом

```typescript
import { useUrlSync, DEFAULT_URL_SYNC_CONFIG } from './useUrlSync';

const { state, updateState } = useUrlSync(initialState, {
  prefix: DEFAULT_URL_SYNC_CONFIG.prefixes.filter, // 'f'
  debounce: 500,
});

// URL: /page?f_search=keyword&f_category=tech&f_page=2
```

### Кастомные transform функции

```typescript
const { state, updateState } = useUrlSync(
  { tags: [], settings: {} },
  {
    transform: {
      serialize: value => {
        if (Array.isArray(value)) return value.join('|');
        if (typeof value === 'object') return JSON.stringify(value);
        return String(value);
      },
      deserialize: value => {
        if (value.includes('|')) return value.split('|');
        if (value.startsWith('{')) return JSON.parse(value);
        return value;
      },
    },
  }
);
```

### Готовые композаблы

Используйте готовые композаблы из `useUrlSyncExample.ts`:

```typescript
import { useSearchUrlSync, usePaginationUrlSync } from './useUrlSyncExample';

// Поиск с URL синхронизацией
const { search, setSearch, clearSearch } = useSearchUrlSync('initial search');

// Пагинация с URL синхронизацией
const { page, perPage, setPage, nextPage, prevPage } = usePaginationUrlSync(1, 20);
```

## API Reference

### useUrlSync(initialState, options)

**Параметры:**

- `initialState: T` - Начальное состояние
- `options?: UseUrlSyncOptions<T>` - Опции настройки

**Опции:**

```typescript
interface UseUrlSyncOptions<T> {
  debounce?: number; // Задержка debounce (по умолчанию 300ms)
  prefix?: string; // Префикс для namespace
  transform?: {
    // Функции трансформации
    serialize?: (value: any) => string;
    deserialize?: (value: string) => any;
  };
  validation?: ZodSchema<T>; // Zod схема для валидации
  history?: boolean; // Управление историей браузера (по умолчанию true)
}
```

**Возвращает:**

```typescript
interface UseUrlSyncReturn<T> {
  state: Readonly<Ref<T>>; // Реактивное состояние
  updateState: (updates: Partial<T>) => void; // Обновление состояния
  resetState: () => void; // Сброс к начальному
  isLoading: Ref<boolean>; // Индикатор загрузки
  errors: Ref<Record<string, string>>; // Ошибки валидации
  urlParams: URLSearchParams; // Прямой доступ к URL
}
```

### Утилитарные функции

```typescript
import { urlSyncUtils } from './useUrlSync';

// Для массивов
const arrayTransform = urlSyncUtils.arrayTransform(',');

// Для объектов
const objectTransform = urlSyncUtils.objectTransform();

// Для чисел
const numberTransform = urlSyncUtils.numberTransform(0);

// Для булевых значений
const booleanTransform = urlSyncUtils.booleanTransform();
```

## Примеры интеграции

### С существующим Pinia Store

```typescript
// stores/creatives.ts
import { useUrlSync } from '../composables/useUrlSync';

export const useFiltersStore = defineStore('filters', () => {
  // Существующий код...

  // Добавляем URL синхронизацию
  let urlSync: ReturnType<typeof useUrlSync> | null = null;

  function initUrlSync() {
    urlSync = useUrlSync(
      {
        searchKeyword: filters.searchKeyword,
        country: filters.country,
        sortBy: filters.sortBy,
      },
      {
        prefix: 'cr',
        debounce: 300,
      }
    );

    // Синхронизация URL -> Store
    watch(
      urlSync.state,
      newState => {
        if (newState.searchKeyword !== filters.searchKeyword) {
          setSearchKeyword(newState.searchKeyword);
        }
        // ... остальные синхронизации
      },
      { deep: true }
    );
  }

  return {
    // ... существующий API
    initUrlSync,
    urlState: readonly(urlSync?.state),
  };
});
```

### В Vue компоненте с Composition API

```vue
<template>
  <div>
    <input :value="filters.search" @input="setSearch($event.target.value)" placeholder="Поиск..." />
    <select :value="filters.category" @change="setCategory($event.target.value)">
      <option value="all">Все категории</option>
      <option value="tech">Технологии</option>
    </select>
    <button @click="resetFilters">Сбросить</button>
  </div>
</template>

<script setup lang="ts">
import { useUrlSync } from '../composables/useUrlSync';

const {
  state: filters,
  updateState,
  resetState,
} = useUrlSync(
  {
    search: '',
    category: 'all',
    page: 1,
  },
  {
    prefix: 'f',
    debounce: 300,
  }
);

const setSearch = (search: string) => updateState({ search, page: 1 });
const setCategory = (category: string) => updateState({ category, page: 1 });
const resetFilters = resetState;
</script>
```

## Лучшие практики

### 1. Используйте префиксы для namespace

```typescript
const filtersUrl = useUrlSync(state, { prefix: 'f' });
const paginationUrl = useUrlSync(state, { prefix: 'p' });
```

### 2. Настраивайте debounce под задачи

```typescript
// Для поиска - больше задержка
const searchUrl = useUrlSync(state, { debounce: 500 });

// Для пагинации - меньше задержка
const paginationUrl = useUrlSync(state, { debounce: 100 });
```

### 3. Используйте transform для сложных типов

```typescript
const url = useUrlSync(state, {
  transform: {
    serialize: value => {
      if (Array.isArray(value)) return value.join(',');
      return String(value);
    },
    deserialize: value => {
      if (value.includes(',')) return value.split(',');
      return value;
    },
  },
});
```

### 4. Добавляйте валидацию для критичных параметров

```typescript
import { z } from 'zod';

const schema = z.object({
  page: z.number().min(1).max(1000),
  search: z.string().max(100),
});

const url = useUrlSync(state, { validation: schema });
```

## Производительность

- **Debouncing**: Предотвращает частые обновления URL
- **Selective sync**: Синхронизируются только измененные параметры
- **Memory cleanup**: Автоматическая очистка при unmount
- **Tree-shaking**: Только используемые функции из @vueuse/core

## Совместимость

- ✅ Vue 3.x
- ✅ @vueuse/core 13.x+
- ✅ TypeScript 4.x+
- ✅ Все современные браузеры с History API

## Дальнейшее развитие (Фаза 2)

В следующих фазах будут добавлены:

- Специализированные композаблы для креативов
- URL Manager Service для координации множественных компонентов
- Advanced validation и схемы
- Performance optimization layer
