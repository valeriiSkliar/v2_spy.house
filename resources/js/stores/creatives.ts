import { FilterOption, FilterState, TabOption, TabsState } from '@/types/creatives';
import debounce from 'lodash.debounce';
import { defineStore } from 'pinia';
import { computed, reactive, ref, watch } from 'vue';
import { useCreativesUrlSync } from '../composables/useCreativesUrlSync';

export const useFiltersStore = defineStore('filters', () => {
  // Состояние фильтров с дефолтными значениями
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

  // Состояние вкладок с дефолтными значениями
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

  // Состояние фильтров - единственный источник истины
  const filters = reactive<FilterState>({ ...defaultFilters });
  
  // Состояние вкладок - единственный источник истины
  const tabs = reactive<TabsState>({ ...defaultTabs });

  // URL синхронизация
  let urlSync: ReturnType<typeof useCreativesUrlSync> | null = null;
  let isUrlSyncEnabled = ref(false);

  // Переводы
  const translations = ref<Record<string, string>>({});

  // Опции для селектов - теперь полностью от сервера с fallback
  const defaultCountryOptions: FilterOption[] = [
    { value: 'default', label: 'Fallback value' }
  ];

  const defaultSortOptions: FilterOption[] = [
    { value: 'default', label: 'By creation date' }
  ];

  const defaultDateRanges: FilterOption[] = [
    { value: 'default', label: 'Date of creation' }
  ];

  // Реактивные опции для селектов (заполняются от сервера)
  const countryOptions = ref<FilterOption[]>([...defaultCountryOptions]);
  const sortOptions = ref<FilterOption[]>([...defaultSortOptions]);
  const dateRanges = ref<FilterOption[]>([...defaultDateRanges]);

  // Опции для мультиселектов (от сервера)
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

  // Computed свойства для мультиселектов (готовые для использования в компонентах)
  const advertisingNetworksOptions = computed(() => multiSelectOptions.advertisingNetworks);
  const languagesOptions = computed(() => multiSelectOptions.languages);
  const operatingSystemsOptions = computed(() => multiSelectOptions.operatingSystems);
  const browsersOptions = computed(() => multiSelectOptions.browsers);
  const devicesOptions = computed(() => multiSelectOptions.devices);
  const imageSizesOptions = computed(() => multiSelectOptions.imageSizes);

  // Computed свойства для вкладок
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

  // Методы для фильтров
  
  /**
   * Устанавливает опции для селектов от сервера
   */
  function setSelectOptions(options: any): void {
    console.log('Setting select options:', options);
    
    // Устанавливаем опции стран
    if (options.countries && Array.isArray(options.countries)) {
      countryOptions.value = [...options.countries];
    }
    
    // Устанавливаем опции сортировки от сервера
    if (options.sortOptions && Array.isArray(options.sortOptions)) {
      sortOptions.value = [...options.sortOptions];
    }
    
    // Устанавливаем опции дат от сервера
    if (options.dateRanges && Array.isArray(options.dateRanges)) {
      dateRanges.value = [...options.dateRanges];
    }
    
    // Преобразуем данные мультиселектов в нужный формат
    if (options.advertisingNetworks) {
      if (Array.isArray(options.advertisingNetworks)) {
        // Если уже массив объектов с value/label
        multiSelectOptions.advertisingNetworks = [...options.advertisingNetworks];
      } else {
        // Если Record<string, string>, преобразуем в массив объектов
        multiSelectOptions.advertisingNetworks = Object.entries(options.advertisingNetworks).map(([key, value]) => ({
          value: key,
          label: value as string
        }));
      }
    }
    
    if (options.languages) {
      if (Array.isArray(options.languages)) {
        multiSelectOptions.languages = [...options.languages];
      } else {
        multiSelectOptions.languages = Object.entries(options.languages).map(([key, value]) => ({
          value: key,
          label: value as string
        }));
      }
    }
    
    if (options.operatingSystems) {
      if (Array.isArray(options.operatingSystems)) {
        multiSelectOptions.operatingSystems = [...options.operatingSystems];
      } else {
        multiSelectOptions.operatingSystems = Object.entries(options.operatingSystems).map(([key, value]) => ({
          value: key,
          label: value as string
        }));
      }
    }
    
    if (options.browsers) {
      if (Array.isArray(options.browsers)) {
        multiSelectOptions.browsers = [...options.browsers];
      } else {
        multiSelectOptions.browsers = Object.entries(options.browsers).map(([key, value]) => ({
          value: key,
          label: value as string
        }));
      }
    }
    
    if (options.devices) {
      if (Array.isArray(options.devices)) {
        multiSelectOptions.devices = [...options.devices];
      } else {
        multiSelectOptions.devices = Object.entries(options.devices).map(([key, value]) => ({
          value: key,
          label: value as string
        }));
      }
    }
    
    if (options.imageSizes) {
      if (Array.isArray(options.imageSizes)) {
        multiSelectOptions.imageSizes = [...options.imageSizes];
      } else {
        multiSelectOptions.imageSizes = Object.entries(options.imageSizes).map(([key, value]) => ({
          value: key,
          label: value as string
        }));
      }
    }
  }

  /**
   * Устанавливает опции и счетчики для вкладок
   */
  function setTabOptions(options: any): void {
    console.log('Setting tab options:', options);
    
    if (options.availableTabs && Array.isArray(options.availableTabs)) {
      tabs.availableTabs = [...options.availableTabs];
    }
    
    if (options.tabCounts && typeof options.tabCounts === 'object') {
      tabs.tabCounts = { ...options.tabCounts };
    }
    
    if (options.activeTab && tabs.availableTabs.includes(options.activeTab)) {
      tabs.activeTab = options.activeTab;
    }
  }

  /**
   * Устанавливает переводы
   */
  function setTranslations(translationsData: Record<string, string>): void {
    console.log('Setting translations:', translationsData);
    translations.value = { ...translationsData };
  }

  /**
   * Получает перевод с fallback
   */
  function getTranslation(key: string, fallback: string = key): string {
    return translations.value[key] || fallback;
  }

  /**
   * Инициализирует store с четким приоритетом: URL → Props → Defaults
   * Store является единственным источником истины
   */
  function initializeFilters(propsFilters?: Partial<FilterState>, selectOptions?: any, translationsData?: Record<string, string>, tabsOptions?: any): void {
    console.log('Initializing filters store...');
    
    // 1. Базовое состояние уже установлено (defaultFilters, defaultTabs)
    console.log('Default filters:', defaultFilters);
    console.log('Default tabs:', defaultTabs);
    
    // 2. Устанавливаем опции для селектов
    if (selectOptions) {
      setSelectOptions(selectOptions);
    }
    
    // 3. Устанавливаем опции для вкладок
    if (tabsOptions) {
      setTabOptions(tabsOptions);
    }
    
    // 4. Применяем props (для локализации и серверных настроек)
    if (propsFilters && Object.keys(propsFilters).length > 0) {
      console.log('Applying props to filters:', propsFilters);
      Object.assign(filters, propsFilters);
    }

    // 5. Инициализируем URL синхронизацию (URL имеет наивысший приоритет)
    initUrlSync();

    // 6. Устанавливаем переводы
    if (translationsData) {
      setTranslations(translationsData);
    }
  }

  // URL синхронизация методы
  function initUrlSync(): void {
    if (urlSync) return; // Уже инициализирован

    console.log('Initializing URL sync...');
    urlSync = useCreativesUrlSync();
    setupUrlSyncWatchers();
    
    // Проверяем есть ли URL параметры
    setTimeout(() => {
      isUrlSyncEnabled.value = true;
      
      const hasUrlParams = Object.keys(urlSync!.urlParams).some(key => 
        key.startsWith('cr_') && urlSync!.urlParams[key]
      );
      
      if (hasUrlParams) {
        console.log('Loading state from URL');
        loadFromUrl();
      } else {
        console.log('No URL params, syncing current state to URL');
        // Синхронизируем текущее состояние store с URL
        urlSync!.syncWithFilterState(
          JSON.parse(JSON.stringify(filters)), 
          tabs.activeTab
        );
      }
    }, 100);
  }


  function setupUrlSyncWatchers(): void {
    if (!urlSync) return;

    let isStoreUpdating = false;
    let isUrlUpdating = false;

    // Создаем debounced функции с lodash
    const debouncedStoreToUrl = debounce(() => {
      if (urlSync && isUrlSyncEnabled.value) {
        console.log('Syncing store to URL:', { filters: filters, activeTab: tabs.activeTab });
        
        // Создаем копию состояния для избежания проблем с Proxy
        const filtersCopy = JSON.parse(JSON.stringify(filters));
        urlSync.syncWithFilterState(filtersCopy, tabs.activeTab);
      }
      
      isStoreUpdating = false;
    }, 300);

    const debouncedUrlToStore = debounce((newUrlState: any) => {
      if (urlSync && isUrlSyncEnabled.value) {
        console.log('Syncing URL to store:', newUrlState);
        const updates = urlSync.getFilterStateUpdates();
        updateFromUrl(updates);
      }
      
      isUrlUpdating = false;
    }, 300);

    // Store -> URL синхронизация с debouncing (фильтры и вкладки)
    watch(
      [filters, tabs],
      () => {
        if (urlSync && isUrlSyncEnabled.value && !isUrlUpdating) {
          isStoreUpdating = true;
          debouncedStoreToUrl();
        }
      },
      { deep: true, flush: 'post' }
    );

    // URL -> Store синхронизация с debouncing
    watch(
      () => urlSync?.state.value,
      (newUrlState) => {
        if (newUrlState && isUrlSyncEnabled.value && !isStoreUpdating) {
          isUrlUpdating = true;
          debouncedUrlToStore(newUrlState);
        }
      },
      { deep: true, flush: 'post' }
    );
  }

  function loadFromUrl(): void {
    if (!urlSync) return;

    // Загружаем состояние фильтров
    const filterUpdates = urlSync.getFilterStateUpdates();
    console.log('Loading filters from URL updates:', filterUpdates);
    updateFromUrl(filterUpdates);
    
    // Загружаем активную вкладку
    const activeTabFromUrl = urlSync.getActiveTabFromUrl();
    if (activeTabFromUrl !== tabs.activeTab) {
      console.log('Loading active tab from URL:', activeTabFromUrl);
      tabs.activeTab = activeTabFromUrl;
    }
  }

  function updateFromUrl(updates: Partial<FilterState>): void {
    // Временно отключаем URL синхронизацию чтобы избежать циклов
    isUrlSyncEnabled.value = false;

    Object.entries(updates).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        const filterKey = key as keyof FilterState;
        const currentValue = filters[filterKey];
        
        // Улучшенная проверка изменений для массивов
        let hasChanged = false;
        if (Array.isArray(value) && Array.isArray(currentValue)) {
          // Сравниваем длину и содержимое массивов
          hasChanged = value.length !== currentValue.length || 
                      !value.every((item, index) => item === currentValue[index]);
        } else {
          hasChanged = currentValue !== value;
        }
        
        if (hasChanged) {
          console.log(`Updating from URL: ${key} =`, value);
          
          if (Array.isArray(value)) {
            // Создаем новый массив для реактивности
            const newArray = [...value];
            (filters[filterKey] as any) = newArray;
          } else {
            (filters[filterKey] as any) = value;
          }
        }
      }
    });

    // Включаем обратно URL синхронизацию
    setTimeout(() => {
      isUrlSyncEnabled.value = true;
    }, 100);
  }

  // Методы для фильтров
  function toggleDetailedFilters(): void {
    filters.isDetailedVisible = !filters.isDetailedVisible;
  }

  function setSearchKeyword(keyword: string): void {
    // Избегаем ненужных обновлений если значение не изменилось
    if (filters.searchKeyword !== keyword) {
      console.log('Setting search keyword:', keyword);
      filters.searchKeyword = keyword;
    }
  }

  function setCountry(country: string): void {
    filters.country = country;
  }

  function setSortBy(sortBy: string): void {
    filters.sortBy = sortBy;
  }

  function setDateCreation(date: string): void {
    filters.dateCreation = date;
  }

  function setPeriodDisplay(period: string): void {
    filters.periodDisplay = period;
  }

  function toggleAdultFilter(): void {
    filters.onlyAdult = !filters.onlyAdult;
  }

  function addToMultiSelect(field: keyof FilterState, value: string): void {
    const currentValues = filters[field] as string[];
    if (!currentValues.includes(value)) {
      // Создаем новый массив для обеспечения реактивности
      const newValues = [...currentValues, value];
      (filters[field] as any) = newValues;
      console.log(`Added ${value} to ${field}:`, newValues);
    }
  }

  function removeFromMultiSelect(field: keyof FilterState, value: string): void {
    const currentValues = filters[field] as string[];
    const index = currentValues.indexOf(value);
    if (index > -1) {
      // Создаем новый массив для обеспечения реактивности
      const newValues = currentValues.filter(item => item !== value);
      (filters[field] as any) = newValues;
      console.log(`Removed ${value} from ${field}:`, newValues);
    }
  }

  function resetFilters(): void {
    // Сбрасываем к дефолтным значениям
    Object.assign(filters, defaultFilters);
    console.log('Filters reset to defaults');
  }

  function saveSettings(): void {
    // Логика сохранения настроек
    console.log('Saving filters:', filters);
    // Здесь можно отправить данные на сервер
  }

  // Методы для вкладок
  function setActiveTab(tabValue: string): void {
    if (tabs.availableTabs.includes(tabValue) && tabs.activeTab !== tabValue) {
      console.log('Setting active tab:', tabValue);
      const previousTab = tabs.activeTab;
      tabs.activeTab = tabValue;
      
      // Эмитим событие смены вкладки для других компонентов
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

  function setTabCounts(counts: Record<string, string | number>): void {
    console.log('Setting tab counts:', counts);
    tabs.tabCounts = { ...tabs.tabCounts, ...counts };
  }

  function setAvailableTabs(newTabs: string[]): void {
    console.log('Setting available tabs:', newTabs);
    tabs.availableTabs = [...newTabs];
    
    // Проверяем что текущая вкладка все еще доступна
    if (!tabs.availableTabs.includes(tabs.activeTab)) {
      tabs.activeTab = tabs.availableTabs[0] || 'push';
    }
  }

  function resetTabs(): void {
    // Сбрасываем к дефолтным значениям
    Object.assign(tabs, defaultTabs);
    console.log('Tabs reset to defaults');
  }

  // Computed свойства
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

  const hasCustomTabCounts = computed(() => {
    return Object.keys(tabs.tabCounts).length > 0;
  });

  return {
    // Состояние
    filters,
    tabs,
    
    // Опции для фильтров
    countryOptions,
    sortOptions,
    dateRanges,
    multiSelectOptions,
    
    // Computed опции для мультиселектов
    advertisingNetworksOptions,
    languagesOptions,
    operatingSystemsOptions,
    browsersOptions,
    devicesOptions,
    imageSizesOptions,
    
    // Computed опции для вкладок
    tabOptions,
    currentTabOption,
    
    // Computed состояния
    hasActiveFilters,
    hasCustomTabCounts,
    
    // Основные методы
    initializeFilters,
    setSelectOptions,
    setTabOptions,
    
    // Методы фильтров
    toggleDetailedFilters,
    setSearchKeyword,
    setCountry,
    setSortBy,
    setDateCreation,
    setPeriodDisplay,
    toggleAdultFilter,
    addToMultiSelect,
    removeFromMultiSelect,
    resetFilters,
    saveSettings,

    // Методы вкладок
    setActiveTab,
    setTabCounts,
    setAvailableTabs,
    resetTabs,

    // URL синхронизация
    initUrlSync,
    urlSync: () => urlSync,
    isUrlSyncEnabled,

    // Переводы
    setTranslations,
    getTranslation,
  };
});