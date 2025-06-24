import { FilterOption, FilterState } from '@/types/creatives';
import debounce from 'lodash.debounce';
import { defineStore } from 'pinia';
import { computed, reactive, ref, watch } from 'vue';
import { useCreativesUrlSync } from '../composables/useCreativesUrlSync';

export const useFiltersStore = defineStore('filters', () => {
  // Состояние фильтров с дефолтными значениями
  const defaultFilters: FilterState = {
    isDetailedVisible: false,
    searchKeyword: '',
    country: 'All Countries',
    dateCreation: 'Date of creation',
    sortBy: 'By creation date',
    periodDisplay: 'Date of creation',
    advertisingNetworks: [],
    languages: [],
    operatingSystems: [],
    browsers: [],
    devices: [],
    imageSizes: [],
    onlyAdult: false,
    savedSettings: []
  };

  // Состояние фильтров - единственный источник истины
  const filters = reactive<FilterState>({ ...defaultFilters });

  // URL синхронизация
  let urlSync: ReturnType<typeof useCreativesUrlSync> | null = null;
  let isUrlSyncEnabled = ref(false);

  // Опции для селектов - теперь полностью от сервера с fallback
  const defaultCountryOptions: FilterOption[] = [
    { value: 'All Countries', label: 'All Countries' }
  ];

  const defaultSortOptions: FilterOption[] = [
    { value: 'By creation date', label: 'By creation date' }
  ];

  const defaultDateRanges: FilterOption[] = [
    { value: 'Date of creation', label: 'Date of creation' }
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

  // Методы
  
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
   * Инициализирует store с четким приоритетом: URL → Props → Defaults
   * Store является единственным источником истины
   */
  function initializeFilters(propsFilters?: Partial<FilterState>, selectOptions?: any): void {
    console.log('Initializing filters store...');
    
    // 1. Базовое состояние уже установлено (defaultFilters)
    console.log('Default filters:', defaultFilters);
    
    // 2. Устанавливаем опции для селектов
    if (selectOptions) {
      setSelectOptions(selectOptions);
    }
    
    // 3. Применяем props (для локализации и серверных настроек)
    if (propsFilters && Object.keys(propsFilters).length > 0) {
      console.log('Applying props to filters:', propsFilters);
      Object.assign(filters, propsFilters);
    }

    // 4. Инициализируем URL синхронизацию (URL имеет наивысший приоритет)
    initUrlSync();
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
        console.log('Loading filters from URL');
        loadFromUrl();
      } else {
        console.log('No URL params, syncing current state to URL');
        // Синхронизируем текущее состояние store с URL
        urlSync!.syncWithFilterState(JSON.parse(JSON.stringify(filters)));
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
        console.log('Syncing store to URL:', { ...filters });
        
        // Создаем копию состояния для избежания проблем с Proxy
        const filtersCopy = JSON.parse(JSON.stringify(filters));
        urlSync.syncWithFilterState(filtersCopy);
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

    // Store -> URL синхронизация с debouncing
    watch(
      filters,
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

  // Computed свойства
  const hasActiveFilters = computed(() => {
    return filters.searchKeyword !== '' ||
           filters.country !== 'All Countries' ||
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
    multiSelectOptions,
    
    // Computed опции для мультиселектов
    advertisingNetworksOptions,
    languagesOptions,
    operatingSystemsOptions,
    browsersOptions,
    devicesOptions,
    imageSizesOptions,
    
    hasActiveFilters,
    
    // Основные методы
    initializeFilters,
    setSelectOptions,
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