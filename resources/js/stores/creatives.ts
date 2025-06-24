import { FilterOption, FilterState } from '@/types/creatives';
import { defineStore } from 'pinia';
import { computed, reactive, ref, watch } from 'vue';
import { useCreativesUrlSync } from '../composables/useCreativesUrlSync';

export const useFiltersStore = defineStore('filters', () => {
  // Состояние фильтров
  const filters = reactive<FilterState>({
    isDetailedVisible: false,
    searchKeyword: '',
    country: 'All Categories',
    dateCreation: 'Date of creation',
    sortBy: 'By creation date',
    periodDisplay: 'Period of display',
    advertisingNetworks: [],
    languages: [],
    operatingSystems: [],
    browsers: [],
    devices: [],
    imageSizes: [],
    onlyAdult: false,
    savedSettings: []
  });

  // URL синхронизация (инициализируется по требованию)
  let urlSync: ReturnType<typeof useCreativesUrlSync> | null = null;
  let isUrlSyncEnabled = ref(false);

  // Опции для селектов
  const countryOptions = ref<FilterOption[]>([
    { value: 'all', label: 'All Categories' },
    { value: 'advertising', label: 'Advertising Networks' },
    { value: 'affiliate', label: 'Affiliate Programs' },
    { value: 'trackers', label: 'Trackers' },
    { value: 'hosting', label: 'Hosting' },
    { value: 'domain', label: 'Domain Registrars' },
    { value: 'spy', label: 'SPY Services' },
    { value: 'proxy', label: 'Proxy and VPN Services' },
    { value: 'browsers', label: 'Anti-detection Browsers' },
    { value: 'accounts', label: 'Account Purchase and Rental' },
    { value: 'apps', label: 'Purchase and Rental of Applications' },
    { value: 'notifications', label: 'Notification and Newsletter Services' },
    { value: 'payments', label: 'Payment Services' },
    { value: 'other', label: 'Other Services and Utilities' }
  ]);

  const sortOptions = ref<FilterOption[]>([
    { value: 'creation', label: 'By creation date' },
    { value: 'activity', label: 'By days of activity' }
  ]);

  const dateRanges = ref<FilterOption[]>([
    { value: 'today', label: 'Today' },
    { value: 'yesterday', label: 'Yesterday' },
    { value: 'last7', label: 'Last 7 days' },
    { value: 'last30', label: 'Last 30 days' },
    { value: 'thisMonth', label: 'This month' },
    { value: 'lastMonth', label: 'Last month' },
    { value: 'custom', label: 'Custom Range' }
  ]);

  // Методы
  function initializeFromProps(initialFilters: Partial<FilterState>): void {
    console.log('initializeFromProps called with:', initialFilters);
    
    Object.entries(initialFilters).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        const filterKey = key as keyof FilterState;
        console.log(`Setting ${key} to:`, value, typeof value);
        
        if (Array.isArray(value)) {
          (filters[filterKey] as any) = [...value];
        } else if (typeof value === 'string' && value !== 'undefined') {
          // Специальная обработка булевых значений переданных как строки
          if (filterKey === 'onlyAdult' && (value === 'true' || value === 'false')) {
            (filters[filterKey] as any) = value === 'true';
          } else {
            (filters[filterKey] as any) = value;
          }
        } else if (typeof value === 'boolean') {
          (filters[filterKey] as any) = value;
        } else {
          (filters[filterKey] as any) = value;
        }
      }
    });
    
    console.log('Filters after initialization:', { ...filters });
  }

  // URL синхронизация методы
  function initUrlSync(): void {
    if (urlSync) return; // Уже инициализирован

    console.log('Initializing URL sync with current filters:', { ...filters });

    // Инициализируем URL синхронизацию с текущим состоянием
    urlSync = useCreativesUrlSync({
      searchKeyword: filters.searchKeyword || '',
      country: filters.country !== 'All Categories' ? filters.country : '',
      dateCreation: filters.dateCreation !== 'Date of creation' ? filters.dateCreation : '',
      sortBy: filters.sortBy !== 'By creation date' ? filters.sortBy : '',
      periodDisplay: filters.periodDisplay !== 'Period of display' ? filters.periodDisplay : '',
      advertisingNetworks: filters.advertisingNetworks.length > 0 ? [...filters.advertisingNetworks] : [],
      languages: filters.languages.length > 0 ? [...filters.languages] : [],
      operatingSystems: filters.operatingSystems.length > 0 ? [...filters.operatingSystems] : [],
      browsers: filters.browsers.length > 0 ? [...filters.browsers] : [],
      devices: filters.devices.length > 0 ? [...filters.devices] : [],
      imageSizes: filters.imageSizes.length > 0 ? [...filters.imageSizes] : [],
      onlyAdult: filters.onlyAdult || false,
      savedSettings: filters.savedSettings.length > 0 ? [...filters.savedSettings] : [],
      isDetailedVisible: filters.isDetailedVisible || false,
    });

    // Настраиваем двустороннюю синхронизацию с задержкой
    setTimeout(() => {
      setupUrlSyncWatchers();
      isUrlSyncEnabled.value = true;
      
      // Загружаем состояние из URL только если в URL есть параметры
      const hasUrlParams = Object.keys(urlSync!.urlParams).some(key => 
        key.startsWith('cr_') && urlSync!.urlParams[key]
      );
      
      if (hasUrlParams) {
        console.log('Loading filters from URL...');
        loadFromUrl();
      } else {
        console.log('No URL parameters found, keeping current filters');
      }
    }, 100);
  }

  function setupUrlSyncWatchers(): void {
    if (!urlSync) return;

    let isStoreUpdating = false;
    let isUrlUpdating = false;
    let storeToUrlTimeout: ReturnType<typeof setTimeout> | null = null;
    let urlToStoreTimeout: ReturnType<typeof setTimeout> | null = null;

    // Store -> URL синхронизация с дебаунсингом
    watch(
      filters,
      () => {
        if (urlSync && isUrlSyncEnabled.value && !isUrlUpdating) {
          // Очищаем предыдущий таймер
          if (storeToUrlTimeout) {
            clearTimeout(storeToUrlTimeout);
          }
          
          isStoreUpdating = true;
          
          // Дебаунсинг для предотвращения частых обновлений
          storeToUrlTimeout = setTimeout(() => {
            if (urlSync && isUrlSyncEnabled.value) {
              console.log('Syncing store to URL:', { ...filters });
              
              // Создаем копию состояния для избежания проблем с Proxy
              const filtersCopy = JSON.parse(JSON.stringify(filters));
              urlSync.syncWithFilterState(filtersCopy);
            }
            
            isStoreUpdating = false;
            storeToUrlTimeout = null;
          }, 150);
        }
      },
      { deep: true, flush: 'post' }
    );

    // URL -> Store синхронизация с дебаунсингом
    watch(
      () => urlSync?.state.value,
      (newUrlState) => {
        if (newUrlState && isUrlSyncEnabled.value && !isStoreUpdating) {
          // Очищаем предыдущий таймер
          if (urlToStoreTimeout) {
            clearTimeout(urlToStoreTimeout);
          }
          
          isUrlUpdating = true;
          
          // Дебаунсинг для предотвращения частых обновлений
          urlToStoreTimeout = setTimeout(() => {
            if (urlSync && isUrlSyncEnabled.value) {
              console.log('Syncing URL to store:', newUrlState);
              const updates = urlSync.getFilterStateUpdates();
              updateFromUrl(updates);
            }
            
            isUrlUpdating = false;
            urlToStoreTimeout = null;
          }, 150);
        }
      },
      { deep: true, flush: 'post' }
    );
  }

  function loadFromUrl(): void {
    if (!urlSync) return;

    const updates = urlSync.getFilterStateUpdates();
    console.log('Loading from URL updates:', updates);
    updateFromUrl(updates);
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

  function toggleDetailedFilters(): void {
    filters.isDetailedVisible = !filters.isDetailedVisible;
  }

  function setSearchKeyword(keyword: string): void {
    filters.searchKeyword = keyword;
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
    filters.searchKeyword = '';
    filters.country = 'All Categories';
    filters.dateCreation = 'Date of creation';
    filters.sortBy = 'By creation date';
    filters.periodDisplay = 'Period of display';
    filters.advertisingNetworks = [];
    filters.languages = [];
    filters.operatingSystems = [];
    filters.browsers = [];
    filters.devices = [];
    filters.imageSizes = [];
    filters.onlyAdult = false;
    filters.savedSettings = [];
  }

  function saveSettings(): void {
    // Логика сохранения настроек
    console.log('Saving filters:', filters);
    // Здесь можно отправить данные на сервер
  }

  // Computed свойства
  const hasActiveFilters = computed(() => {
    return filters.searchKeyword !== '' ||
           filters.country !== 'All Categories' ||
           filters.advertisingNetworks.length > 0 ||
           filters.languages.length > 0 ||
           filters.operatingSystems.length > 0 ||
           filters.browsers.length > 0 ||
           filters.devices.length > 0 ||
           filters.imageSizes.length > 0 ||
           filters.onlyAdult ||
           filters.savedSettings.length > 0;
  });

  return {
    filters,
    countryOptions,
    sortOptions,
    dateRanges,
    hasActiveFilters,
    
    // Основные методы
    initializeFromProps,
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

    // URL синхронизация
    initUrlSync,
    urlSync: () => urlSync,
    isUrlSyncEnabled,
  };
});