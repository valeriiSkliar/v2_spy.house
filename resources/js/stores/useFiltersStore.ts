// stores/useFiltersStore.ts
// Оптимизированный store с разделением ответственности

import { useCreatives } from '@/composables/useCreatives';
import { useCreativesUrlSync } from '@/composables/useCreativesUrlSync';
import { useFiltersSynchronization } from '@/composables/useFiltersSynchronization';
import type {
    FilterOption,
    FilterState,
    MultiSelectFilterKey,
    SelectOptions,
    TabOptions,
    TabsState
} from '@/types/creatives.d';
import { DEFAULT_FILTERS, DEFAULT_TABS } from '@/types/creatives.d';
import { defineStore } from 'pinia';
import { computed, reactive, ref, shallowRef } from 'vue';

export const useCreativesFiltersStore = defineStore('creativesFilters', () => {
  // ============================================================================
  // СОСТОЯНИЕ
  // ============================================================================
  
  // Основное состояние фильтров (single source of truth)
  const filters = reactive<FilterState>({ ...DEFAULT_FILTERS });
  
  // Состояние вкладок (single source of truth)  
  const tabs = reactive<TabsState>({ ...DEFAULT_TABS });
  
  // Опции для селектов (shallow ref для производительности)
  const countryOptions = shallowRef<FilterOption[]>([]);
  const sortOptions = shallowRef<FilterOption[]>([]);
  const dateRanges = shallowRef<FilterOption[]>([]);
  
  // Опции для мультиселектов
  const multiSelectOptions = reactive<{
    advertisingNetworks: FilterOption[];
    languages: FilterOption[];
    operatingSystems: FilterOption[];
    browsers: FilterOption[];
    devices: FilterOption[];
    imageSizes: FilterOption[];
  }>({
    advertisingNetworks: [],
    languages: [],
    operatingSystems: [],
    browsers: [],
    devices: [],
    imageSizes: [],
  });
  
  // Переводы
  const translations = ref<Record<string, string>>({});
  
  // Флаги состояния
  const isInitialized = ref(false);
  const isInitializing = ref(false);

  // ============================================================================
  // КОМПОЗАБЛЫ
  // ============================================================================
  
  // Управление креативами
  const creativesComposable = useCreatives();
  
  // URL синхронизация
  const urlSync = useCreativesUrlSync();
  
  // Синхронизация фильтров
  const filterSync = useFiltersSynchronization(filters, tabs, urlSync, creativesComposable);

  // ============================================================================
  // COMPUTED СВОЙСТВА
  // ============================================================================
  
  // Опции для мультиселектов (computed для реактивности)
  const advertisingNetworksOptions = computed(() => multiSelectOptions.advertisingNetworks);
  const languagesOptions = computed(() => multiSelectOptions.languages);
  const operatingSystemsOptions = computed(() => multiSelectOptions.operatingSystems);
  const browsersOptions = computed(() => multiSelectOptions.browsers);
  const devicesOptions = computed(() => multiSelectOptions.devices);
  const imageSizesOptions = computed(() => multiSelectOptions.imageSizes);
  
  // Опции для вкладок
  const tabOptions = computed(() => {
    return tabs.availableTabs.map(tabValue => ({
      value: tabValue,
      label: getTranslation(`tabs.${tabValue}`, tabValue),
      count: tabs.tabCounts[tabValue] || 0
    }));
  });
  
  const currentTabOption = computed(() => {
    return tabOptions.value.find(tab => tab.value === tabs.activeTab);
  });
  
  // Активные фильтры
  const hasActiveFilters = computed(() => {
    return Object.entries(filters).some(([key, value]) => {
      // Исключаем технические поля
      if (['isDetailedVisible', 'savedSettings'].includes(key)) return false;
      
      if (Array.isArray(value)) return value.length > 0;
      if (typeof value === 'boolean') return value;
      if (typeof value === 'string') return value !== '' && value !== 'default';
      
      return false;
    });
  });
  
  // Количество активных фильтров
  const activeFiltersCount = computed(() => {
    return creativesComposable.meta.value.activeFiltersCount;
  });

  // ============================================================================
  // МЕТОДЫ УПРАВЛЕНИЯ ОПЦИЯМИ
  // ============================================================================
  
  /**
   * Устанавливает опции для селектов
   */
  function setSelectOptions(options: Partial<SelectOptions>): void {
    if (options.countries) countryOptions.value = options.countries;
    if (options.sortOptions) sortOptions.value = options.sortOptions;
    if (options.dateRanges) dateRanges.value = options.dateRanges;
    
    // Мультиселекты
    if (options.advertisingNetworks) {
      multiSelectOptions.advertisingNetworks = normalizeOptions(options.advertisingNetworks);
    }
    if (options.languages) {
      multiSelectOptions.languages = normalizeOptions(options.languages);
    }
    if (options.operatingSystems) {
      multiSelectOptions.operatingSystems = normalizeOptions(options.operatingSystems);
    }
    if (options.browsers) {
      multiSelectOptions.browsers = normalizeOptions(options.browsers);
    }
    if (options.devices) {
      multiSelectOptions.devices = normalizeOptions(options.devices);
    }
    if (options.imageSizes) {
      multiSelectOptions.imageSizes = normalizeOptions(options.imageSizes);
    }
  }
  
  /**
   * Устанавливает опции для вкладок
   */
  function setTabOptions(options: Partial<TabOptions>): void {
    if (options.availableTabs) {
      tabs.availableTabs = [...options.availableTabs];
    }
    if (options.tabCounts) {
      tabs.tabCounts = { ...options.tabCounts };
    }
    if (options.activeTab && tabs.availableTabs.includes(options.activeTab)) {
      tabs.activeTab = options.activeTab;
    }
  }
  
  /**
   * Нормализует опции в единый формат
   */
  function normalizeOptions(options: any): FilterOption[] {
    if (Array.isArray(options)) {
      return options;
    }
    
    if (typeof options === 'object') {
      return Object.entries(options).map(([value, label]) => ({
        value,
        label: String(label),
      }));
    }
    
    return [];
  }

  // ============================================================================
  // МЕТОДЫ УПРАВЛЕНИЯ ПЕРЕВОДАМИ  
  // ============================================================================
  
  /**
   * Устанавливает переводы
   */
  function setTranslations(translationsData: Record<string, string>): void {
    translations.value = { ...translationsData };
  }
  
  /**
   * Получает перевод с fallback
   */
  function getTranslation(key: string, fallback: string = key): string {
    return translations.value[key] || fallback;
  }

  // ============================================================================
  // МЕТОДЫ УПРАВЛЕНИЯ ФИЛЬТРАМИ
  // ============================================================================
  
  /**
   * Обновляет отдельный фильтр
   */
  function updateFilter<K extends keyof FilterState>(key: K, value: FilterState[K]): void {
    if (filters[key] !== value) {
      filters[key] = value;
    }
  }
  
  /**
   * Обновляет несколько фильтров одновременно
   */
  function updateFilters(updates: Partial<FilterState>): void {
    Object.entries(updates).forEach(([key, value]) => {
      const filterKey = key as keyof FilterState;
      if (filters[filterKey] !== value) {
        filters[filterKey] = value as never; // TODO: fix this
      }
    });
  }
  
  /**
   * Переключает видимость детальных фильтров
   */
  function toggleDetailedFilters(): void {
    filters.isDetailedVisible = !filters.isDetailedVisible;
  }
  
  /**
   * Переключает adult фильтр
   */
  function toggleAdultFilter(): void {
    filters.onlyAdult = !filters.onlyAdult;
  }
  
  /**
   * Добавляет значение в мультиселект
   */
  function addToMultiSelect(field: MultiSelectFilterKey, value: string): void {
    const currentValues = filters[field];
    if (!currentValues.includes(value)) {
      filters[field] = [...currentValues, value];
    }
  }
  
  /**
   * Удаляет значение из мультиселекта
   */
  function removeFromMultiSelect(field: MultiSelectFilterKey, value: string): void {
    const currentValues = filters[field];
    filters[field] = currentValues.filter(item => item !== value);
  }
  
  /**
   * Сбрасывает фильтры к дефолтным значениям
   */
  function resetFilters(): void {
    Object.assign(filters, DEFAULT_FILTERS);
  }

  // ============================================================================
  // МЕТОДЫ УПРАВЛЕНИЯ ВКЛАДКАМИ
  // ============================================================================
  
  /**
   * Устанавливает активную вкладку
   */
  function setActiveTab(tabValue: string): void {
    if (tabs.availableTabs.includes(tabValue as any) && tabs.activeTab !== tabValue) {
      const previousTab = tabs.activeTab;
      tabs.activeTab = tabValue as any;
      
      // Эмитим событие для других компонентов
      const event = new CustomEvent('creatives:tab-changed', {
        detail: {
          previousTab,
          currentTab: tabValue,
          tabOption: currentTabOption.value
        }
      });
      document.dispatchEvent(event);
    }
  }
  
  /**
   * Обновляет счетчики вкладок
   */
  function setTabCounts(counts: Record<string, string | number>): void {
    tabs.tabCounts = { ...tabs.tabCounts, ...counts };
  }
  
  /**
   * Сбрасывает вкладки к дефолтным значениям
   */
  function resetTabs(): void {
    Object.assign(tabs, DEFAULT_TABS);
  }

  // ============================================================================
  // МЕТОДЫ ИНИЦИАЛИЗАЦИИ
  // ============================================================================
  
  /**
   * Инициализирует store
   */
  async function initializeFilters(
    propsFilters?: Partial<FilterState>,
    selectOptions?: Partial<SelectOptions>,
    translationsData?: Record<string, string>,
    tabsOptions?: Partial<TabOptions>
  ): Promise<void> {
    if (isInitialized.value || isInitializing.value) return;
    
    isInitializing.value = true;
    
    try {
      // 1. Устанавливаем опции и переводы
      if (selectOptions) setSelectOptions(selectOptions);
      if (tabsOptions) setTabOptions(tabsOptions);
      if (translationsData) setTranslations(translationsData);
      
      // 2. Применяем props фильтры
      if (propsFilters) updateFilters(propsFilters);
      
      // 3. Инициализируем синхронизацию
      await filterSync.initialize();
      
      // 4. Загружаем креативы если есть параметры в URL или активные фильтры
      if (urlSync.hasUrlParams() || hasActiveFilters.value) {
        await loadCreativesFromStore();
      }
      
      isInitialized.value = true;
      
    } finally {
      isInitializing.value = false;
    }
  }
  
  /**
   * Сохраняет настройки (заглушка для будущей реализации)
   */
  function saveSettings(): void {
    // TODO: Реализовать сохранение настроек на сервер
    console.log('Сохранение настроек:', filters);
  }

  // ============================================================================
  // ПЕРЕОПРЕДЕЛЕНИЯ МЕТОДОВ КОМПОЗАБЛОВ
  // ============================================================================
  
  /**
   * Переопределяем loadCreatives для автоматического использования текущих фильтров store
   */
  async function loadCreativesFromStore(page: number = 1): Promise<void> {
    const creativesFilters = creativesComposable.mapFiltersToCreativesFilters(
      filters,
      tabs.activeTab,
      page
    );
    
    await creativesComposable.loadCreativesWithFilters(creativesFilters);
  }

  // ============================================================================
  // ВОЗВРАЩАЕМЫЙ ОБЪЕКТ
  // ============================================================================
  
  return {
    // Состояние
    filters,
    tabs,
    isInitialized,
    isInitializing,
    
    // Опции
    countryOptions,
    sortOptions, 
    dateRanges,
    multiSelectOptions,
    advertisingNetworksOptions,
    languagesOptions,
    operatingSystemsOptions,
    browsersOptions,
    devicesOptions,
    imageSizesOptions,
    tabOptions,
    currentTabOption,
    
    // Computed свойства
    hasActiveFilters,
    activeFiltersCount,
    
    // Методы управления опциями
    setSelectOptions,
    setTabOptions,
    setTranslations,
    getTranslation,
    
    // Методы управления фильтрами
    updateFilter,
    updateFilters,
    toggleDetailedFilters,
    toggleAdultFilter,
    addToMultiSelect,
    removeFromMultiSelect,
    resetFilters,
    
    // Методы управления вкладками
    setActiveTab,
    setTabCounts,
    resetTabs,
    
    // Методы инициализации
    initializeFilters,
    saveSettings,
    
    // Переопределенные методы
    loadCreatives: loadCreativesFromStore,
    
    // Композаблы (проксируем остальные методы и состояние)
    loadCreativesWithFilters: creativesComposable.loadCreativesWithFilters,
    refreshCreatives: creativesComposable.refreshCreatives,
    loadNextPage: creativesComposable.loadNextPage,
    clearCreatives: creativesComposable.clearCreatives,
    mapFiltersToCreativesFilters: creativesComposable.mapFiltersToCreativesFilters,
    
    // Состояние креативов
    creatives: creativesComposable.creatives,
    pagination: creativesComposable.pagination,
    meta: creativesComposable.meta,
    isLoading: creativesComposable.isLoading,
    error: creativesComposable.error,
    
    // Композаблы для расширенного использования
    urlSync: () => urlSync,
    filterSync: () => filterSync,
  };
});