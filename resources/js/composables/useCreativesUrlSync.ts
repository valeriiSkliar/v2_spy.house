// composables/useCreativesUrlSync.ts  
// Оптимизированная URL синхронизация на базе @vueuse/core с кастомной сериализацией

import type { FilterState, TabValue, UrlSyncParams } from '@/types/creatives.d';
import { CREATIVES_CONSTANTS, isValidTabValue } from '@/types/creatives.d';
import { useUrlSearchParams } from '@vueuse/core';
import debounce from 'lodash.debounce';
import { computed, nextTick, ref, type Ref } from 'vue';

/**
 * Результат композабла для синхронизации креативов с URL
 */
export interface UseCreativesUrlSyncReturn {
  // Состояние
  urlParams: ReturnType<typeof useUrlSearchParams>;
  state: Readonly<Ref<UrlSyncParams>>;
  isEnabled: Ref<boolean>;
  
  // Методы синхронизации
  syncFiltersToUrl: (filters: FilterState, activeTab: TabValue, page?: number) => void;
  syncUrlToFilters: () => { filters: Partial<FilterState>; activeTab: TabValue; page?: number };
  
  // Утилиты
  hasUrlParams: () => boolean;
  clearUrlParams: () => void;
  getFilterUpdates: () => Partial<FilterState>;
  getActiveTabFromUrl: () => TabValue;
  getPageFromUrl: () => number;
}

/**
 * Композабл для синхронизации фильтров креативов с URL
 * Использует @vueuse/core с кастомной логикой сериализации для оптимального URL
 */
export function useCreativesUrlSync(): UseCreativesUrlSyncReturn {
  // Инициализация URL параметров с встроенной оптимизацией
  const urlParams = useUrlSearchParams('history', {
    removeFalsyValues: true,
    removeNullishValues: true,
  });

  // Флаг активности синхронизации
  const isEnabled = ref(false);
  
  // Состояние URL параметров (readonly для внешнего использования)
  const state = computed(() => urlParams as UrlSyncParams);

  /**
   * Маппинг между ключами фильтров и URL параметрами
   */
  const FILTER_URL_MAPPING = {
    searchKeyword: 'cr_searchKeyword',
    country: 'cr_country', 
    dateCreation: 'cr_dateCreation',
    sortBy: 'cr_sortBy',
    periodDisplay: 'cr_periodDisplay',
    onlyAdult: 'cr_onlyAdult',
    advertisingNetworks: 'cr_advertisingNetworks',
    languages: 'cr_languages',
    operatingSystems: 'cr_operatingSystems',
    browsers: 'cr_browsers',
    devices: 'cr_devices',
    imageSizes: 'cr_imageSizes',
    perPage: 'cr_perPage',
  } as const;

  const TAB_URL_KEY = 'cr_activeTab';
  const PAGE_URL_KEY = 'cr_page';

  /**
   * Определяет тип поля для десериализации
   */
  const getFieldType = (key: keyof FilterState): 'string' | 'boolean' | 'array' | 'number' => {
    const arrayFields: (keyof FilterState)[] = [
      'advertisingNetworks', 'languages', 'operatingSystems', 
      'browsers', 'devices', 'imageSizes'
    ];
    
    if (arrayFields.includes(key)) return 'array';
    if (key === 'onlyAdult') return 'boolean';
    if (key === 'perPage') return 'number';
    return 'string';
  };

  /**
   * Десериализует значение из URL используя встроенные механизмы
   */
  const parseUrlValue = (urlValue: string, targetType: 'string' | 'boolean' | 'array' | 'number'): any => {
    if (!urlValue) return undefined;

    switch (targetType) {
      case 'boolean':
        return urlValue === '1' || urlValue === 'true';
      
      case 'array':
        return urlValue.split(',').filter(Boolean);
      
      case 'number':
        const numValue = parseInt(urlValue, 10);
        return !isNaN(numValue) ? numValue : CREATIVES_CONSTANTS.DEFAULT_PAGE_SIZE;
      
      case 'string':
      default:
        return urlValue;
    }
  };

  /**
   * Сериализует значение для URL с использованием логики @vueuse/core
   */
  const serializeValue = (value: any): string | undefined => {
    if (value === null || value === undefined || value === '') {
      return undefined;
    }

    if (Array.isArray(value)) {
      return value.length > 0 ? value.join(',') : undefined;
    }

    if (typeof value === 'boolean') {
      return value ? '1' : undefined; // Записываем только true значения
    }

    if (typeof value === 'number') {
      return value !== CREATIVES_CONSTANTS.DEFAULT_PAGE_SIZE ? String(value) : undefined;
    }

    const stringValue = String(value);
    return stringValue !== 'default' ? stringValue : undefined;
  };

  /**
   * Синхронизирует фильтры в URL (Store -> URL)
   * Использует встроенные возможности @vueuse/core с кастомной предобработкой
   */
  const syncFiltersToUrl = (filters: FilterState, activeTab: TabValue, page?: number): void => {
    if (!isEnabled.value) return;

    // Обновляем фильтры с кастомной сериализацией
    Object.entries(FILTER_URL_MAPPING).forEach(([filterKey, urlKey]) => {
      const value = filters[filterKey as keyof FilterState];
      const serialized = serializeValue(value);
      
      if (serialized !== undefined) {
        urlParams[urlKey] = serialized;
      } else {
        delete urlParams[urlKey];
      }
    });

    // Обновляем активную вкладку
    if (activeTab !== 'push') { // push - дефолтная вкладка
      urlParams[TAB_URL_KEY] = activeTab;
    } else {
      delete urlParams[TAB_URL_KEY];
    }

    // Обновляем страницу пагинации
    if (page && page > 1) {
      urlParams[PAGE_URL_KEY] = String(page);
    } else {
      delete urlParams[PAGE_URL_KEY];
    }
  };

  /**
   * Синхронизирует URL в фильтры (URL -> Store)
   */
  const syncUrlToFilters = (): { filters: Partial<FilterState>; activeTab: TabValue; page?: number } => {
    const filterUpdates: Partial<FilterState> = {};

    // Обрабатываем фильтры
    Object.entries(FILTER_URL_MAPPING).forEach(([filterKey, urlKey]) => {
      const urlValue = urlParams[urlKey];
      if (urlValue) {
        const fieldType = getFieldType(filterKey as keyof FilterState);
        const deserializedValue = parseUrlValue(String(urlValue), fieldType);
        
        if (deserializedValue !== undefined) {
          (filterUpdates as any)[filterKey] = deserializedValue;
        }
      }
    });

    // Обрабатываем активную вкладку
    const activeTab = getActiveTabFromUrl();

    // Обрабатываем страницу
    const page = getPageFromUrl();

    return { filters: filterUpdates, activeTab, page };
  };

  /**
   * Получает обновления только для фильтров
   */
  const getFilterUpdates = (): Partial<FilterState> => {
    const { filters } = syncUrlToFilters();
    return filters;
  };

  /**
   * Получает активную вкладку из URL
   */
  const getActiveTabFromUrl = (): TabValue => {
    const urlTab = urlParams[TAB_URL_KEY];
    return (urlTab && isValidTabValue(String(urlTab))) ? String(urlTab) as TabValue : 'push';
  };

  /**
   * Получает номер страницы из URL
   */
  const getPageFromUrl = (): number => {
    const urlPage = urlParams[PAGE_URL_KEY];
    if (urlPage) {
      const pageNumber = parseInt(String(urlPage), 10);
      return (pageNumber > 0) ? pageNumber : 1;
    }
    return 1;
  };

  /**
   * Проверяет наличие URL параметров креативов
   */
  const hasUrlParams = (): boolean => {
    return Object.keys(urlParams).some(key => key.startsWith(CREATIVES_CONSTANTS.URL_PREFIX + '_'));
  };

  /**
   * Очищает все URL параметры креативов
   */
  const clearUrlParams = (): void => {
    Object.keys(urlParams).forEach(key => {
      if (key.startsWith(CREATIVES_CONSTANTS.URL_PREFIX + '_')) {
        delete urlParams[key];
      }
    });
  };

  /**
   * Включает синхронизацию с небольшой задержкой
   */
  const enableSync = async (): Promise<void> => {
    await nextTick();
    isEnabled.value = true;
  };

  /**
   * Отключает синхронизацию
   */
  // const disableSync = (): void => {
  //   isEnabled.value = false;
  // };

  // Автоматическое включение синхронизации через небольшую задержку
  // Это позволяет компонентам инициализироваться до начала синхронизации
  debounce(enableSync, 100);

  return {
    // Состояние
    urlParams,
    state,
    isEnabled,
    
    // Методы синхронизации
    syncFiltersToUrl,
    syncUrlToFilters,
    
    // Утилиты
    hasUrlParams,
    clearUrlParams,
    getFilterUpdates,
    getActiveTabFromUrl,
    getPageFromUrl,
  };
}

