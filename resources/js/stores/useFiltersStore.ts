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
// через префикс 'cr_' (например: cr_countries, cr_page, cr_activeTab)
//
// ⚡ ПРОИЗВОДИТЕЛЬНОСТЬ:
// - Debounced операции для URL синхронизации
// - Кэширование API запросов
// - Reactive updates только при реальных изменениях
//
// 🚨 ВАЖНО ДЛЯ TREE-SHAKING:
// Содержит побочные эффекты (watchEffect, реактивные watchers)!
// НЕ УДАЛЯТЬ через tree-shaking в production сборке!

import { useCreatives } from '@/composables/useCreatives';
import { useCreativesCopyText } from '@/composables/useCreativesCopyText';
import { useCreativesDetails } from '@/composables/useCreativesDetails';
import { useCreativesDownloader } from '@/composables/useCreativesDownloader';
import { useCreativesTabOpener } from '@/composables/useCreativesTabOpener';
import { useCreativesUrlSync } from '@/composables/useCreativesUrlSync';
import { useFiltersSynchronization } from '@/composables/useFiltersSynchronization';
import {
  CREATIVES_CONSTANTS,
  type Creative,
  type FavoritesSyncData,
  type FilterOption,
  type FilterState,
  type TabOption,
  type TabsState,
  type TabValue
} from '@/types/creatives.d';
import merge from 'deepmerge';
import debounce from 'lodash.debounce';
import { defineStore } from 'pinia';
import { computed, nextTick, reactive, ref, watchEffect } from 'vue';

