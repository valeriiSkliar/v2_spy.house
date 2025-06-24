import { DEFAULT_URL_SYNC_CONFIG, urlSyncUtils, useUrlSync } from './useUrlSync';

/**
 * Пример использования useUrlSync композабла
 * Демонстрирует основные возможности URL синхронизации
 */

// Интерфейс для примера состояния фильтров
interface ExampleFiltersState {
  searchKeyword: string;
  country: string;
  categories: string[];
  isAdult: boolean;
  dateRange: string;
  sortBy: string;
  page: number;
}

// Начальное состояние
const initialFiltersState: ExampleFiltersState = {
  searchKeyword: '',
  country: 'All Countries',
  categories: [],
  isAdult: false,
  dateRange: 'all',
  sortBy: 'creation',
  page: 1,
};

/**
 * Пример 1: Базовое использование без дополнительных опций
 */
export function useBasicUrlSync() {
  return useUrlSync(initialFiltersState);
}

/**
 * Пример 2: Использование с префиксом для namespace
 */
export function usePrefixedUrlSync() {
  return useUrlSync(initialFiltersState, {
    prefix: DEFAULT_URL_SYNC_CONFIG.prefixes.filter, // 'f'
    debounce: 500, // увеличенный debounce
  });
}

/**
 * Пример 3: Использование с кастомными transform функциями
 */
export function useCustomTransformUrlSync() {
  return useUrlSync(initialFiltersState, {
    prefix: 'creative',
    debounce: 200,
    transform: {
      // Кастомная сериализация для массивов
      serialize: (value: any) => {
        if (Array.isArray(value)) {
          return value.join('|'); // используем | вместо запятой
        }
        return String(value);
      },
      // Кастомная десериализация
      deserialize: (value: string) => {
        if (value.includes('|')) {
          return value.split('|').filter(Boolean);
        }
        return value;
      },
    },
  });
}

/**
 * Пример 4: Использование с утилитарными transform функциями
 */
export function useUtilsTransformUrlSync() {
  return useUrlSync(
    {
      tags: [] as string[],
      settings: {} as Record<string, any>,
      count: 0,
      enabled: false,
    },
    {
      prefix: 'util',
      transform: {
        serialize: (value: any) => {
          if (Array.isArray(value)) {
            return urlSyncUtils.arrayTransform().serialize(value);
          }
          if (typeof value === 'object' && value !== null) {
            return urlSyncUtils.objectTransform().serialize(value);
          }
          if (typeof value === 'number') {
            return urlSyncUtils.numberTransform().serialize(value);
          }
          if (typeof value === 'boolean') {
            return urlSyncUtils.booleanTransform().serialize(value);
          }
          return String(value);
        },
        deserialize: (value: string) => {
          // Простая логика определения типа по значению
          if (value.includes(',')) {
            return urlSyncUtils.arrayTransform().deserialize(value);
          }
          if (value.startsWith('{') || value.startsWith('[')) {
            return urlSyncUtils.objectTransform().deserialize(value);
          }
          if (value === '0' || value === '1' || value === 'true' || value === 'false') {
            return urlSyncUtils.booleanTransform().deserialize(value);
          }
          if (!isNaN(Number(value))) {
            return urlSyncUtils.numberTransform().deserialize(value);
          }
          return value;
        },
      },
    }
  );
}

/**
 * Пример 5: Простой композабл для поиска с URL синхронизацией
 */
export function useSearchUrlSync(initialSearch = '') {
  const { state, updateState, resetState, isLoading, errors } = useUrlSync(
    { search: initialSearch },
    {
      prefix: DEFAULT_URL_SYNC_CONFIG.prefixes.search, // 's'
      debounce: 300,
    }
  );

  // Удобные методы для работы с поиском
  const setSearch = (search: string) => {
    updateState({ search });
  };

  const clearSearch = () => {
    updateState({ search: '' });
  };

  return {
    search: state.value.search,
    setSearch,
    clearSearch,
    resetState,
    isLoading,
    errors,
  };
}

/**
 * Пример 6: Композабл для пагинации с URL синхронизацией
 */
export function usePaginationUrlSync(initialPage = 1, initialPerPage = 20) {
  const { state, updateState, resetState, isLoading, errors } = useUrlSync(
    { 
      page: initialPage, 
      perPage: initialPerPage 
    },
    {
      prefix: DEFAULT_URL_SYNC_CONFIG.prefixes.pagination, // 'p'
      debounce: 100, // быстрый отклик для пагинации
      transform: {
        serialize: (value: any) => String(value),
        deserialize: (value: string) => {
          const num = Number(value);
          return isNaN(num) ? 1 : Math.max(1, num);
        },
      },
    }
  );

  // Удобные методы для пагинации
  const setPage = (page: number) => {
    updateState({ page: Math.max(1, page) });
  };

  const setPerPage = (perPage: number) => {
    updateState({ perPage: Math.max(1, perPage), page: 1 }); // сброс на первую страницу
  };

  const nextPage = () => {
    setPage(state.value.page + 1);
  };

  const prevPage = () => {
    setPage(Math.max(1, state.value.page - 1));
  };

  return {
    page: state.value.page,
    perPage: state.value.perPage,
    setPage,
    setPerPage,
    nextPage,
    prevPage,
    resetState,
    isLoading,
    errors,
  };
}

/**
 * Пример использования в Vue компоненте:
 * 
 * ```vue
 * <script setup lang="ts">
 * import { useBasicUrlSync, useSearchUrlSync, usePaginationUrlSync } from './useUrlSyncExample';
 * 
 * // Базовые фильтры
 * const filters = useBasicUrlSync();
 * 
 * // Поиск
 * const { search, setSearch, clearSearch } = useSearchUrlSync();
 * 
 * // Пагинация
 * const { page, perPage, setPage, nextPage, prevPage } = usePaginationUrlSync();
 * 
 * // Методы для обновления фильтров
 * const updateCountry = (country: string) => {
 *   filters.updateState({ country });
 * };
 * 
 * const toggleAdultFilter = () => {
 *   filters.updateState({ isAdult: !filters.state.isAdult });
 * };
 * 
 * const addCategory = (category: string) => {
 *   const categories = [...filters.state.categories, category];
 *   filters.updateState({ categories });
 * };
 * </script>
 * ```
 */ 