// composables/useCreatives.ts
// Композабл для управления креативами

import { creativesService } from '@/services/CreativesService';
import type {
  Creative,
  CreativesFilters,
  FilterState,
  Pagination,
  ProcessedCreativesData,
  RequestMeta,
  TabValue,
  UseCreativesReturn
} from '@/types/creatives.d';
import { CREATIVES_CONSTANTS } from '@/types/creatives.d';
import { computed, ref, shallowRef } from 'vue';

/**
 * Композабл для управления креативами
 * Отвечает только за загрузку и состояние креативов
 */
export function useCreatives(): UseCreativesReturn {
  // ============================================================================
  // СОСТОЯНИЕ
  // ============================================================================
  
  // Данные креативов (shallow ref для производительности больших списков)
  const creativesData = shallowRef<ProcessedCreativesData | null>(null);
  
  // Состояние загрузки и ошибок
  const isLoading = ref(false);
  const error = ref<string | null>(null);
  
  // Кэш последнего запроса для предотвращения дубликатов
  const lastRequestSignature = ref<string>('');

  // ============================================================================
  // COMPUTED СВОЙСТВА
  // ============================================================================
  
  // Список креативов
  const creatives = computed((): Creative[] => {
    return creativesData.value?.items || [];
  });
  
  // Информация о пагинации
  const pagination = computed((): Pagination => {
    return creativesData.value?.pagination || {
      total: 0,
      perPage: CREATIVES_CONSTANTS.DEFAULT_PAGE_SIZE,
      currentPage: 1,
      lastPage: 1,
      from: 0,
      to: 0
    };
  });
  
  // Метаданные запроса
  const meta = computed((): RequestMeta => {
    return creativesData.value?.meta || {
      hasSearch: false,
      activeFiltersCount: 0,
      cacheKey: ''
    };
  });
  
  // Вспомогательные computed
  const hasCreatives = computed((): boolean => creatives.value.length > 0);
  const hasError = computed((): boolean => error.value !== null);
  const hasSearch = computed((): boolean => meta.value.hasSearch);
  
  // Состояние пагинации
  const canLoadMore = computed((): boolean => {
    const pag = pagination.value;
    return pag.currentPage < pag.lastPage;
  });
  
  const isFirstPage = computed((): boolean => pagination.value.currentPage === 1);
  const isLastPage = computed((): boolean => pagination.value.currentPage === pagination.value.lastPage);

  // ============================================================================
  // УТИЛИТАРНЫЕ ФУНКЦИИ
  // ============================================================================
  
  /**
   * Преобразует FilterState в CreativesFilters
   */
  function mapFiltersToCreativesFilters(
    filters: FilterState, 
    activeTab: TabValue, 
    page: number = 1
  ): CreativesFilters {
    return {
      searchKeyword: filters.searchKeyword || undefined,
      country: filters.country !== 'default' ? filters.country : undefined,
      dateCreation: filters.dateCreation !== 'default' ? filters.dateCreation : undefined,
      sortBy: filters.sortBy !== 'default' ? (filters.sortBy as any) : 'creation',
      periodDisplay: filters.periodDisplay !== 'default' ? filters.periodDisplay : undefined,
      advertisingNetworks: filters.advertisingNetworks.length > 0 ? filters.advertisingNetworks : undefined,
      languages: filters.languages.length > 0 ? filters.languages : undefined,
      operatingSystems: filters.operatingSystems.length > 0 ? filters.operatingSystems : undefined,
      browsers: filters.browsers.length > 0 ? filters.browsers : undefined,
      devices: filters.devices.length > 0 ? filters.devices : undefined,
      imageSizes: filters.imageSizes.length > 0 ? filters.imageSizes : undefined,
      onlyAdult: filters.onlyAdult,
      activeTab,
      page,
      perPage: CREATIVES_CONSTANTS.DEFAULT_PAGE_SIZE
    };
  }
  
  /**
   * Генерирует подпись запроса для предотвращения дубликатов
   */
  function generateRequestSignature(filters: CreativesFilters): string {
    const { page, ...otherFilters } = filters;
    return JSON.stringify({ filters: otherFilters, page });
  }
  
  /**
   * Проверяет изменились ли фильтры (исключая страницу)
   */
  function hasFiltersChanged(currentFilters: CreativesFilters, previousSignature: string): boolean {
    const { page, ...otherFilters } = currentFilters;
    const currentSignature = JSON.stringify({ filters: otherFilters, page: 1 });
    const { page: prevPage, ...prevOtherFilters } = JSON.parse(previousSignature).filters || {};
    const prevSignatureWithoutPage = JSON.stringify({ filters: prevOtherFilters, page: 1 });
    
    return currentSignature !== prevSignatureWithoutPage;
  }

  // ============================================================================
  // ОСНОВНЫЕ МЕТОДЫ
  // ============================================================================
  
  /**
   * Загружает креативы с указанными фильтрами
   */
  async function loadCreativesWithFilters(filters: CreativesFilters): Promise<void> {
    const requestSignature = generateRequestSignature(filters);
    
    // Проверяем дубликаты запросов
    if (requestSignature === lastRequestSignature.value && creativesService.isLoading(filters)) {
      return; // Запрос уже выполняется
    }
    
    lastRequestSignature.value = requestSignature;
    
    try {
      error.value = null;
      isLoading.value = true;
      
      // Если изменились фильтры (не страница), очищаем данные для UX
      if (creativesData.value && hasFiltersChanged(filters, lastRequestSignature.value)) {
        creativesData.value = null;
      }
      
      const data = await creativesService.loadCreatives(filters);
      creativesData.value = data;
      
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Неизвестная ошибка';
      error.value = errorMessage;
      creativesData.value = null;
      
      // Логируем только в dev режиме
      if (process.env.NODE_ENV === 'development') {
        console.error('Ошибка загрузки креативов:', err);
      }
      
    } finally {
      isLoading.value = false;
    }
  }
  
  /**
   * Загружает креативы (основной метод)
   * Может использоваться с переданными фильтрами или без них
   */
  async function loadCreatives(
    page: number = 1, 
    customFilters?: CreativesFilters
  ): Promise<void> {
    if (customFilters) {
      // Если переданы конкретные фильтры, используем их
      const filters = { ...customFilters, page };
      await loadCreativesWithFilters(filters);
    } else {
      // Иначе этот метод должен быть переопределен в store для использования текущих фильтров
      console.warn('loadCreatives вызван без фильтров. Переопределите этот метод в store для использования текущих фильтров store.');
    }
  }
  
  /**
   * Обновляет креативы (загружает первую страницу)
   */
  async function refreshCreatives(): Promise<void> {
    await loadCreatives(1);
  }
  
  /**
   * Загружает следующую страницу
   */
  async function loadNextPage(): Promise<void> {
    if (!canLoadMore.value || isLoading.value) return;
    
    const nextPage = pagination.value.currentPage + 1;
    await loadCreatives(nextPage);
  }
  
  /**
   * Загружает конкретную страницу
   */
  async function loadPage(page: number): Promise<void> {
    if (page < 1 || page > pagination.value.lastPage || isLoading.value) return;
    
    await loadCreatives(page);
  }
  
  /**
   * Очищает данные креативов
   */
  function clearCreatives(): void {
    creativesData.value = null;
    error.value = null;
    isLoading.value = false;
    lastRequestSignature.value = '';
  }
  
  /**
   * Отменяет все активные запросы
   */
  function cancelRequests(): void {
    creativesService.cancelAllRequests();
    isLoading.value = false;
  }
  
  /**
   * Сбрасывает состояние ошибки
   */
  function clearError(): void {
    error.value = null;
  }

  // ============================================================================
  // МЕТОДЫ ДЛЯ РАБОТЫ С ОТДЕЛЬНЫМИ КРЕАТИВАМИ
  // ============================================================================
  
  /**
   * Находит креатив по ID
   */
  function findCreativeById(id: number): Creative | undefined {
    return creatives.value.find(creative => creative.id === id);
  }
  
  /**
   * Добавляет креатив в избранное (заглушка)
   */
  function toggleFavorite(creativeId: number): void {
    const creative = findCreativeById(creativeId);
    if (creative) {
      creative.isFavorite = !creative.isFavorite;
      // TODO: Отправить изменение на сервер
    }
  }
  
  /**
   * Фильтрует креативы по критерию
   */
  function filterCreatives(predicate: (creative: Creative) => boolean): Creative[] {
    return creatives.value.filter(predicate);
  }
  
  /**
   * Получает креативы для взрослых
   */
  function getAdultCreatives(): Creative[] {
    return filterCreatives(creative => creative.is_adult === true);
  }
  
  /**
   * Получает недавние креативы
   */
  function getRecentCreatives(): Creative[] {
    return filterCreatives(creative => creative.isRecent === true);
  }
  
  /**
   * Получает избранные креативы
   */
  function getFavoriteCreatives(): Creative[] {
    return filterCreatives(creative => creative.isFavorite === true);
  }

  // ============================================================================
  // СТАТИСТИКА И УТИЛИТЫ
  // ============================================================================
  
  /**
   * Получает статистику креативов
   */
  function getCreativesStats() {
    const total = creatives.value.length;
    const adult = getAdultCreatives().length;
    const recent = getRecentCreatives().length;
    const favorites = getFavoriteCreatives().length;
    
    return {
      total,
      adult,
      recent,
      favorites,
      adultPercentage: total > 0 ? Math.round((adult / total) * 100) : 0,
      recentPercentage: total > 0 ? Math.round((recent / total) * 100) : 0,
    };
  }
  
  /**
   * Проверяет состояние загрузки от сервиса
   */
  function isServiceLoading(): boolean {
    return creativesService.isLoading();
  }
  
  /**
   * Получает информацию о кэше сервиса
   */
  function getCacheInfo() {
    return creativesService.getCacheStats();
  }

  // ============================================================================
  // ВОЗВРАЩАЕМЫЙ ОБЪЕКТ
  // ============================================================================
  
  return {
    // Состояние (readonly)
    creatives: computed(() => creatives.value),
    pagination: computed(() => pagination.value),
    meta: computed(() => meta.value),
    isLoading: computed(() => isLoading.value),
    error: computed(() => error.value),
    
    // Вспомогательные computed
    // hasCreatives: computed(() => hasCreatives.value),
    // hasError: computed(() => hasError.value),
    // hasSearch: computed(() => hasSearch.value),
    // canLoadMore: computed(() => canLoadMore.value),
    // isFirstPage: computed(() => isFirstPage.value),
    // isLastPage: computed(() => isLastPage.value),
    
    // Основные методы
    loadCreatives,
    loadCreativesWithFilters,
    refreshCreatives,
    loadNextPage,
    // loadPage,
    clearCreatives,
    // cancelRequests,
    // clearError,
    
    // Методы для работы с креативами
    // findCreativeById,
    // toggleFavorite,
    // filterCreatives,
    // getAdultCreatives,
    // getRecentCreatives,
    // getFavoriteCreatives,
    
    // Утилиты
    mapFiltersToCreativesFilters,
    // getCreativesStats,
    // isServiceLoading,
    // getCacheInfo,
  };
}