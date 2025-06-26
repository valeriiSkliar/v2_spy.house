// stores/useFiltersStore.ts
// Store для креативов на базе новых композаблов

import { useCreatives } from '@/composables/useCreatives';
import { useCreativesUrlSync } from '@/composables/useCreativesUrlSync';
import { useFiltersSynchronization } from '@/composables/useFiltersSynchronization';
import type {
  FilterOption,
  FilterState,
  TabOption,
  TabsState,
  TabValue
} from '@/types/creatives';
import { defineStore } from 'pinia';
import { computed, reactive, ref } from 'vue';

export const useCreativesFiltersStore = defineStore('creativesFilters', () => {
  // ============================================================================
  // СОСТОЯНИЕ
  // ============================================================================
  
  // Дефолтные значения фильтров
  const defaultFilters: FilterState = {
    isDetailedVisible: false,
    searchKeyword: '',
    country: 'default',
    dateCreation: 'default',
    sortBy: 'default',
    periodDisplay: 'default',
    advertisingNetworks: [],
    languages: [],
    operatingSystems: [],
    browsers: [],
    devices: [],
    imageSizes: [],
    onlyAdult: false,
    savedSettings: []
  };

  // Дефолтные значения вкладок
  const defaultTabs: TabsState = {
    activeTab: 'push',
    availableTabs: ['push', 'inpage', 'facebook', 'tiktok'],
    tabCounts: {
      push: '170k',
      inpage: '3.1k',
      facebook: '65.1k',
      tiktok: '45.2m'
    }
  };

  // Реактивное состояние
  const filters = reactive<FilterState>({ ...defaultFilters });
  const tabs = reactive<TabsState>({ ...defaultTabs });
  const isInitialized = ref(false);
  const translations = ref<Record<string, string>>({});

  // Опции для селектов
  const countryOptions = ref<FilterOption[]>([{ value: 'default', label: 'Все страны' }]);
  const sortOptions = ref<FilterOption[]>([{ value: 'default', label: 'По дате создания' }]);
  const dateRanges = ref<FilterOption[]>([{ value: 'default', label: 'Вся история' }]);

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

  // ============================================================================
  // КОМПОЗАБЛЫ
  // ============================================================================
  
  // Инициализируем композаблы
  const creativesComposable = useCreatives();
  const urlSync = useCreativesUrlSync();
  const filtersSync = useFiltersSynchronization(
    filters,
    tabs,
    urlSync,
    creativesComposable
  );

  // ============================================================================
  // COMPUTED СВОЙСТВА
  // ============================================================================
  
  // Опции для мультиселектов (computed)
  const advertisingNetworksOptions = computed(() => multiSelectOptions.advertisingNetworks);
  const languagesOptions = computed(() => multiSelectOptions.languages);
  const operatingSystemsOptions = computed(() => multiSelectOptions.operatingSystems);
  const browsersOptions = computed(() => multiSelectOptions.browsers);
  const devicesOptions = computed(() => multiSelectOptions.devices);
  const imageSizesOptions = computed(() => multiSelectOptions.imageSizes);

  // Опции для вкладок
  const tabOptions = computed((): TabOption[] => {
    return tabs.availableTabs.map(tabValue => ({
      value: tabValue,
      label: getTranslation(`tabs.${tabValue}`, tabValue),
      count: tabs.tabCounts[tabValue] || 0
    }));
  });

  const currentTabOption = computed((): TabOption | undefined => {
    return tabOptions.value.find(tab => tab.value === tabs.activeTab);
  });

  // Есть ли активные фильтры
  const hasActiveFilters = computed(() => {
    return filters.searchKeyword !== '' ||
           filters.country !== 'default' ||
           filters.dateCreation !== 'default' ||
           filters.sortBy !== 'default' ||
           filters.periodDisplay !== 'default' ||
           filters.advertisingNetworks.length > 0 ||
           filters.languages.length > 0 ||
           filters.operatingSystems.length > 0 ||
           filters.browsers.length > 0 ||
           filters.devices.length > 0 ||
           filters.imageSizes.length > 0 ||
           filters.onlyAdult ||
           filters.savedSettings.length > 0;
  });

  // Проксируем computed свойства из композабла креативов
  const creatives = computed(() => creativesComposable.creatives.value);
  const pagination = computed(() => creativesComposable.pagination.value);
  const isLoading = computed(() => creativesComposable.isLoading.value);
  const error = computed(() => creativesComposable.error.value);
  const hasCreatives = computed(() => creatives.value.length > 0);
  const meta = computed(() => creativesComposable.meta.value);

  // ============================================================================
  // МЕТОДЫ ИНИЦИАЛИЗАЦИИ
  // ============================================================================
  
  /**
   * Устанавливает опции для селектов
   */
  function setSelectOptions(options: any): void {
    if (options.countries && Array.isArray(options.countries)) {
      countryOptions.value = [...options.countries];
    }
    
    if (options.sortOptions && Array.isArray(options.sortOptions)) {
      sortOptions.value = [...options.sortOptions];
    }
    
    if (options.dateRanges && Array.isArray(options.dateRanges)) {
      dateRanges.value = [...options.dateRanges];
    }
    
    // Обрабатываем мультиселекты
    const multiSelectFields = [
      'advertisingNetworks', 'languages', 'operatingSystems', 
      'browsers', 'devices', 'imageSizes'
    ];
    
    multiSelectFields.forEach(field => {
      if (options[field]) {
        if (Array.isArray(options[field])) {
          (multiSelectOptions as any)[field] = [...options[field]];
        } else {
          (multiSelectOptions as any)[field] = Object.entries(options[field]).map(([key, value]) => ({
            value: key,
            label: value as string
          }));
        }
      }
    });
  }

  /**
   * Устанавливает опции для вкладок
   */
  function setTabOptions(options: any): void {
    if (options.availableTabs && Array.isArray(options.availableTabs)) {
      tabs.availableTabs = [...options.availableTabs] as TabValue[];
    }
    
    if (options.tabCounts && typeof options.tabCounts === 'object') {
      tabs.tabCounts = { ...options.tabCounts };
    }
    
    if (options.activeTab && tabs.availableTabs.includes(options.activeTab as TabValue)) {
      tabs.activeTab = options.activeTab as TabValue;
    }
  }

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

  /**
   * Основная инициализация store
   */
  async function initializeFilters(
    propsFilters?: Partial<FilterState>,
    selectOptions?: any,
    translationsData?: Record<string, string>,
    tabsOptions?: any
  ): Promise<void> {
    console.log('🚀 Инициализация CreativesFiltersStore с новыми композаблами');
    
    // 1. Устанавливаем опции
    if (selectOptions) {
      setSelectOptions(selectOptions);
    }
    
    if (tabsOptions) {
      setTabOptions(tabsOptions);
    }
    
    if (translationsData) {
      setTranslations(translationsData);
    }
    
    // 2. Применяем props
    if (propsFilters && Object.keys(propsFilters).length > 0) {
      Object.assign(filters, propsFilters);
    }
    
    // 3. Инициализируем синхронизацию фильтров
    await filtersSync.initialize();
    
    isInitialized.value = true;
    console.log('✅ CreativesFiltersStore инициализирован');
  }

  // ============================================================================
  // МЕТОДЫ УПРАВЛЕНИЯ ФИЛЬТРАМИ
  // ============================================================================
  
  /**
   * Универсальное обновление фильтра
   */
  function updateFilter<K extends keyof FilterState>(key: K, value: FilterState[K]): void {
    if (filters[key] !== value) {
      filters[key] = value;
    }
  }

  /**
   * Переключение детальных фильтров
   */
  function toggleDetailedFilters(): void {
    filters.isDetailedVisible = !filters.isDetailedVisible;
  }

  /**
   * Переключение adult фильтра
   */
  function toggleAdultFilter(): void {
    filters.onlyAdult = !filters.onlyAdult;
  }

  /**
   * Добавление в мультиселект
   */
  function addToMultiSelect(field: keyof FilterState, value: string): void {
    const currentValues = filters[field] as string[];
    if (!currentValues.includes(value)) {
      const newValues = [...currentValues, value];
      (filters[field] as any) = newValues;
    }
  }

  /**
   * Удаление из мультиселекта
   */
  function removeFromMultiSelect(field: keyof FilterState, value: string): void {
    const currentValues = filters[field] as string[];
    const index = currentValues.indexOf(value);
    if (index > -1) {
      const newValues = currentValues.filter(item => item !== value);
      (filters[field] as any) = newValues;
    }
  }

  /**
   * Сброс фильтров
   */
  function resetFilters(): void {
    Object.assign(filters, defaultFilters);
  }

  /**
   * Сохранение настроек
   */
  function saveSettings(): void {
    // TODO: Реализовать сохранение на сервер
    console.log('Сохранение настроек фильтров');
  }

  // ============================================================================
  // МЕТОДЫ УПРАВЛЕНИЯ ВКЛАДКАМИ
  // ============================================================================
  
  /**
   * Установка активной вкладки
   */
  function setActiveTab(tabValue: TabValue): void {
    if (tabs.availableTabs.includes(tabValue) && tabs.activeTab !== tabValue) {
      const previousTab = tabs.activeTab;
      tabs.activeTab = tabValue;
      
      // Эмитим событие
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

  // ============================================================================
  // МЕТОДЫ КРЕАТИВОВ (ПРОКСИРОВАНИЕ)
  // ============================================================================
  
  /**
   * Загрузка креативов
   */
  async function loadCreatives(page: number = 1): Promise<void> {
    const creativesFilters = creativesComposable.mapFiltersToCreativesFilters(
      filters,
      tabs.activeTab,
      page
    );
    
    // Синхронизируем page с URL при загрузке страницы
    urlSync.syncFiltersToUrl(filters, tabs.activeTab, page);
    
    await creativesComposable.loadCreativesWithFilters(creativesFilters);
  }

  /**
   * Загрузка следующей страницы
   */
  async function loadNextPage(): Promise<void> {
    await creativesComposable.loadNextPage();
  }

  /**
   * Обновление креативов
   */
  async function refreshCreatives(): Promise<void> {
    await creativesComposable.refreshCreatives();
  }

  // ============================================================================
  // ВОЗВРАТ ОБЪЕКТА STORE
  // ============================================================================
  
  return {
    // Состояние
    filters,
    tabs,
    isInitialized,
    
    // Опции
    countryOptions,
    sortOptions,
    dateRanges,
    advertisingNetworksOptions,
    languagesOptions,
    operatingSystemsOptions,
    browsersOptions,
    devicesOptions,
    imageSizesOptions,
    tabOptions,
    currentTabOption,
    
    // Computed из креативов
    creatives,
    pagination,
    isLoading,
    error,
    hasCreatives,
    meta,
    hasActiveFilters,
    
    // Методы инициализации
    initializeFilters,
    setSelectOptions,
    setTabOptions,
    setTranslations,
    getTranslation,
    
    // Методы фильтров
    updateFilter,
    toggleDetailedFilters,
    toggleAdultFilter,
    addToMultiSelect,
    removeFromMultiSelect,
    resetFilters,
    saveSettings,
    
    // Методы вкладок
    setActiveTab,
    
    // Методы креативов
    loadCreatives,
    loadNextPage,
    refreshCreatives,
    
    // Композаблы (для прямого доступа если нужно)
    creativesComposable,
    urlSync,
    filtersSync,
  };
});