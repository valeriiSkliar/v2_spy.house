// stores/useFiltersStore.ts
// Центральный Store для модуля креативов на базе современной композиционной архитектуры
//
// 🏗️ АРХИТЕКТУРНАЯ РОЛЬ:
// Этот Store является центральным узлом управления состоянием всего модуля креативов.
// Он интегрирует и координирует работу всех специализированных композаблов.
//
// 📋 ИНТЕГРИРОВАННЫЕ КОМПОЗАБЛЫ:
// - useCreatives          → Управление данными креативов и API запросами
// - useCreativesUrlSync   → Синхронизация состояния с URL параметрами  
// - useFiltersSynchronization → Координация синхронизации между URL, Store и API
//
// 🔄 ПАТТЕРН ПРОКСИРОВАНИЯ:
// Store проксирует методы и computed свойства из композаблов, предоставляя
// единый API для Vue компонентов. Это позволяет компонентам не знать о
// внутренней архитектуре и работать через простой interface.
//
// 🎯 ИСПОЛЬЗУЕТСЯ В:
// - PaginationComponent.vue     → Пагинация креативов
// - CreativesListComponent.vue  → Список креативов  
// - FiltersComponent.vue        → Фильтры креативов
// - TabsComponent.vue           → Вкладки креативов
//
// 📊 УПРАВЛЯЕМОЕ СОСТОЯНИЕ:
// - filters: FilterState        → Состояние всех фильтров
// - tabs: TabsState            → Состояние вкладок и их счетчиков
// - selectOptions              → Опции для dropdown селектов
// - translations               → Переводы интерфейса
//
// 🔗 URL СИНХРОНИЗАЦИЯ:
// Автоматически синхронизирует состояние фильтров с URL параметрами
// через префикс 'cr_' (например: cr_country, cr_page, cr_activeTab)
//
// ⚡ ПРОИЗВОДИТЕЛЬНОСТЬ:
// - Debounced операции для URL синхронизации
// - Кэширование API запросов
// - Reactive updates только при реальных изменениях

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
    savedSettings: [],
    perPage: 12,
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
  
  // Состояние избранного
  const favoritesCount = ref<number | undefined>(undefined);
  const favoritesItems = ref<number[]>([]);
  const isFavoritesLoading = ref(false);

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
  // КОМПОЗАБЛЫ - ИНТЕГРАЦИЯ СПЕЦИАЛИЗИРОВАННОЙ ЛОГИКИ
  // ============================================================================
  
  // Инициализируем композаблы в строгом порядке зависимостей:
  
  // 1️⃣ Базовый композабл для работы с API креативов
  const creativesComposable = useCreatives();
  
  // 2️⃣ URL синхронизация (зависит от типов креативов)
  const urlSync = useCreativesUrlSync();
  
  // 3️⃣ Координатор синхронизации (связывает все воедино)
  const filtersSync = useFiltersSynchronization(
    filters,              // Реактивное состояние фильтров из Store
    tabs,                 // Реактивное состояние вкладок из Store  
    urlSync,              // URL синхронизация
    creativesComposable   // API и данные креативов
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

  // ============================================================================
  // ПРОКСИРОВАНИЕ ДАННЫХ ИЗ КОМПОЗАБЛОВ
  // ============================================================================
  
  // Проксируем computed свойства из композабла креативов для единого API:
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
   * Получает перевод с fallback с поддержкой dot-notation
   */
  function getTranslation(key: string, fallback: string = key): string {
    // Поддержка dot-notation для вложенных объектов (например: 'filter.title')
    const keys = key.split('.');
    let result: any = translations.value;
    
    for (const k of keys) {
      if (result && typeof result === 'object' && k in result) {
        result = result[k];
      } else {
        return fallback;
      }
    }
    
    // Если результат - объект, попробуем найти 'title' ключ по умолчанию
    if (typeof result === 'object' && result !== null) {
      if ('title' in result) {
        return result.title;
      }
      // Или возвращаем fallback если не смогли извлечь строку
      return fallback;
    }
    
    return typeof result === 'string' ? result : fallback;
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
  // МЕТОДЫ КРЕАТИВОВ (ПРОКСИРОВАНИЕ С URL СИНХРОНИЗАЦИЕЙ)
  // ============================================================================
  
  /**
   * Загрузка креативов с указанной страницей
   * 
   * Интегрирует:
   * - Преобразование фильтров Store → API формат
   * - Синхронизацию page с URL параметрами  
   * - Загрузку данных через API композабл
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
   * Загрузка следующей страницы (используется в PaginationComponent)
   */
  async function loadNextPage(): Promise<void> {
    await creativesComposable.loadNextPage();
  }

  /**
   * Обновление креативов (перезагрузка с текущими фильтрами)
   */
  async function refreshCreatives(): Promise<void> {
    await creativesComposable.refreshCreatives();
  }

  // ============================================================================
  // МЕТОДЫ УПРАВЛЕНИЯ ИЗБРАННЫМ
  // ============================================================================
  
  /**
   * Установка количества избранного
   */
  function setFavoritesCount(count: number): void {
    favoritesCount.value = count;
  }

  /**
   * Обновление счетчика избранного с сервера
   */
  async function refreshFavoritesCount(): Promise<void> {
    if (isFavoritesLoading.value) return;

    try {
      isFavoritesLoading.value = true;
      
      // Реальный API вызов
      const response = await window.axios.get('/api/creatives/favorites/count');
      favoritesCount.value = response.data.data.count;
      
      // Эмитим событие обновления
      const event = new CustomEvent('creatives:favorites-updated', {
        detail: {
          count: favoritesCount.value,
          action: 'refresh',
          timestamp: new Date().toISOString()
        }
      });
      document.dispatchEvent(event);
      
    } catch (error) {
      console.error('Ошибка при обновлении счетчика избранного:', error);
      throw error;
    } finally {
      isFavoritesLoading.value = false;
    }
  }

  /**
   * Добавление креатива в избранное
   */
  async function addToFavorites(creativeId: number): Promise<void> {
    if (isFavoritesLoading.value) return;

    try {
      isFavoritesLoading.value = true;
      
      // Оптимистичное обновление
      if (!favoritesItems.value.includes(creativeId)) {
        favoritesItems.value.push(creativeId);
        if (favoritesCount.value !== undefined) {
          favoritesCount.value += 1;
        }
      }
      
      // API вызов для добавления в избранное
      const response = await window.axios.post(`/api/creatives/${creativeId}/favorite`);
      
      // Обновляем count из ответа API (если отличается от оптимистичного)
      if (response.data.data.totalFavorites !== favoritesCount.value) {
        favoritesCount.value = response.data.data.totalFavorites;
      }
      
      // Эмитим событие
      const event = new CustomEvent('creatives:favorites-updated', {
        detail: {
          count: favoritesCount.value || 0,
          action: 'add',
          creativeId,
          timestamp: new Date().toISOString()
        }
      });
      document.dispatchEvent(event);
      
    } catch (error) {
      // Откатываем оптимистичное обновление при ошибке
      const index = favoritesItems.value.indexOf(creativeId);
      if (index > -1) {
        favoritesItems.value.splice(index, 1);
        if (favoritesCount.value !== undefined) {
          favoritesCount.value -= 1;
        }
      }
      
      console.error('Ошибка при добавлении в избранное:', error);
      throw error;
    } finally {
      isFavoritesLoading.value = false;
    }
  }

  /**
   * Удаление креатива из избранного
   */
  async function removeFromFavorites(creativeId: number): Promise<void> {
    if (isFavoritesLoading.value) return;

    try {
      isFavoritesLoading.value = true;
      
      // Оптимистичное обновление
      const index = favoritesItems.value.indexOf(creativeId);
      if (index > -1) {
        favoritesItems.value.splice(index, 1);
        if (favoritesCount.value !== undefined) {
          favoritesCount.value -= 1;
        }
      }
      
      // API вызов для удаления из избранного
      const response = await window.axios.delete(`/api/creatives/${creativeId}/favorite`);
      
      // Обновляем count из ответа API (если отличается от оптимистичного)
      if (response.data.data.totalFavorites !== favoritesCount.value) {
        favoritesCount.value = response.data.data.totalFavorites;
      }
      
      // Эмитим событие
      const event = new CustomEvent('creatives:favorites-updated', {
        detail: {
          count: favoritesCount.value || 0,
          action: 'remove',
          creativeId,
          timestamp: new Date().toISOString()
        }
      });
      document.dispatchEvent(event);
      
    } catch (error) {
      // Откатываем оптимистичное обновление при ошибке
      if (!favoritesItems.value.includes(creativeId)) {
        favoritesItems.value.push(creativeId);
        if (favoritesCount.value !== undefined) {
          favoritesCount.value += 1;
        }
      }
      
      console.error('Ошибка при удалении из избранного:', error);
      throw error;
    } finally {
      isFavoritesLoading.value = false;
    }
  }

  // ============================================================================
  // ВОЗВРАТ ОБЪЕКТА STORE - ЕДИНЫЙ API ДЛЯ VUE КОМПОНЕНТОВ
  // ============================================================================
  
  return {
    // ========================================
    // РЕАКТИВНОЕ СОСТОЯНИЕ
    // ========================================
    filters,                    // Текущие фильтры (FilterState)
    tabs,                       // Состояние вкладок (TabsState)  
    isInitialized,              // Флаг готовности Store
    
    // ========================================
    // ОПЦИИ ДЛЯ СЕЛЕКТОВ И UI
    // ========================================
    countryOptions,             // Опции стран для dropdown
    sortOptions,                // Опции сортировки
    dateRanges,                 // Опции диапазонов дат
    advertisingNetworksOptions, // Опции рекламных сетей
    languagesOptions,           // Опции языков
    operatingSystemsOptions,    // Опции ОС
    browsersOptions,            // Опции браузеров  
    devicesOptions,             // Опции устройств
    imageSizesOptions,          // Опции размеров изображений
    tabOptions,                 // Опции вкладок с счетчиками
    currentTabOption,           // Текущая активная вкладка
    
    // ========================================
    // ПРОКСИРОВАННЫЕ ДАННЫЕ ИЗ КОМПОЗАБЛОВ
    // ========================================
    creatives,                  // Список креативов из API
    pagination,                 // Данные пагинации 
    isLoading,                  // Состояние загрузки
    error,                      // Ошибки загрузки
    hasCreatives,               // Есть ли креативы для отображения
    meta,                       // Метаданные запроса
    hasActiveFilters,           // Есть ли активные фильтры
    
    // ========================================
    // МЕТОДЫ ИНИЦИАЛИЗАЦИИ
    // ========================================
    initializeFilters,          // Основная инициализация Store
    setSelectOptions,           // Установка опций селектов
    setTabOptions,              // Установка опций вкладок
    setTranslations,            // Установка переводов
    getTranslation,             // Получение перевода с fallback
    
    // ========================================
    // МЕТОДЫ УПРАВЛЕНИЯ ФИЛЬТРАМИ
    // ========================================
    updateFilter,               // Обновление любого фильтра
    toggleDetailedFilters,      // Переключение детальных фильтров
    toggleAdultFilter,          // Переключение adult фильтра
    addToMultiSelect,           // Добавление в мультиселект
    removeFromMultiSelect,      // Удаление из мультиселекта
    resetFilters,               // Сброс всех фильтров
    saveSettings,               // Сохранение настроек (TODO)
    
    // ========================================
    // МЕТОДЫ УПРАВЛЕНИЯ ВКЛАДКАМИ  
    // ========================================
    setActiveTab,               // Установка активной вкладки
    
    // ========================================
    // МЕТОДЫ РАБОТЫ С КРЕАТИВАМИ
    // ========================================
    loadCreatives,              // Загрузка креативов (с page)
    loadNextPage,               // Загрузка следующей страницы
    refreshCreatives,           // Перезагрузка креативов
    
    // ========================================
    // СОСТОЯНИЕ И МЕТОДЫ ИЗБРАННОГО
    // ========================================
    favoritesCount,             // Количество избранного
    favoritesItems,             // Список ID избранных креативов
    isFavoritesLoading,         // Состояние загрузки избранного
    setFavoritesCount,          // Установка количества избранного
    refreshFavoritesCount,      // Обновление счетчика с сервера
    addToFavorites,             // Добавление в избранное
    removeFromFavorites,        // Удаление из избранного
    
    // ========================================
    // ПРЯМОЙ ДОСТУП К КОМПОЗАБЛАМ (для отладки)
    // ========================================
    creativesComposable,        // useCreatives композабл
    urlSync,                    // useCreativesUrlSync композабл  
    filtersSync,                // useFiltersSynchronization композабл
  };
});