// DEPRECATED: 
import type { Creative, CreativesFilters, ProcessedCreativesData } from '@/types/creatives';
import { FilterOption, FilterState, TabOption, TabsState, TabValue } from '@/types/creatives';
import debounce from 'lodash.debounce';
import { defineStore } from 'pinia';
import { computed, reactive, ref, watch } from 'vue';
import { useCreativesUrlSync } from '../composables/useCreativesUrlSync';
import { creativesService } from '../services/CreativesService';

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
    savedSettings: [],
    perPage: 12
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

  // === СОСТОЯНИЕ КРЕАТИВОВ ===
  // Данные креативов
  const creativesData = ref<ProcessedCreativesData | null>(null);
  const creativesLoading = ref(false);
  const creativesError = ref<string | null>(null);
  
  // Кэш последнего запроса для предотвращения дубликатов
  const lastRequestKey = ref<string>('');

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

  // === COMPUTED СВОЙСТВА ДЛЯ КРЕАТИВОВ ===
  
  // Список креативов
  const creatives = computed((): Creative[] => {
    return creativesData.value?.items || [];
  });

  // Информация о пагинации
  const pagination = computed(() => {
    return creativesData.value?.pagination || {
      total: 0,
      perPage: 12,
      currentPage: 1,
      lastPage: 1,
      from: 0,
      to: 0
    };
  });

  // Метаданные последнего запроса
  const requestMeta = computed(() => {
    return creativesData.value?.meta || {
      hasSearch: false,
      activeFiltersCount: 0,
      cacheKey: ''
    };
  });

  // Есть ли данные
  const hasCreatives = computed((): boolean => {
    return creatives.value.length > 0;
  });

  // Есть ли поиск
  const hasSearch = computed((): boolean => {
    return requestMeta.value.hasSearch;
  });

  // Количество активных фильтров (из CreativesService)
  const activeFiltersCount = computed((): number => {
    return requestMeta.value.activeFiltersCount;
  });

  // Состояние загрузки (комбинированное)
  const isLoading = computed((): boolean => {
    return creativesLoading.value || creativesService.isLoading();
  });

  // Есть ли ошибка
  const hasError = computed((): boolean => {
    return creativesError.value !== null;
  });

  // Методы для фильтров
  
  /**
   * Устанавливает опции для селектов от сервера
   */
  function setSelectOptions(options: any): void {
    
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
    console.log('🏷️ setTabOptions вызван с опциями:', options);
    
    if (options.availableTabs && Array.isArray(options.availableTabs)) {
      tabs.availableTabs = [...options.availableTabs];
    }
    
    if (options.tabCounts && typeof options.tabCounts === 'object') {
      tabs.tabCounts = { ...options.tabCounts };
    }
    
    // Устанавливаем activeTab если он указан и входит в доступные вкладки
    if (options.activeTab && tabs.availableTabs.includes(options.activeTab)) {
      console.log(`🔀 Устанавливаем activeTab из options: ${options.activeTab}`);
      tabs.activeTab = options.activeTab;
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
   * Инициализирует store с четким приоритетом: URL → Props → Defaults
   * Store является единственным источником истины
   */
  function initializeFilters(propsFilters?: Partial<FilterState>, selectOptions?: any, translationsData?: Record<string, string>, tabsOptions?: any): void {
    
    // 1. Базовое состояние уже установлено (defaultFilters, defaultTabs)
    
    // 2. Устанавливаем опции для селектов
    if (selectOptions) {
      setSelectOptions(selectOptions);
    }
    
    // 3. Устанавливаем опции для вкладок (включая activeTab из props)
    if (tabsOptions) {
      setTabOptions(tabsOptions);
    }
    
    // 4. Применяем props (для локализации и серверных настроек)
    if (propsFilters && Object.keys(propsFilters).length > 0) {
      Object.assign(filters, propsFilters);
    }

    // 5. Устанавливаем переводы
    if (translationsData) {
      setTranslations(translationsData);
    }

    // 6. Инициализируем URL синхронизацию (URL имеет наивысший приоритет)
    // Но НЕ загружаем из URL сразу - дадим время компонентам инициализироваться
    initUrlSync();

    // 7. Настраиваем watchers для автоматической перезагрузки
    setupAutoReloadWatchers();

    // 8. Отложенная загрузка из URL и креативов
    setTimeout(() => {
      console.log('🔗 Проверяем URL параметры и загружаем соответствующее состояние');
      
      // Сначала загружаем состояние из URL (если есть)
      const hasUrlParams = Object.keys(urlSync?.urlParams || {}).some(key => 
        key.startsWith('cr_') && urlSync?.urlParams[key]
      );
      
      if (hasUrlParams) {
        console.log('📄 Найдены URL параметры, загружаем состояние из URL');
        loadFromUrl();
      } else {
        console.log('🔗 URL параметры не найдены, синхронизируем текущее состояние с URL');
        // Синхронизируем текущее состояние store с URL
        if (urlSync) {
          urlSync.syncFiltersToUrl(
            JSON.parse(JSON.stringify(filters)), 
            tabs.activeTab
          );
        }
      }
      
      // Затем загружаем креативы с финальным состоянием
      console.log('🚀 Автоматическая загрузка креативов после инициализации Store');
      loadCreatives();
    }, 150);
  }

  // Автоматическая перезагрузка креативов при изменении фильтров
  function setupAutoReloadWatchers(): void {
    console.log('📡 Настройка watchers для автоматической перезагрузки креативов');

    // Создаем debounced функцию для перезагрузки
    const debouncedReload = debounce(() => {
      console.log('🔄 Перезагрузка креативов из-за изменения фильтров');
      refreshCreatives();
    }, 500);

    // Следим за изменениями фильтров (исключая isDetailedVisible)
    watch(
      () => ({
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
        onlyAdult: filters.onlyAdult
      }),
      (newFilters, oldFilters) => {
        // Проверяем что это не первичная инициализация
        if (oldFilters) {
          console.log('📝 Изменились фильтры:', newFilters);
          debouncedReload();
        }
      },
      { deep: true }
    );

    // Следим за изменениями активной вкладки
    watch(
      () => tabs.activeTab,
      (newTab, oldTab) => {
        // Проверяем что это не первичная инициализация и синхронизация включена
        if (oldTab && newTab !== oldTab && isUrlSyncEnabled.value) {
          console.log('🔀 Изменилась вкладка:', { from: oldTab, to: newTab });
          refreshCreatives();
        }
      }
    );
  }

  // URL синхронизация методы
  function initUrlSync(): void {
    if (urlSync) return; // Уже инициализирован

    urlSync = useCreativesUrlSync();
    setupUrlSyncWatchers();
    
    // Включаем синхронизацию но НЕ загружаем сразу
    // Загрузку будет контролировать initializeFilters
    setTimeout(() => {
      isUrlSyncEnabled.value = true;
    }, 50);
  }


  function setupUrlSyncWatchers(): void {
    if (!urlSync) return;

    let isStoreUpdating = false;
    let isUrlUpdating = false;

    // Создаем debounced функции с lodash
    const debouncedStoreToUrl = debounce(() => {
      if (urlSync && isUrlSyncEnabled.value) {
        
        // Создаем копию состояния для избежания проблем с Proxy
        const filtersCopy = JSON.parse(JSON.stringify(filters));
        urlSync.syncFiltersToUrl(filtersCopy, tabs.activeTab);
      }
      
      isStoreUpdating = false;
    }, 300);

    const debouncedUrlToStore = debounce((newUrlState: any) => {
      console.log('🔄 debouncedUrlToStore вызван с новым состоянием:', newUrlState);
      if (urlSync && isUrlSyncEnabled.value) {
        const updates = urlSync.getFilterUpdates();
        updateFromUrl(updates);
      }
      
      isUrlUpdating = false;
    }, 300);

    // Store -> URL синхронизация с debouncing (фильтры и вкладки)
    watch(
      [filters, tabs],
      (_newValue, oldValue) => {
        // Проверяем что это не первичная инициализация и синхронизация включена
        if (urlSync && isUrlSyncEnabled.value && !isUrlUpdating && oldValue) {
          console.log('📡 Store -> URL: изменения в Store, обновляем URL');
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

    console.log('📄 loadFromUrl: загружаем состояние из URL');
    
    // Временно отключаем watchers для предотвращения лишних срабатываний
    isUrlSyncEnabled.value = false;

    // Загружаем состояние фильтров
    const filterUpdates = urlSync.getFilterUpdates();
    console.log('🔧 Обновления фильтров из URL:', filterUpdates);
    
    // Обновляем фильтры без triggering watchers
    Object.entries(filterUpdates).forEach(([key, value]) => {
      if (value !== undefined && value !== null) {
        const filterKey = key as keyof FilterState;
        
        if (Array.isArray(value)) {
          (filters[filterKey] as any) = [...value];
        } else {
          (filters[filterKey] as any) = value;
        }
      }
    });
    
    // Загружаем активную вкладку
    const activeTabFromUrl = urlSync.getActiveTabFromUrl();
    console.log('🏷️ Активная вкладка из URL:', activeTabFromUrl, 'текущая:', tabs.activeTab);
    
    if (activeTabFromUrl !== tabs.activeTab) {
      console.log(`🔀 Устанавливаем вкладку из URL: ${activeTabFromUrl}`);
      tabs.activeTab = activeTabFromUrl;
    }

    // Включаем обратно синхронизацию
    setTimeout(() => {
      isUrlSyncEnabled.value = true;
      console.log('✅ loadFromUrl завершен, URL синхронизация включена');
    }, 50);
  }

  function updateFromUrl(updates: Partial<FilterState>): void {
    console.log('📥 updateFromUrl вызван с обновлениями:', updates);
    
    // Временно отключаем URL синхронизацию и watchers чтобы избежать циклов
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
          console.log(`🔄 Обновляем ${key}:`, { from: currentValue, to: value });
          
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
      console.log('✅ URL синхронизация включена обратно');
    }, 100);
  }

  // Методы для фильтров
  function toggleDetailedFilters(): void {
    filters.isDetailedVisible = !filters.isDetailedVisible;
  }

  function setSearchKeyword(keyword: string): void {
    // Избегаем ненужных обновлений если значение не изменилось
    if (filters.searchKeyword !== keyword) {
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
    }
  }

  function removeFromMultiSelect(field: keyof FilterState, value: string): void {
    const currentValues = filters[field] as string[];
    const index = currentValues.indexOf(value);
    if (index > -1) {
      // Создаем новый массив для обеспечения реактивности
      const newValues = currentValues.filter(item => item !== value);
      (filters[field] as any) = newValues;
    }
  }

  function resetFilters(): void {
    // Сбрасываем к дефолтным значениям
    Object.assign(filters, defaultFilters);
  }

  function saveSettings(): void {
    // Логика сохранения настроек
    // Здесь можно отправить данные на сервер
  }

  // Методы для вкладок
  function setActiveTab(tabValue: TabValue): void {
    if (tabs.availableTabs.includes(tabValue) && tabs.activeTab !== tabValue) {
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
    tabs.tabCounts = { ...tabs.tabCounts, ...counts };
  }

  function setAvailableTabs(newTabs: TabValue[]): void {
    tabs.availableTabs = [...newTabs];
    
    // Проверяем что текущая вкладка все еще доступна
    if (!tabs.availableTabs.includes(tabs.activeTab)) {
      tabs.activeTab = tabs.availableTabs[0] || 'push';
    }
  }

  function resetTabs(): void {
    // Сбрасываем к дефолтным значениям
    Object.assign(tabs, defaultTabs);
  }

  // === МЕТОДЫ ДЛЯ КРЕАТИВОВ ===

  /**
   * Преобразует FilterState в CreativesFilters
   */
  function mapFiltersToCreativesFilters(): CreativesFilters {
    return {
      searchKeyword: filters.searchKeyword || undefined,
      // country: filters.country !== 'default' ? filters.country : undefined,
      dateCreation: filters.dateCreation !== 'default' ? filters.dateCreation : undefined,
      sortBy: filters.sortBy !== 'default' ? (filters.sortBy as 'creation' | 'activity') : 'creation',
      periodDisplay: filters.periodDisplay !== 'default' ? filters.periodDisplay : undefined,
      advertisingNetworks: filters.advertisingNetworks.length > 0 ? filters.advertisingNetworks : undefined,
      // languages: filters.languages.length > 0 ? filters.languages : undefined,
      operatingSystems: filters.operatingSystems.length > 0 ? filters.operatingSystems : undefined,
      browsers: filters.browsers.length > 0 ? filters.browsers : undefined,
      devices: filters.devices.length > 0 ? filters.devices : undefined,
      imageSizes: filters.imageSizes.length > 0 ? filters.imageSizes : undefined,
      onlyAdult: filters.onlyAdult,
      page: 1, // Всегда начинаем с первой страницы при изменении фильтров
      perPage: 12 // Значение по умолчанию
    };
  }

  /**
   * Загружает креативы с текущими фильтрами
   */
  async function loadCreatives(page: number = 1): Promise<void> {
    console.log('🔽 Начинаем загрузку креативов, page:', page);
    
    try {
      creativesError.value = null;
      creativesLoading.value = true;

      // Преобразуем фильтры Store в формат CreativesService
      const creativesFilters = mapFiltersToCreativesFilters();
      creativesFilters.page = page;

      console.log('📋 Фильтры для загрузки:', creativesFilters);
      console.log('🏷️ Активная вкладка:', tabs.activeTab);

      // Генерируем ключ запроса для предотвращения дубликатов
      const requestKey = JSON.stringify({ filters: creativesFilters, tab: tabs.activeTab });
      
      // Проверяем, не выполняется ли уже такой же запрос
      if (requestKey === lastRequestKey.value && creativesService.isLoading(creativesFilters)) {
        console.log('⏭️ Запрос уже выполняется, пропускаем');
        return;
      }

      lastRequestKey.value = requestKey;
      console.log('🔑 Ключ запроса:', requestKey.substring(0, 50) + '...');

      // Загружаем данные через CreativesService
      console.log('🌐 Вызываем creativesService.loadCreatives...');
      const data = await creativesService.loadCreatives(creativesFilters);
      
      console.log('✅ Данные загружены:', data);
      creativesData.value = data;

    } catch (error) {
      console.error('❌ Ошибка загрузки креативов:', error);
      creativesError.value = error instanceof Error ? error.message : 'Неизвестная ошибка';
      creativesData.value = null;
    } finally {
      creativesLoading.value = false;
      console.log('🏁 Загрузка креативов завершена');
    }
  }

  /**
   * Загружает следующую страницу креативов
   */
  async function loadNextPage(): Promise<void> {
    if (!creativesData.value) return;
    
    const currentPage = creativesData.value.pagination.currentPage;
    const lastPage = creativesData.value.pagination.lastPage;
    
    if (currentPage < lastPage) {
      await loadCreatives(currentPage + 1);
    }
  }

  /**
   * Обновляет креативы при изменении фильтров
   */
  async function refreshCreatives(): Promise<void> {
    await loadCreatives(1);
  }

  /**
   * Очищает данные креативов
   */
  function clearCreatives(): void {
    creativesData.value = null;
    creativesError.value = null;
    creativesLoading.value = false;
    lastRequestKey.value = '';
  }

  /**
   * Отменяет все активные запросы
   */
  function cancelRequests(): void {
    creativesService.cancelAllRequests();
    creativesLoading.value = false;
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
    
    // === СОСТОЯНИЕ КРЕАТИВОВ ===
    creativesData,
    creativesLoading,
    creativesError,
    
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
    
    // === COMPUTED СВОЙСТВА ДЛЯ КРЕАТИВОВ ===
    creatives,
    pagination,
    requestMeta,
    hasCreatives,
    hasSearch,
    activeFiltersCount,
    isLoading,
    hasError,
    
    // Computed состояния (существующие)
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

    // === МЕТОДЫ ДЛЯ КРЕАТИВОВ ===
    mapFiltersToCreativesFilters,
    loadCreatives,
    loadNextPage,
    refreshCreatives,
    clearCreatives,
    cancelRequests,

    // URL синхронизация
    initUrlSync,
    urlSync: () => urlSync,
    isUrlSyncEnabled,

    // Переводы
    setTranslations,
    getTranslation,
  };
});