export const useCreativesFiltersStore = defineStore('creativesFilters', () => {
  // ============================================================================
  // СОСТОЯНИЕ
  // ============================================================================
  
  const isEmittingTabEvent = ref(false);
  const isTabEventsDisabled = ref(false); // Дополнительный флаг для полного отключения (для тестов)

  // Дефолтные значения фильтров
  const defaultFilters: FilterState = {
    isDetailedVisible: false,
    searchKeyword: '',
    countries: [],
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
  
  // ============================================================================
  // СИСТЕМА ПЕРЕВОДОВ С ЗАЩИТОЙ ОТ RACE CONDITION
  // ============================================================================
  
  // Состояние готовности переводов
  const isTranslationsReady = ref(false);
  const translationsLoadingPromise = ref<Promise<void> | null>(null);
  
  // Очередь ожидающих переводы компонентов
  const translationWaitingQueue = ref<Array<() => void>>([]);
  
  // Базовые переводы (fallback для критических ключей)
  const defaultTranslations: Record<string, string> = {
    'title': 'Filter',
    'searchKeyword': 'Search by Keyword',
    'country': 'Country',
    'dateCreation': 'Date of creation',
    'sortBy': 'Sort by',
    'isDetailedVisible': 'Detailed filtering',
    'languages': 'Languages',
    'advertisingNetworks': 'Advertising networks',
    'operatingSystems': 'Operation systems',
    'browsers': 'Browsers',
    'devices': 'Devices',
    'imageSizes': 'Image sizes',
    'onlyAdult': 'Only adult',
    'copyButton': 'Copy',
    'details.title': 'Details',
    'details.add-to-favorites': 'Add to favorites',
    'details.remove-from-favorites': 'Remove from favorites',
    'details.download': 'Download',
    'details.copy': 'Copy',
    'details.copied': 'Copied',
    'tabs.push': 'Push',
    'tabs.inpage': 'Inpage',
    'tabs.facebook': 'Facebook',
    'tabs.tiktok': 'TikTok'
  };
  
  // Состояние избранного
  const favoritesCount = ref<number | undefined>(undefined);
  const favoritesItems = ref<number[]>([]);
  const isFavoritesLoading = ref(false);
  
  // Состояние загрузки для конкретных креативов (предотвращение множественных запросов)
  const favoritesLoadingMap = ref<Map<number, boolean>>(new Map());

  // Состояние просмотра деталей креативов
  const selectedCreative = ref<Creative | null>(null);
  const isDetailsVisible = ref(false);
  const detailsLoadingMap = ref<Map<number, boolean>>(new Map());

  // Опции для селектов
  const sortOptions = ref<FilterOption[]>([{ value: 'default', label: 'По дате создания' }]);
  const dateRanges = ref<FilterOption[]>([{ value: 'default', label: 'Вся история' }]);

  // Опции для мультиселектов
  const multiSelectOptions = reactive<{
    countries: FilterOption[];
    advertisingNetworks: FilterOption[];
    languages: FilterOption[];
    operatingSystems: FilterOption[];
    browsers: FilterOption[];
    devices: FilterOption[];
    imageSizes: FilterOption[];
  }>({
    countries: [],
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
  
  // 3️⃣ Координатор синхронизации (теперь только утилитарные функции)
  const filtersSync = useFiltersSynchronization(
    filters,              // Реактивное состояние фильтров из Store
    tabs,                 // Реактивное состояние вкладок из Store  
    urlSync,              // URL синхронизация
    creativesComposable   // API и данные креативов
  );
  
  // 4️⃣ Централизованная обработка скачивания креативов
  const downloader = useCreativesDownloader();

  // 5️⃣ Централизованная обработка открытия в новых вкладках
  const tabOpener = useCreativesTabOpener();

  // 6️⃣ Централизованная обработка деталей креативов
  const detailsManager = useCreativesDetails();

  // 7️⃣ Централизованная обработка копирования текста
  const copyTextManager = useCreativesCopyText();

  // ============================================================================
  // WATCHERS - ЦЕНТРАЛИЗОВАННАЯ СИНХРОНИЗАЦИЯ
  // ============================================================================
  
  /**
   * Debounced функция для загрузки креативов (используем lodash.debounce)
   */
  const loadCreativesDebounced = debounce(async () => {
    try {
      // Логирование для production отладки
      if (typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('store:load-creatives', {
          detail: { 
            page: 1,
            source: 'debounced-watcher',
            timestamp: Date.now()
          }
        }));
      }
      
      const creativesFilters = creativesComposable.mapFiltersToCreativesFilters(
        filters, 
        tabs.activeTab, 
        1 // Всегда загружаем первую страницу при изменении фильтров
      );
      
      await creativesComposable.loadCreativesWithFilters(creativesFilters);
    } catch (error) {
      console.error('Ошибка загрузки креативов в Store:', error);
      
      // Логирование ошибки для production
      if (typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('store:load-error', {
          detail: { 
            error: error instanceof Error ? error.message : String(error),
            timestamp: Date.now()
          }
        }));
      }
    }
  }, CREATIVES_CONSTANTS.DEBOUNCE_DELAY);
  
  /**
   * Настраивает все watchers для автоматической синхронизации
   * Все watchers централизованы в Store для предотвращения дублирования
   * ВАЖНО: Вызывается СРАЗУ при создании store для предотвращения проблем с tree-shaking в production
   */
  function setupFiltersWatchers(): void {
    // Логирование для production (через события)
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('store:watchers-initialized', {
        detail: { store: 'CreativesFiltersStore', timestamp: Date.now() }
      }));
    }
    
    // Watcher 1: Store -> URL синхронизация
    watchEffect(() => {
      if (!isInitialized.value) return;
      
      // Отслеживаем изменения фильтров (исключая служебные)
      const filtersToWatch = { ...filters };
      delete (filtersToWatch as any).isDetailedVisible;
      delete (filtersToWatch as any).savedSettings;
      
      // Отслеживаем активную вкладку
      const activeTab = tabs.activeTab;
      
      // Событие для production отладки
      if (typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('store:sync-to-url', {
          detail: { 
            filters: Object.keys(filtersToWatch).length,
            activeTab,
            timestamp: Date.now()
          }
        }));
      }
      
      // Синхронизируем в URL с debounce
      filtersSync.syncToUrl();
    });
    
    // Watcher 2: URL -> Store синхронизация  
    watchEffect(() => {
      if (!isInitialized.value) return;
      
      // Отслеживаем изменения URL состояния
      const urlState = urlSync.state.value;
      
      // Синхронизируем из URL
      if (Object.keys(urlState).length > 0) {
        // Событие для production отладки
        if (typeof window !== 'undefined') {
          window.dispatchEvent(new CustomEvent('store:sync-from-url', {
            detail: { 
              urlStateKeys: Object.keys(urlState).length,
              timestamp: Date.now()
            }
          }));
        }
        
        filtersSync.syncFromUrl();
      }
    });
    
    // Watcher 3: Автоматическая загрузка креативов при изменении фильтров
    watchEffect(() => {
      if (!isInitialized.value) return;
      
      // Отслеживаем значимые фильтры с безопасной проверкой массивов
      const watchedFilters = {
        searchKeyword: filters.searchKeyword,
        countries: Array.isArray(filters.countries) ? [...filters.countries] : [],
        dateCreation: filters.dateCreation,
        sortBy: filters.sortBy,
        periodDisplay: filters.periodDisplay,
        advertisingNetworks: Array.isArray(filters.advertisingNetworks) ? [...filters.advertisingNetworks] : [],
        languages: Array.isArray(filters.languages) ? [...filters.languages] : [],
        operatingSystems: Array.isArray(filters.operatingSystems) ? [...filters.operatingSystems] : [],
        browsers: Array.isArray(filters.browsers) ? [...filters.browsers] : [],
        devices: Array.isArray(filters.devices) ? [...filters.devices] : [],
        imageSizes: Array.isArray(filters.imageSizes) ? [...filters.imageSizes] : [],
        onlyAdult: filters.onlyAdult,
        perPage: filters.perPage,
        activeTab: tabs.activeTab
      };
      
      // Событие для production отладки
      if (typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('store:filters-changed', {
          detail: { 
            filtersCount: Object.keys(watchedFilters).length,
            timestamp: Date.now()
          }
        }));
      }
      
      // Загружаем креативы с debounce
      loadCreativesDebounced();
    });
    
    // Watcher 4: Отдельный watcher для скрытия деталей при изменении фильтров/пагинации/вкладок
    // Отслеживает только значимые изменения, исключая внутренние состояния деталей
    let previousFiltersState: string | null = null;
    
    watchEffect(() => {
      if (!isInitialized.value) return;
      
      // Создаем отпечаток состояния только для значимых изменений
      const currentFiltersState = JSON.stringify({
        searchKeyword: filters.searchKeyword,
        countries: filters.countries,
        dateCreation: filters.dateCreation,
        sortBy: filters.sortBy,
        periodDisplay: filters.periodDisplay,
        advertisingNetworks: filters.advertisingNetworks,
        languages: filters.languages,
        operatingSystems: filters.operatingSystems,
        browsers: filters.browsers,
        devices: filters.devices,
        imageSizes: filters.imageSizes,
        onlyAdult: filters.onlyAdult,
        perPage: filters.perPage,
        activeTab: tabs.activeTab,
        // Добавляем текущую страницу для отслеживания смены страниц
        currentPage: pagination.value.currentPage
      });
      
      // Проверяем, действительно ли изменились фильтры
      if (previousFiltersState !== null && previousFiltersState !== currentFiltersState) {
        // Скрываем детали только при реальном изменении фильтров/пагинации/вкладок
        if (isDetailsVisible.value) {
          selectedCreative.value = null;
          isDetailsVisible.value = false;
          
          // Эмитируем событие скрытия деталей
          document.dispatchEvent(new CustomEvent('creatives:details-hidden', {
            detail: {
              reason: 'filters-changed',
              timestamp: new Date().toISOString()
            }
          }));
          
          console.log('🎯 Детали креатива автоматически скрыты из-за изменения фильтров/пагинации/вкладок');
        }
      }
      
      // Обновляем предыдущее состояние
      previousFiltersState = currentFiltersState;
    });
  }

  // ============================================================================
  // ИНИЦИАЛИЗАЦИЯ WATCHERS - СРАЗУ ПРИ СОЗДАНИИ STORE
  // ============================================================================
  
  // КРИТИЧЕСКИ ВАЖНО: Watchers должны быть созданы СРАЗУ при создании store
  // Это предотвращает их удаление через tree-shaking в production
  setupFiltersWatchers();

  // ============================================================================
  // ГЛОБАЛЬНЫЕ СЛУШАТЕЛИ СОБЫТИЙ - ЦЕНТРАЛИЗОВАННОЕ УПРАВЛЕНИЕ
  // ============================================================================
  
  /**
   * Настраивает слушатели событий избранного, деталей и скачивания
   * Обрабатывает события от карточек креативов
   */
  function setupEventListeners(): void {
    // Слушатель событий избранного от карточек
    const handleFavoriteToggle = async (event: CustomEvent) => {
      const { creativeId, isFavorite } = event.detail;
      
      try {
        if (isFavorite) {
          await removeFromFavorites(creativeId);
        } else {
          await addToFavorites(creativeId);
        }
      } catch (error) {
        console.error('Ошибка обработки избранного:', error);
        
        // Эмитируем событие ошибки для UI
        document.dispatchEvent(new CustomEvent('creatives:favorites-error', {
          detail: {
            creativeId,
            action: isFavorite ? 'remove' : 'add',
            error: error instanceof Error ? error.message : 'Unknown error',
            timestamp: new Date().toISOString()
          }
        }));
      }
    };

    // Слушатель успешного показа деталей от композабла (для обновления состояния Store)
    const handleDetailsShown = (event: CustomEvent) => {
      const { creative } = event.detail;
      if (creative) {
        selectedCreative.value = creative;
        isDetailsVisible.value = true;
        console.log('🎯 Store: детали креатива показаны, состояние обновлено');
      }
    };

    // Слушатель скрытия деталей от композабла (для обновления состояния Store)
    const handleDetailsHidden = () => {
      selectedCreative.value = null;
      isDetailsVisible.value = false;
      console.log('🎯 Store: детали креатива скрыты, состояние обновлено');
    };

    // Регистрируем слушатели
    document.addEventListener('creatives:toggle-favorite', handleFavoriteToggle as unknown as EventListener);
    document.addEventListener('creatives:details-shown', handleDetailsShown as unknown as EventListener);
    document.addEventListener('creatives:details-hidden', handleDetailsHidden as unknown as EventListener);
    
    // Инициализируем обработчик скачивания креативов через композабл
    const downloadCleanup = downloader.setupDownloadEventListener();
    
    // Инициализируем обработчик открытия в новых вкладках через композабл
    const tabOpenerCleanup = tabOpener.initializeTabOpener();
    
    // Инициализируем обработчик деталей креативов через композабл
    const detailsCleanup = detailsManager.setupDetailsEventListener();
    
    // Инициализируем обработчик копирования текста через композабл
    const copyTextCleanup = copyTextManager.setupCopyEventListener();
    
    // Сохраняем функции очистки для использования в cleanupEventListeners
    (cleanupEventListeners as any).downloadCleanup = downloadCleanup;
    (cleanupEventListeners as any).tabOpenerCleanup = tabOpenerCleanup;
    (cleanupEventListeners as any).detailsCleanup = detailsCleanup;
    (cleanupEventListeners as any).copyTextCleanup = copyTextCleanup;
    
    // Логирование для production отладки
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('store:event-listeners-setup', {
        detail: { 
          store: 'CreativesFiltersStore',
          listeners: ['toggle-favorite', 'details-shown', 'details-hidden', 'download', 'open-in-new-tab', 'show-details', 'hide-details', 'toggle-details', 'copy-text'],
          timestamp: Date.now()
        }
      }));
    }
  }

  // КРИТИЧЕСКИ ВАЖНО: Слушатели должны быть настроены СРАЗУ при создании store
  setupEventListeners();

  // ============================================================================
  // МЕТОДЫ ОЧИСТКИ
  // ============================================================================
  
  /**
   * Очищает слушатели событий (для cleanup при unmount)
   */
  function cleanupEventListeners(): void {
    document.removeEventListener('creatives:toggle-favorite', () => {});
    document.removeEventListener('creatives:details-shown', () => {});
    document.removeEventListener('creatives:details-hidden', () => {});
    
    // Очищаем обработчик скачивания если он был инициализирован
    if ((cleanupEventListeners as any).downloadCleanup) {
      (cleanupEventListeners as any).downloadCleanup();
    }
    
    // Очищаем обработчик открытия в новых вкладках если он был инициализирован
    if ((cleanupEventListeners as any).tabOpenerCleanup) {
      (cleanupEventListeners as any).tabOpenerCleanup();
    }
    
    // Очищаем обработчик деталей если он был инициализирован
    if ((cleanupEventListeners as any).detailsCleanup) {
      (cleanupEventListeners as any).detailsCleanup();
    }
    
    // Очищаем обработчик копирования текста если он был инициализирован
    if ((cleanupEventListeners as any).copyTextCleanup) {
      (cleanupEventListeners as any).copyTextCleanup();
    }
    
    // Логирование для production отладки
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('store:event-listeners-cleanup', {
        detail: { 
          store: 'CreativesFiltersStore',
          timestamp: Date.now()
        }
      }));
    }
  }

  // ============================================================================
  // COMPUTED СВОЙСТВА
  // ============================================================================
  
  // Опции для мультиселектов (computed)
  const countriesOptions = computed(() => multiSelectOptions.countries);
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
           filters.countries.length > 0 ||
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
  // ПРОКСИРОВАННЫЕ ДАННЫЕ ИЗ КОМПОЗАБЛОВ
  // ============================================================================
  
  // Проксируем computed свойства из композабла креативов для единого API:
  const creatives = computed(() => creativesComposable.creatives.value);
  const pagination = computed(() => creativesComposable.pagination.value);
  const isLoading = computed(() => creativesComposable.isLoading.value);
  const error = computed(() => creativesComposable.error.value);
  const hasCreatives = computed(() => creatives.value.length > 0);
  const meta = computed(() => creativesComposable.meta.value);
  const searchCount = computed(() => creativesComposable.searchCount.value);

  // Computed свойства для пагинации (для инкапсуляции в PaginationComponent)
  const currentPage = computed(() => pagination.value.currentPage);
  const lastPage = computed(() => pagination.value.lastPage);
  const totalItems = computed(() => pagination.value.total);
  const perPage = computed(() => pagination.value.perPage);
  const fromItem = computed(() => pagination.value.from);
  const toItem = computed(() => pagination.value.to);
  const isOnFirstPage = computed(() => currentPage.value <= 1);
  const isOnLastPage = computed(() => currentPage.value >= lastPage.value);
  const canLoadMore = computed(() => currentPage.value < lastPage.value);
  const shouldShowPagination = computed(() => lastPage.value > 1);

  // Computed свойства для избранного
  const isFavoriteCreative = computed(() => {
    return (creativeId: number): boolean => {
      return favoritesItems.value.includes(creativeId);
    };
  });
  
  // Computed свойство для проверки состояния загрузки избранного конкретного креатива
  const isFavoriteLoading = computed(() => {
    return (creativeId: number): boolean => {
      return favoritesLoadingMap.value.get(creativeId) ?? false;
    };
  });

  // Computed свойства для деталей креативов
  const hasSelectedCreative = computed(() => selectedCreative.value !== null);
  const currentCreativeDetails = computed(() => selectedCreative.value);
  const isDetailsLoading = computed(() => {
    return (creativeId: number): boolean => {
      return detailsLoadingMap.value.get(creativeId) ?? false;
    };
  });

  // ============================================================================
  // МЕТОДЫ ИНИЦИАЛИЗАЦИИ
  // ============================================================================
  
  /**
   * Устанавливает опции для селектов
   */
  function setSelectOptions(options: any): void {
    if (options.sortOptions && Array.isArray(options.sortOptions)) {
      sortOptions.value = [...options.sortOptions];
    }
    
    if (options.dateRanges && Array.isArray(options.dateRanges)) {
      dateRanges.value = [...options.dateRanges];
    }
    
    // Обрабатываем мультиселекты
    const multiSelectFields = [
      'countries', 'advertisingNetworks', 'languages', 'operatingSystems', 
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
   * Устанавливает переводы с защитой от race condition
   * МЕРЖИТ новые переводы с существующими (не перезаписывает!)
   * Поддерживает глубокое слияние вложенных объектов
   */
  function setTranslations(translationsData: Record<string, string>): void {
    // Глубоко мержим новые переводы с существующими вместо полной перезаписи
    // Используем проверенную библиотеку deepmerge вместо кастомной функции
    translations.value = merge(translations.value, translationsData);
    
    // Устанавливаем флаг готовности
    isTranslationsReady.value = true;
    
    // Обрабатываем очередь ожидающих компонентов
    const queue = translationWaitingQueue.value.splice(0);
    queue.forEach(callback => {
      try {
        callback();
      } catch (error) {
        console.error('Ошибка при обработке ожидающего компонента переводов:', error);
      }
    });
    
    // Очищаем промис загрузки для новых запросов
    translationsLoadingPromise.value = null;
    
    // Логирование для production отладки
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('store:translations-ready', {
        detail: { 
          translationsCount: Object.keys(translationsData).length,
          queueProcessed: queue.length,
          timestamp: Date.now()
        }
      }));
    }
  }

  /**
   * Ожидает готовности переводов
   * Используется компонентами для предотвращения race condition
   */
  async function waitForTranslations(): Promise<void> {
    if (isTranslationsReady.value) {
      return Promise.resolve();
    }
    
    // Если уже есть промис загрузки, ждем его
    if (translationsLoadingPromise.value) {
      return translationsLoadingPromise.value;
    }
    
    // Создаем новый промис ожидания
    translationsLoadingPromise.value = new Promise<void>((resolve) => {
      if (isTranslationsReady.value) {
        resolve();
        return;
      }
      
      // Добавляем в очередь ожидания
      translationWaitingQueue.value.push(resolve);
    });
    
    return translationsLoadingPromise.value;
  }

  /**
   * Получает перевод с fallback и защитой от race condition
   * Поддерживает как плоские ключи с точками, так и dot-notation для вложенных объектов
   */
  function getTranslation(key: string, fallback?: string): string {
    const effectiveFallback = fallback || defaultTranslations[key] || key;
    
    // Если переводы не готовы, возвращаем fallback
    if (!isTranslationsReady.value) {
      return effectiveFallback;
    }
    
    // ПРИОРИТЕТ 1: Сначала ищем плоский ключ (например: 'details.title' как есть)
    if (key in translations.value) {
      const directResult = translations.value[key];
      if (typeof directResult === 'string') {
        return directResult;
      }
    }
    
    // ПРИОРИТЕТ 2: Поддержка dot-notation для вложенных объектов (например: 'details.title' → obj.details.title)
    const keys = key.split('.');
    let result: any = translations.value;
    
    for (const k of keys) {
      if (result && typeof result === 'object' && k in result) {
        result = result[k];
      } else {
        return effectiveFallback;
      }
    }
    
    // Если результат - объект, попробуем найти 'title' ключ по умолчанию
    if (typeof result === 'object' && result !== null) {
      if ('title' in result) {
        return result.title;
      }
      // Или возвращаем fallback если не смогли извлечь строку
      return effectiveFallback;
    }
    
    return typeof result === 'string' ? result : effectiveFallback;
  }

  /**
   * Reactive computed для безопасного получения перевода
   * Автоматически обновляется когда переводы становятся доступными
   */
  function useTranslation(key: string, fallback?: string) {
    return computed(() => getTranslation(key, fallback));
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
    
    // 3. Инициализируем синхронизацию фильтров (только утилиты)
    await filtersSync.initialize();
    
    // 4. Загружаем избранные креативы (только для аутентифицированных пользователей)
    try {
      await loadFavoritesIds();
    } catch (error) {
      console.warn('Не удалось загрузить избранные креативы (возможно пользователь не аутентифицирован):', error);
      // Не прерываем инициализацию, если избранное не загрузилось
    }
    
    // 5. Устанавливаем флаг инициализации для активации watchers
    await nextTick();
    isInitialized.value = true;
    
    console.log('✅ CreativesFiltersStore инициализирован, watchers активированы');
  }

  // ============================================================================
  // МЕТОДЫ УПРАВЛЕНИЯ ФИЛЬТРАМИ
  // ============================================================================
  
  /**
   * Универсальное обновление фильтра
   * Обеспечивает принудительную реактивность для production
   */
  function updateFilter<K extends keyof FilterState>(key: K, value: FilterState[K]): void {
    const oldValue = filters[key];
    
    if (oldValue !== value) {
      // Для массивов делаем глубокое сравнение
      if (Array.isArray(value) && Array.isArray(filters[key])) {
        const currentArray = filters[key] as any[];
        const newArray = value as any[];
        
        // Проверяем действительно ли изменился массив
        const hasChanged = currentArray.length !== newArray.length ||
                          !currentArray.every((item, index) => item === newArray[index]);
        
        if (hasChanged) {
          // Принудительное обновление для реактивности
          (filters[key] as any) = [...newArray];
          
          // Событие для production отладки
          if (typeof window !== 'undefined') {
            window.dispatchEvent(new CustomEvent('store:filter-updated', {
              detail: { 
                key, 
                type: 'array',
                oldLength: currentArray.length,
                newLength: newArray.length,
                timestamp: Date.now()
              }
            }));
          }
          
          // ПРИНУДИТЕЛЬНАЯ перезагрузка креативов если watchers не работают
          triggerCreativesReload('filter-update', key);
        }
      } else {
        // Для примитивных значений
        filters[key] = value;
        
        // Событие для production отладки
        if (typeof window !== 'undefined') {
          window.dispatchEvent(new CustomEvent('store:filter-updated', {
            detail: { 
              key, 
              type: typeof value,
              oldValue: oldValue,
              newValue: value,
              timestamp: Date.now()
            }
          }));
        }
        
        // ПРИНУДИТЕЛЬНАЯ перезагрузка креативов если watchers не работают
        triggerCreativesReload('filter-update', key);
      }
    }
  }

  /**
   * Принудительная перезагрузка креативов
   * Используется как fallback если watchers не работают в production
   */
  function triggerCreativesReload(source: string, trigger?: string): void {
    if (!isInitialized.value) return;
    
    // Логирование для отладки
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('store:trigger-reload', {
        detail: { 
          source,
          trigger,
          timestamp: Date.now()
        }
      }));
    }
    
    // Принудительная перезагрузка через debounced функцию
    loadCreativesDebounced();
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
    const currentValues = filters[field];
    
    // Проверяем что поле существует и является массивом
    if (!Array.isArray(currentValues)) {
      // Если поле не существует или не является массивом, создаем новый массив
      (filters[field] as any) = [value];
      return;
    }
    
    // Проверяем что значение еще не добавлено (избегаем дубликатов)
    if (!currentValues.includes(value)) {
      const newValues = [...currentValues, value];
      (filters[field] as any) = newValues;
    }
  }

  /**
   * Удаление из мультиселекта
   */
  function removeFromMultiSelect(field: keyof FilterState, value: string): void {
    const currentValues = filters[field];
    
    // Проверяем что поле существует и является массивом
    if (!Array.isArray(currentValues)) {
      // Если поле не существует или не является массивом, ничего не делаем
      return;
    }
    
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
   * 
   * Примечание: Скрытие деталей при смене вкладки обрабатывается автоматически
   * через watcher, который отслеживает изменения activeTab
   */
  function setActiveTab(tabValue: TabValue): void {
    // Проверяем валидность вкладки и отличие от текущей
    if (!tabs.availableTabs.includes(tabValue) || tabs.activeTab === tabValue) {
      return;
    }

    // Немедленно устанавливаем состояние загрузки для мгновенного отклика UI
    creativesComposable.setIsLoading(true);
  
    // Если события отключены, обновляем состояние но не эмитируем
    if (isTabEventsDisabled.value) {
      tabs.activeTab = tabValue;
      return;
    }
    
    // Проверяем флаг эмиссии для предотвращения циклических событий
    if (isEmittingTabEvent.value) {
      return;
    }
  
    const previousTab = tabs.activeTab;
    
    // Устанавливаем флаг эмиссии СИНХРОННО для блокировки всех последующих событий
    isEmittingTabEvent.value = true;
    
    try {
      // Обновляем активную вкладку
      tabs.activeTab = tabValue;
  
      // Эмитируем событие только если события не отключены глобально
      if (!isTabEventsDisabled.value) {
        const event = new CustomEvent('creatives:tab-changed', {
          detail: {
            previousTab,
            currentTab: tabValue,
            tabOption: currentTabOption.value
          }
        });
        
        document.dispatchEvent(event);
      }
      
    } finally {
      // Сбрасываем флаг эмиссии СИНХРОННО для немедленного снятия блокировки
      // Это предотвращает блокировку последующих событий от пользователя
      isEmittingTabEvent.value = false;
    }
  }

  // Метод для программного отключения эмиссии событий (для тестов):
  function setTabEventEmissionEnabled(enabled: boolean): void {
    isTabEventsDisabled.value = !enabled;
  }

  // ============================================================================
  // МЕТОДЫ КРЕАТИВОВ (ПРОКСИРОВАНИЕ С URL СИНХРОНИЗАЦИЕЙ)
  // ============================================================================
  
  /**
   * Загружает только количество креативов без полного списка
   * Используется для быстрого обновления счетчика при изменении фильтров
   */
  async function loadSearchCount(): Promise<void> {
    const creativesFilters = creativesComposable.mapFiltersToCreativesFilters(
      filters,
      tabs.activeTab,
      1 // Для подсчета страница не важна
    );
    
    await creativesComposable.loadSearchCount(creativesFilters);
  }

  /**
   * Устанавливает количество найденных креативов
   */
  function setSearchCount(count: number): void {
    creativesComposable.setSearchCount(count);
  }

  /**
   * Загрузка креативов с указанной страницей
   * 
   * Интегрирует:
   * - Преобразование фильтров Store → API формат
   * - Синхронизацию page с URL параметрами  
   * - Загрузку данных через API композабл
   * 
   * Примечание: Скрытие деталей при смене страницы обрабатывается автоматически
   * через watcher, который отслеживает изменения perPage в фильтрах
   */
  async function loadCreatives(page: number = 1): Promise<void> {
    // Логирование для production отладки
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('store:load-creatives', {
        detail: { 
          page,
          source: 'direct-call',
          timestamp: Date.now()
        }
      }));
    }
    
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
    const nextPage = pagination.value.currentPage + 1;
    if (nextPage <= pagination.value.lastPage) {
      await loadCreatives(nextPage);
    }
  }

  /**
   * Загрузка конкретной страницы (используется в PaginationComponent)
   */
  async function loadPage(page: number): Promise<void> {
    if (page >= 1 && page <= pagination.value.lastPage && page !== pagination.value.currentPage && !isLoading.value) {
      await loadCreatives(page);
    }
  }

  /**
   * Загрузка предыдущей страницы (используется в PaginationComponent)
   */
  async function loadPreviousPage(): Promise<void> {
    const prevPage = pagination.value.currentPage - 1;
    if (prevPage >= 1) {
      await loadCreatives(prevPage);
    }
  }

  /**
   * Переход на первую страницу (используется в PaginationComponent)
   */
  async function goToFirstPage(): Promise<void> {
    if (pagination.value.currentPage !== 1) {
      await loadCreatives(1);
    }
  }

  /**
   * Переход на последнюю страницу (используется в PaginationComponent)
   */
  async function goToLastPage(): Promise<void> {
    const lastPage = pagination.value.lastPage;
    if (pagination.value.currentPage !== lastPage) {
      await loadCreatives(lastPage);
    }
  }

  /**
   * Переход на следующую страницу (используется в PaginationComponent)
   */
  async function goToNextPage(): Promise<void> {
    await loadNextPage();
  }

  /**
   * Переход на предыдущую страницу (используется в PaginationComponent)
   */
  async function goToPreviousPage(): Promise<void> {
    await loadPreviousPage();
  }

  /**
   * Обновление креативов (перезагрузка с текущими фильтрами)
   */
  async function refreshCreatives(): Promise<void> {
    await creativesComposable.refreshCreatives();
  }

  // ============================================================================
  // МЕТОДЫ УПРАВЛЕНИЯ ДЕТАЛЯМИ КРЕАТИВОВ - ПРОКСИРОВАНИЕ К КОМПОЗАБЛУ
  // ============================================================================
  
  // Все методы управления деталями креативов перенесены в композабл useCreativesDetails
  // Store больше не содержит логику отображения деталей, только состояние для UI
  // Методы доступны через detailsManager композабл в return объекте

  // ============================================================================
  // МЕТОДЫ УПРАВЛЕНИЯ ИЗБРАННЫМ
  // ============================================================================

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
   * Загрузка списка ID избранных креативов
   */
  async function loadFavoritesIds(): Promise<void> {
    if (isFavoritesLoading.value) return;

    try {
      isFavoritesLoading.value = true;
      
      // Реальный API вызов для получения списка ID
      const response = await window.axios.get('/api/creatives/favorites/ids');
      
      // Обновляем состояние
      favoritesItems.value = response.data.data.ids || [];
      favoritesCount.value = response.data.data.count || 0;
      
      console.log('✅ Загружены избранные креативы:', {
        count: favoritesCount.value,
        ids: favoritesItems.value
      });
      
      // Эмитим событие загрузки
      const event = new CustomEvent('creatives:favorites-loaded', {
        detail: {
          count: favoritesCount.value,
          ids: favoritesItems.value,
          timestamp: new Date().toISOString()
        }
      });
      document.dispatchEvent(event);
      
    } catch (error) {
      console.error('Ошибка при загрузке списка избранного:', error);
      // При ошибке сбрасываем состояние
      favoritesItems.value = [];
      favoritesCount.value = 0;
      throw error;
    } finally {
      isFavoritesLoading.value = false;
    }
  }

  /**
   * Добавление креатива в избранное
   */
  async function addToFavorites(creativeId: number): Promise<void> {
    // Проверяем глобальное состояние загрузки и состояние конкретного креатива
    if (isFavoritesLoading.value || favoritesLoadingMap.value.get(creativeId)) {
      console.warn(`Добавление в избранное для креатива ${creativeId} уже выполняется`);
      return;
    }

    try {
      // Устанавливаем состояние загрузки для конкретного креатива
      favoritesLoadingMap.value.set(creativeId, true);
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
      
    } catch (error: any) {
      // Проверяем, является ли это ошибкой синхронизации (409 - уже в избранном)
      if (error.response?.status === 409 && error.response?.data?.code === 'ALREADY_IN_FAVORITES') {
        // Синхронизируем состояние с сервером
        const syncData: FavoritesSyncData = error.response.data.data;

        // Обновляем локальное состояние
        updateCreativeInList(syncData.creativeId, {
          isFavorite: syncData.isFavorite
        });

        // Обновляем общий счетчик
        favoritesCount.value = syncData.totalFavorites;

        // Показываем пользователю информативное сообщение
        showMessage('Креатив уже в избранном', 'info');

        return; // Выходим, не показывая ошибку
      }

      // Откатываем оптимистичное обновление при других ошибках
      const index = favoritesItems.value.indexOf(creativeId);
      if (index > -1) {
        favoritesItems.value.splice(index, 1);
        if (favoritesCount.value !== undefined) {
          favoritesCount.value -= 1;
        }
      }
      
      console.error('Ошибка при добавлении в избранное:', error);
      showMessage('Ошибка при добавлении в избранное', 'error');
      throw error;
    } finally {
      // Очищаем состояние загрузки для конкретного креатива и глобально
      favoritesLoadingMap.value.delete(creativeId);
      isFavoritesLoading.value = false;
    }
  }

  /**
   * Удаление креатива из избранного
   */
  async function removeFromFavorites(creativeId: number): Promise<void> {
    // Проверяем глобальное состояние загрузки и состояние конкретного креатива
    if (isFavoritesLoading.value || favoritesLoadingMap.value.get(creativeId)) {
      console.warn(`Удаление из избранного для креатива ${creativeId} уже выполняется`);
      return;
    }

    try {
      // Устанавливаем состояние загрузки для конкретного креатива
      favoritesLoadingMap.value.set(creativeId, true);
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
      
    } catch (error: any) {
      // Проверяем, является ли это ошибкой синхронизации (404 - не в избранном)
      if (error.response?.status === 404 && error.response?.data?.code === 'NOT_IN_FAVORITES') {
        // Синхронизируем состояние с сервером
        const syncData: FavoritesSyncData = error.response.data.data;

        // Обновляем локальное состояние
        updateCreativeInList(syncData.creativeId, {
          isFavorite: syncData.isFavorite
        });

        // Обновляем общий счетчик
        favoritesCount.value = syncData.totalFavorites;

        // Показываем пользователю информативное сообщение
        showMessage('Креатив не найден в избранном', 'info');

        return; // Выходим, не показывая ошибку
      }

      // Откатываем оптимистичное обновление при других ошибках
      if (!favoritesItems.value.includes(creativeId)) {
        favoritesItems.value.push(creativeId);
        if (favoritesCount.value !== undefined) {
          favoritesCount.value += 1;
        }
      }
      
      console.error('Ошибка при удалении из избранного:', error);
      showMessage('Ошибка при удалении из избранного', 'error');
      throw error;
    } finally {
      // Очищаем состояние загрузки для конкретного креатива и глобально
      favoritesLoadingMap.value.delete(creativeId);
      isFavoritesLoading.value = false;
    }
  }

  
  /**
   * Установка количества избранного
   */
  function setFavoritesCount(count: number): void {
    favoritesCount.value = count;
  }

  /**
   * Обновление креатива в локальном состоянии
   * Используется для синхронизации данных с сервером
   */
  function updateCreativeInList(creativeId: number, updates: Partial<Creative>): void {
    // Обновляем в основном списке креативов
    const creative = creatives.value.find((c: Creative) => c.id === creativeId);
    if (creative) {
      Object.assign(creative, updates);
    }

    // Обновляем в выбранном креативе для деталей, если это он
    if (selectedCreative.value && selectedCreative.value.id === creativeId) {
      Object.assign(selectedCreative.value, updates);
    }

    // Обновляем состояние избранного в локальном массиве
    if ('isFavorite' in updates) {
      const isInFavorites = favoritesItems.value.includes(creativeId);
      
      if (updates.isFavorite && !isInFavorites) {
        favoritesItems.value.push(creativeId);
      } else if (!updates.isFavorite && isInFavorites) {
        const index = favoritesItems.value.indexOf(creativeId);
        if (index > -1) {
          favoritesItems.value.splice(index, 1);
        }
      }
    }
  }

  /**
   * Синхронизация статуса избранного с сервером
   * Загружает актуальный статус креатива и обновляет локальное состояние
   */
  async function syncFavoriteStatus(creativeId: number): Promise<FavoritesSyncData> {
    try {
      const response = await window.axios.get(`/api/creatives/${creativeId}/favorite/status`);
      const data: FavoritesSyncData = response.data.data;

      // Обновляем локальное состояние креатива
      updateCreativeInList(data.creativeId, {
        isFavorite: data.isFavorite
      });

      // Обновляем общий счетчик
      favoritesCount.value = data.totalFavorites;

      return data;
    } catch (error) {
      console.error('Ошибка синхронизации статуса избранного:', error);
      throw error;
    }
  }

  /**
   * Показать сообщение пользователю
   * В будущем может быть заменено на toast/notification систему
   */
  function showMessage(message: string, type: 'info' | 'error' | 'success' = 'info'): void {
    // Временная реализация через console
    // В будущем можно заменить на toast/notification
    console.log(`[${type.toUpperCase()}] ${message}`);
    
    // Эмитируем событие для возможной обработки в UI
    document.dispatchEvent(new CustomEvent('creatives:user-message', {
      detail: {
        message,
        type,
        timestamp: new Date().toISOString()
      }
    }));
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
    sortOptions,                // Опции сортировки
    dateRanges,                 // Опции диапазонов дат
    countriesOptions,           // Опции стран для мультиселекта
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
    searchCount,                // Количество найденных креативов
    hasActiveFilters,           // Есть ли активные фильтры
    
    // ========================================
    // COMPUTED СВОЙСТВА ПАГИНАЦИИ
    // ========================================
    currentPage,                // Текущая страница
    lastPage,                   // Последняя страница
    totalItems,                 // Общее количество элементов
    perPage,                    // Элементов на страницу
    fromItem,                   // Номер первого элемента на странице
    toItem,                     // Номер последнего элемента на странице
    isOnFirstPage,              // Находимся ли на первой странице
    isOnLastPage,               // Находимся ли на последней странице
    canLoadMore,                // Можно ли загрузить еще
    shouldShowPagination,       // Нужно ли показывать пагинацию
    
    // ========================================
    // COMPUTED СВОЙСТВА ИЗБРАННОГО
    // ========================================
    isFavoriteCreative,         // Функция проверки избранного креатива
    isFavoriteLoading,          // Функция проверки состояния загрузки избранного креатива
    
    // ========================================
    // СОСТОЯНИЕ И COMPUTED СВОЙСТВА ДЕТАЛЕЙ
    // ========================================
    selectedCreative,           // Выбранный креатив для просмотра деталей
    isDetailsVisible,           // Видна ли панель деталей
    hasSelectedCreative,        // Есть ли выбранный креатив
    currentCreativeDetails,     // Текущие детали креатива (алиас для selectedCreative)
    isDetailsLoading,           // Функция проверки состояния загрузки деталей креатива
    
    // ========================================
    // МЕТОДЫ ИНИЦИАЛИЗАЦИИ
    // ========================================
    initializeFilters,          // Основная инициализация Store
    setSelectOptions,           // Установка опций селектов
    setTabOptions,              // Установка опций вкладок
    setTranslations,            // Установка переводов
    getTranslation,             // Получение перевода с fallback
    useTranslation,             // Reactive перевод для компонентов
    waitForTranslations,        // Ожидание готовности переводов
    
    // ========================================
    // СОСТОЯНИЕ ПЕРЕВОДОВ
    // ========================================
    isTranslationsReady,        // Готовы ли переводы
    defaultTranslations,        // Базовые переводы (fallback)
    
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
    setTabEventEmissionEnabled, // Отключение/включение событий (для тестов)
    
    // ========================================
    // МЕТОДЫ РАБОТЫ С КРЕАТИВАМИ
    // ========================================
    loadCreatives,              // Загрузка креативов (с page)
    loadNextPage,               // Загрузка следующей страницы
    loadPage,                   // Загрузка конкретной страницы
    loadPreviousPage,           // Загрузка предыдущей страницы
    goToFirstPage,             // Переход на первую страницу
    goToLastPage,              // Переход на последнюю страницу
    goToNextPage,              // Переход на следующую страницу
    goToPreviousPage,          // Переход на предыдущую страницу
    refreshCreatives,           // Перезагрузка креативов
    loadSearchCount,            // Загрузка только количества креативов
    setSearchCount,             // Установка количества креативов
    
    // ========================================
    // СОСТОЯНИЕ И МЕТОДЫ ИЗБРАННОГО
    // ========================================
    favoritesCount,             // Количество избранного
    favoritesItems,             // Список ID избранных креативов
    isFavoritesLoading,         // Состояние загрузки избранного
    favoritesLoadingMap,        // Map состояний загрузки для конкретных креативов
    setFavoritesCount,          // Установка количества избранного
    refreshFavoritesCount,      // Обновление счетчика с сервера
    loadFavoritesIds,           // Загрузка списка ID избранных креативов
    addToFavorites,             // Добавление в избранное
    removeFromFavorites,        // Удаление из избранного
    updateCreativeInList,       // Обновление креатива в локальном состоянии
    syncFavoriteStatus,         // Синхронизация статуса избранного с сервером
    showMessage,                // Показ сообщений пользователю
    
    // ========================================
    // ПРЯМОЙ ДОСТУП К КОМПОЗАБЛАМ (для отладки)
    // ========================================
    creativesComposable,        // useCreatives композабл
    urlSync,                    // useCreativesUrlSync композабл  
    filtersSync,                // useFiltersSynchronization композабл
    downloader,                 // useCreativesDownloader композабл
    tabOpener,                  // useCreativesTabOpener композабл
    detailsManager,             // useCreativesDetails композабл
    copyTextManager,            // useCreativesCopyText композабл
    
    // ========================================
    // МЕТОДЫ ОЧИСТКИ
    // ========================================
    cleanupEventListeners,      // Очистка слушателей событий
  };
});