/**
 * Утилитарные функции для URL синхронизации
 */
export const urlSyncUtils = {
  /**
   * Создает debounced функцию синхронизации
   */
  createDebouncedSync: (syncFn: Function, delay = CREATIVES_CONSTANTS.DEBOUNCE_DELAY) => {
    let timeoutId: NodeJS.Timeout;
    
    return (...args: any[]) => {
      clearTimeout(timeoutId);
      timeoutId = setTimeout(() => syncFn(...args), delay);
    };
  },

  /**
   * Проверяет отличаются ли состояния фильтров
   */
  hasFiltersChanged: (current: Partial<FilterState>, previous: Partial<FilterState>): boolean => {
    const currentKeys = Object.keys(current);
    const previousKeys = Object.keys(previous);
    
    if (currentKeys.length !== previousKeys.length) return true;
    
    return currentKeys.some(key => {
      const currentValue = (current as any)[key];
      const previousValue = (previous as any)[key];
      
      if (Array.isArray(currentValue) && Array.isArray(previousValue)) {
        return JSON.stringify(currentValue.sort()) !== JSON.stringify(previousValue.sort());
      }
      
      return currentValue !== previousValue;
    });
  },

  /**
   * Фильтрует валидные URL параметры креативов
   */
  filterValidParams: (params: Record<string, any>): UrlSyncParams => {
    const validParams: UrlSyncParams = {};
    
    Object.entries(params).forEach(([key, value]) => {
      if (key.startsWith(CREATIVES_CONSTANTS.URL_PREFIX + '_') && value !== undefined) {
        (validParams as any)[key] = value;
      }
    });
    
    return validParams;
  },

  /**
   * Логирование изменений для отладки (только в dev режиме)
   */
  logSyncChanges: (source: 'url' | 'filters', changes: any): void => {
    if (process.env.NODE_ENV === 'development') {
      console.log(`🔄 URL Sync [${source}]:`, changes);
    }
  }
};