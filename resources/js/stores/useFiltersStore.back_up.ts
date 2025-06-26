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
  
  // Опции для селектов с дефолтными значениями
  const countryOptions = shallowRef<FilterOption[]>([
    { value: 'default', label: 'Loading countries...' }
  ]);
  const sortOptions = shallowRef<FilterOption[]>([
    { value: 'creation', label: 'Date of creation' },
    { value: 'popularity', label: 'Popularity' }
  ]);
  const dateRanges = shallowRef<FilterOption[]>([
    { value: 'default', label: 'Loading date ranges...' }
  ]);
  
  // Опции для мультиселектов с дефолтными значениями
  const multiSelectOptions = reactive<{
    advertisingNetworks: FilterOption[];
    languages: FilterOption[];
    operatingSystems: FilterOption[];
    browsers: FilterOption[];
    devices: FilterOption[];
    imageSizes: FilterOption[];
  }>({
    advertisingNetworks: [{ value: 'loading', label: 'Loading networks...' }],
    languages: [{ value: 'loading', label: 'Loading languages...' }],
    operatingSystems: [{ value: 'loading', label: 'Loading OS...' }],
    browsers: [{ value: 'loading', label: 'Loading browsers...' }],
    devices: [{ value: 'loading', label: 'Loading devices...' }],
    imageSizes: [{ value: 'loading', label: 'Loading sizes...' }],
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
    console.log('🔧 setSelectOptions called with:', {
      hasCountries: !!(options.countries && options.countries.length),
      hasSortOptions: !!(options.sortOptions && options.sortOptions.length),
      hasDateRanges: !!(options.dateRanges && options.dateRanges.length),
      hasAdvertisingNetworks: !!(options.advertisingNetworks && options.advertisingNetworks.length),
      hasLanguages: !!(options.languages && options.languages.length),
      countriesCount: options.countries ? options.countries.length : 0,
      sortOptionsCount: options.sortOptions ? options.sortOptions.length : 0,
      advertisingNetworksCount: options.advertisingNetworks ? (Array.isArray(options.advertisingNetworks) ? options.advertisingNetworks.length : Object.keys(options.advertisingNetworks).length) : 0,
    });

    if (options.countries) {
      countryOptions.value = options.countries;
      console.log('✅ Countries set:', countryOptions.value.length);
    }
    if (options.sortOptions) {
      sortOptions.value = options.sortOptions;
      console.log('✅ Sort options set:', sortOptions.value.length);
    }
    if (options.dateRanges) {
      dateRanges.value = options.dateRanges;
      console.log('✅ Date ranges set:', dateRanges.value.length);
    }
    
    // Мультиселекты
    if (options.advertisingNetworks) {
      multiSelectOptions.advertisingNetworks = normalizeOptions(options.advertisingNetworks);
      console.log('✅ Advertising networks set:', multiSelectOptions.advertisingNetworks.length);
    }
    if (options.languages) {
      multiSelectOptions.languages = normalizeOptions(options.languages);
      console.log('✅ Languages set:', multiSelectOptions.languages.length);
    }
    if (options.operatingSystems) {
      multiSelectOptions.operatingSystems = normalizeOptions(options.operatingSystems);
      console.log('✅ Operating systems set:', multiSelectOptions.operatingSystems.length);
    }
    if (options.browsers) {
      multiSelectOptions.browsers = normalizeOptions(options.browsers);
      console.log('✅ Browsers set:', multiSelectOptions.browsers.length);
    }
    if (options.devices) {
      multiSelectOptions.devices = normalizeOptions(options.devices);
      console.log('✅ Devices set:', multiSelectOptions.devices.length);
    }
    if (options.imageSizes) {
      multiSelectOptions.imageSizes = normalizeOptions(options.imageSizes);
      console.log('✅ Image sizes set:', multiSelectOptions.imageSizes.length);
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
    console.log('🚀 initializeFilters called with:', {
      hasPropsFilters: !!propsFilters,
      hasSelectOptions: !!selectOptions,
      hasTranslations: !!translationsData,
      hasTabOptions: !!tabsOptions,
      isInitialized: isInitialized.value,
      isInitializing: isInitializing.value,
      selectOptionsKeys: selectOptions ? Object.keys(selectOptions) : [],
    });

    if (isInitialized.value) {
      console.log('⚠️ Store already initialized, but checking for additional options...');
      
      // Если store уже инициализирован, но у нас есть новые selectOptions - обновляем их
      if (selectOptions && Object.keys(selectOptions).length > 0) {
        console.log('🔄 Updating selectOptions for already initialized store...');
        setSelectOptions(selectOptions);
      }
      
      // Аналогично для переводов
      if (translationsData && Object.keys(translationsData).length > 0) {
        console.log('🔄 Updating translations for already initialized store...');
        setTranslations(translationsData);
      }
      
      // Если пришли новые tabOptions - тоже обновляем
      if (tabsOptions && Object.keys(tabsOptions).length > 0) {
        console.log('🔄 Updating tabOptions for already initialized store...');
        setTabOptions(tabsOptions);
      }
      
      // Если пришли новые propsFilters - обновляем их тоже
      if (propsFilters && Object.keys(propsFilters).length > 0) {
        console.log('🔄 Updating filters for already initialized store...');
        updateFilters(propsFilters);
      }
      
      return;
    }
    
    if (isInitializing.value) {
      console.log('⏳ Store is initializing, waiting...');
      // Wait for initialization to complete
      await new Promise<void>((resolve) => {
        const unwatch = () => {
          if (isInitialized.value || !isInitializing.value) {
            resolve();
            return true;
          }
          return false;
        };
        
        if (unwatch()) return;
        
        const interval = setInterval(() => {
          if (unwatch()) {
            clearInterval(interval);
          }
        }, 10);
      });
      return;
    }
    
    isInitializing.value = true;
    
    try {
      // 1. Устанавливаем опции и переводы
      console.log('📝 Step 1: Setting options and translations');
      if (selectOptions) {
        console.log('🔧 Calling setSelectOptions...');
        setSelectOptions(selectOptions);
      } else {
        console.log('⚠️ No selectOptions provided');
      }
      
      if (tabsOptions) {
        console.log('📋 Setting tab options...');
        setTabOptions(tabsOptions);
      }
      
      if (translationsData) {
        console.log('🌐 Setting translations...');
        setTranslations(translationsData);
      }
      
      // 2. Применяем props фильтры
      console.log('📝 Step 2: Applying props filters');
      if (propsFilters) {
        console.log('🔄 Updating filters with props...');
        updateFilters(propsFilters);
      }
      
      // 3. Инициализируем синхронизацию
      console.log('📝 Step 3: Initializing synchronization');
      await filterSync.initialize();
      
      // 4. Загружаем креативы если есть параметры в URL или активные фильтры
      console.log('📝 Step 4: Checking if we need to load creatives');
      if (urlSync.hasUrlParams() || hasActiveFilters.value) {
        console.log('🚀 Loading creatives...');
        await loadCreativesFromStore();
      } else {
        console.log('⏭️ No URL params or active filters, skipping creatives load');
      }
      
      isInitialized.value = true;
      console.log('✅ Store initialization completed successfully');
      
    } catch (error) {
      console.error('❌ Error during store initialization:', error);
      throw error;
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