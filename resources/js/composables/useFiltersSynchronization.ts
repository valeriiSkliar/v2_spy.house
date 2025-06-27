// composables/useFiltersSynchronization.ts
// Композабл для синхронизации фильтров между URL, Store и API

import type { UseCreativesUrlSyncReturn } from '@/composables/useCreativesUrlSync';
import type { FilterState, TabsState, UseCreativesReturn } from '@/types/creatives.d';
import { CREATIVES_CONSTANTS } from '@/types/creatives.d';
import { nextTick, Ref, ref, watchEffect } from 'vue';

/**
 * Результат композабла синхронизации фильтров
 */
export interface UseFiltersSynchronizationReturn {
  isEnabled: Ref<boolean>;
  initialize: () => Promise<void>;
  syncToUrl: () => void;
  syncFromUrl: () => void;
  disable: () => void;
  enable: () => void;
}

/**
 * Композабл для синхронизации фильтров
 * Связывает воедино URL, Store состояние и загрузку креативов
 */
export function useFiltersSynchronization(
  filters: FilterState,
  tabs: TabsState,
  urlSync: UseCreativesUrlSyncReturn,
  creativesComposable: UseCreativesReturn
): UseFiltersSynchronizationReturn {
  
  // ============================================================================
  // СОСТОЯНИЕ
  // ============================================================================
  
  const isEnabled = ref(false);
  const isInitialized = ref(false);
  const isSyncing = ref(false);
  
  // Счетчики для отладки циклов
  let syncToUrlCount = 0;
  let syncFromUrlCount = 0;
  
  // Таймеры для debounce
  let urlSyncTimer: NodeJS.Timeout | undefined;
  let loadCreativesTimer: NodeJS.Timeout | undefined;

  // ============================================================================
  // УТИЛИТАРНЫЕ ФУНКЦИИ
  // ============================================================================
  
  /**
   * Создает debounced функцию
   */
  function debounce<T extends (...args: any[]) => any>(
    func: T, 
    delay: number
  ): (...args: Parameters<T>) => void {
    let timeoutId: NodeJS.Timeout;
    
    return (...args: Parameters<T>) => {
      clearTimeout(timeoutId);
      timeoutId = setTimeout(() => func(...args), delay);
    };
  }
  
  /**
   * Сравнивает состояния фильтров (глубокое сравнение)
   */
  function areFiltersEqual(filters1: Partial<FilterState>, filters2: Partial<FilterState>): boolean {
    const keys1 = Object.keys(filters1);
    const keys2 = Object.keys(filters2);
    
    if (keys1.length !== keys2.length) return false;
    
    return keys1.every(key => {
      const value1 = (filters1 as any)[key];
      const value2 = (filters2 as any)[key];
      
      if (Array.isArray(value1) && Array.isArray(value2)) {
        return JSON.stringify([...value1].sort()) === JSON.stringify([...value2].sort());
      }
      
      return value1 === value2;
    });
  }
  
  /**
   * Логирует действия синхронизации (только в dev режиме)
   */
  function logSync(action: string, data?: any): void {
    if (process.env.NODE_ENV === 'development') {
      console.log(`🔄 FilterSync [${action}]:`, data);
    }
  }
  
  /**
   * Проверяет превышение лимитов для предотвращения циклов
   */
  function checkSyncLimits(): boolean {
    const maxSyncsPerSecond = 10;
    
    if (syncToUrlCount > maxSyncsPerSecond || syncFromUrlCount > maxSyncsPerSecond) {
      console.error('🚨 FilterSync: Обнаружен потенциальный цикл синхронизации');
      disable();
      return false;
    }
    
    return true;
  }
  
  /**
   * Сбрасывает счетчики синхронизации
   */
  function resetSyncCounters(): void {
    syncToUrlCount = 0;
    syncFromUrlCount = 0;
  }

  // ============================================================================
  // ОСНОВНЫЕ МЕТОДЫ СИНХРОНИЗАЦИИ
  // ============================================================================
  
  /**
   * Синхронизирует фильтры в URL
   */
  const syncToUrl = debounce((): void => {
    if (!isEnabled.value || isSyncing.value) return;
    
    if (!checkSyncLimits()) return;
    
    syncToUrlCount++;
    isSyncing.value = true;
    
    try {
      logSync('Store -> URL', { filters, activeTab: tabs.activeTab });
      urlSync.syncFiltersToUrl(filters, tabs.activeTab);
    } finally {
      isSyncing.value = false;
    }
  }, CREATIVES_CONSTANTS.DEBOUNCE_DELAY);
  
  /**
   * Синхронизирует фильтры из URL
   */
  const syncFromUrl = debounce((): void => {
    if (!isEnabled.value || isSyncing.value) return;
    
    if (!checkSyncLimits()) return;
    
    syncFromUrlCount++;
    isSyncing.value = true;
    
    try {
      const { filters: urlFilters, activeTab, page } = urlSync.syncUrlToFilters();
      
      logSync('URL -> Store', { urlFilters, activeTab, page });
      
      // Обновляем фильтры без triggering watchers
      Object.entries(urlFilters).forEach(([key, value]) => {
        const filterKey = key as keyof FilterState;
        if (value !== undefined && value !== null) {
          const currentValue = filters[filterKey];
          
          // Проверяем изменения
          let hasChanged = false;
          if (Array.isArray(value) && Array.isArray(currentValue)) {
            hasChanged = !areFiltersEqual({ [key]: value }, { [key]: currentValue });
          } else {
            hasChanged = currentValue !== value;
          }
          
          if (hasChanged) {
            if (Array.isArray(value)) {
              (filters[filterKey] as any) = [...value];
            } else {
              (filters[filterKey] as any) = value;
            }
          }
        }
      });
      
      // Обновляем активную вкладку
      if (activeTab !== tabs.activeTab) {
        tabs.activeTab = activeTab;
      }
      
      // Если есть страница в URL, загружаем конкретную страницу
      if (page && page > 1) {
        const creativesFilters = creativesComposable.mapFiltersToCreativesFilters(
          filters, 
          tabs.activeTab, 
          page
        );
        
        creativesComposable.loadCreativesWithFilters(creativesFilters);
      }
      
    } finally {
      isSyncing.value = false;
    }
  }, CREATIVES_CONSTANTS.DEBOUNCE_DELAY / 2); // Более быстрая реакция на URL изменения
  
  /**
   * Загружает креативы с debounce
   */
  const loadCreativesDebounced = debounce(async (): Promise<void> => {
    if (!isEnabled.value) return;
    
    try {
      const creativesFilters = creativesComposable.mapFiltersToCreativesFilters(
        filters, 
        tabs.activeTab, 
        1 // Всегда загружаем первую страницу при изменении фильтров
      );
      
      logSync('Loading creatives', creativesFilters);
      
      await creativesComposable.loadCreativesWithFilters(creativesFilters);
      
    } catch (error) {
      console.error('Ошибка загрузки креативов в FilterSync:', error);
    }
  }, CREATIVES_CONSTANTS.DEBOUNCE_DELAY);

  // ============================================================================
  // МЕТОДЫ УПРАВЛЕНИЯ
  // ============================================================================
  
  /**
   * Инициализирует синхронизацию
   */
  async function initialize(): Promise<void> {
    if (isInitialized.value) return;
    
    logSync('Initializing');
    
    // Проверяем есть ли параметры в URL
    const hasUrlParams = urlSync.hasUrlParams();
    
    if (hasUrlParams) {
      // Загружаем состояние из URL
      logSync('Loading from URL');
      syncFromUrl();
    }
    
    await nextTick();
    
    // Настраиваем watchers
    setupWatchers();
    
    // Включаем синхронизацию
    enable();
    
    // Сброс счетчиков каждую секунду
    setInterval(resetSyncCounters, 1000);
    
    // Если в URL есть параметры или активные фильтры, загружаем креативы
    if (hasUrlParams || hasActiveFilters()) {
      await loadCreativesDebounced();
    } else {
      // Иначе синхронизируем текущее состояние с URL
      syncToUrl();
    }
    
    isInitialized.value = true;
    logSync('Initialized');
  }
  
  /**
   * Включает синхронизацию
   */
  function enable(): void {
    if (!isEnabled.value) {
      isEnabled.value = true;
      urlSync.isEnabled.value = true;
      logSync('Enabled');
    }
  }
  
  /**
   * Отключает синхронизацию
   */
  function disable(): void {
    if (isEnabled.value) {
      isEnabled.value = false;
      urlSync.isEnabled.value = false;
      
      // Очищаем таймеры
      clearTimeout(urlSyncTimer);
      clearTimeout(loadCreativesTimer);
      
      logSync('Disabled');
    }
  }
  
  /**
   * Проверяет есть ли активные фильтры
   */
  function hasActiveFilters(): boolean {
    return Object.entries(filters).some(([key, value]) => {
      if (['isDetailedVisible', 'savedSettings'].includes(key)) return false;
      
      if (Array.isArray(value)) return value.length > 0;
      if (typeof value === 'boolean') return value;
      if (typeof value === 'string') return value !== '' && value !== 'default';
      
      return false;
    });
  }

  // ============================================================================
  // WATCHERS
  // ============================================================================
  
  /**
   * Настраивает watchers для автоматической синхронизации
   */
  function setupWatchers(): void {
    // Watcher для синхронизации Store -> URL
    watchEffect(() => {
      if (!isEnabled.value || !isInitialized.value) return;
      
      // Отслеживаем изменения фильтров (исключая isDetailedVisible)
      const filtersToWatch = { ...filters };
      delete (filtersToWatch as any).isDetailedVisible;
      delete (filtersToWatch as any).savedSettings;
      
      // Отслеживаем активную вкладку
      const activeTab = tabs.activeTab;
      
      // Синхронизируем в URL
      syncToUrl();
    });
    
    // Watcher для синхронизации URL -> Store  
    watchEffect(() => {
      if (!isEnabled.value || !isInitialized.value) return;
      
      // Отслеживаем изменения URL состояния
      const urlState = urlSync.state.value;
      
      // Синхронизируем из URL
      if (Object.keys(urlState).length > 0) {
        syncFromUrl();
      }
    });
    
    // Watcher для автоматической загрузки креативов
    watchEffect(() => {
      if (!isEnabled.value || !isInitialized.value) return;
      
      // Отслеживаем изменения значимых фильтров
      const watchedFilters = {
        searchKeyword: filters.searchKeyword,
        country: filters.country,
        dateCreation: filters.dateCreation,
        sortBy: filters.sortBy,
        periodDisplay: filters.periodDisplay,
        advertisingNetworks: [...filters.advertisingNetworks],
        languages: [...filters.languages],
        operatingSystems: [...filters.operatingSystems],
        browsers: [...filters.browsers],
        devices: [...filters.devices],
        imageSizes: [...filters.imageSizes],
        onlyAdult: filters.onlyAdult,
        perPage: filters.perPage,
        activeTab: tabs.activeTab
      };
      
      // Загружаем креативы
      loadCreativesDebounced();
    });
    
    logSync('Watchers setup complete');
  }

  // ============================================================================
  // ВОЗВРАЩАЕМЫЙ ОБЪЕКТ
  // ============================================================================
  
  return {
    isEnabled,
    initialize,
    syncToUrl,
    syncFromUrl,
    disable,
    enable,
  };
}