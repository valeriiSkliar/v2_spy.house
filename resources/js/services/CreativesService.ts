// Типы для системы креативов
interface Creative {
  id: number;
  name: string;
  category: string;
  country: string;
  file_url: string;
  preview_url?: string;
  created_at: string;
  activity_date?: string;
  advertising_networks?: string[];
  languages?: string[];
  operating_systems?: string[];
  browsers?: string[];
  devices?: string[];
  image_sizes?: string[];
  is_adult?: boolean;
}

interface CreativesFilters {
  searchKeyword?: string;
  country?: string;
  dateCreation?: string;
  sortBy?: 'creation' | 'activity';
  periodDisplay?: string;
  advertisingNetworks?: string[];
  languages?: string[];
  operatingSystems?: string[];
  browsers?: string[];
  devices?: string[];
  imageSizes?: string[];
  onlyAdult?: boolean;
  page?: number;
  perPage?: number;
}

interface CreativesResponse {
  data: Creative[];
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
  from: number;
  to: number;
}

interface ProcessedCreativesData {
  items: Creative[];
  pagination: {
    total: number;
    perPage: number;
    currentPage: number;
    lastPage: number;
    from: number;
    to: number;
  };
  meta: {
    hasSearch: boolean;
    activeFiltersCount: number;
    cacheKey: string;
  };
}

// Конфигурация сервиса
interface CreativesServiceConfig {
  defaultCacheTtl: number;
  searchCacheTtl: number;
  debounceDelay: number;
  maxCacheKeyLength: number;
}

/**
 * Сервис для управления креативами с расширенной логикой фильтрации и кэширования
 */
class CreativesService {
  private config: CreativesServiceConfig = {
    defaultCacheTtl: 5 * 60 * 1000, // 5 минут
    searchCacheTtl: 30 * 1000,      // 30 секунд для поиска
    debounceDelay: 300,             // 300ms debounce
    maxCacheKeyLength: 20           // максимальная длина ключа кэша
  };

  private loadingStates = new Map<string, boolean>();
  // private lastRequestTime = 0;

  constructor(config?: Partial<CreativesServiceConfig>) {
    if (config) {
      this.config = { ...this.config, ...config };
    }
  }

  /**
   * Основной метод загрузки креативов с фильтрацией
   */
  async loadCreatives(filters: CreativesFilters = {}): Promise<ProcessedCreativesData> {
    console.log('🔍 CreativesService.loadCreatives вызван с фильтрами:', filters);
    
    // Генерируем уникальный ключ для запроса
    const requestKey = this.generateRequestKey(filters);
    console.log('🔑 Ключ запроса в Service:', requestKey);
    
    // Проверяем, не выполняется ли уже такой запрос
    if (this.loadingStates.get(requestKey)) {
      console.log('⚠️ Запрос уже выполняется, отклоняем');
      throw new Error('Запрос уже выполняется');
    }

    this.loadingStates.set(requestKey, true);
    console.log('🚀 Начинаем выполнение запроса');

    try {
      // Предварительная обработка фильтров
      const processedFilters = this.preprocessFilters(filters);
      console.log('🔧 Обработанные фильтры:', processedFilters);
      
      // Определяем конфигурацию кэширования
      const cacheConfig = this.getCacheConfig(processedFilters);
      console.log('💾 Конфигурация кэша:', cacheConfig);
      
      // Выполняем API запрос (будет подключен на следующем этапе)
      console.log('📡 Вызываем makeApiRequest...');
      const response = await this.makeApiRequest(processedFilters, cacheConfig);
      console.log('📨 Ответ от makeApiRequest:', response);
      
      // Постобработка данных
      const processedData = this.postprocessData(response, processedFilters);
      console.log('✨ Финальные обработанные данные:', processedData);
      
      return processedData;
      
    } finally {
      this.loadingStates.delete(requestKey);
      console.log('🏁 Запрос завершен, состояние очищено');
    }
  }

  /**
   * Предварительная обработка фильтров
   */
  private preprocessFilters(filters: CreativesFilters): CreativesFilters {
    const processed: CreativesFilters = {};

    // Очистка пустых строк и null значений
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== '' && value !== null && value !== undefined) {
        if (Array.isArray(value)) {
          // Для массивов убираем пустые значения
          const cleanArray = value.filter(item => item !== '' && item !== null);
          if (cleanArray.length > 0) {
            processed[key as keyof CreativesFilters] = cleanArray as any;
          }
        } else {
          processed[key as keyof CreativesFilters] = value;
        }
      }
    });

    // Установка значений по умолчанию
    return {
      page: 1,
      perPage: 12,
      sortBy: 'creation',
      country: 'All Categories',
      onlyAdult: false,
      ...processed
    };
  }

  /**
   * Определение конфигурации кэширования
   */
  private getCacheConfig(filters: CreativesFilters) {
    const hasSearch = Boolean(filters.searchKeyword && filters.searchKeyword.length > 0);
    const hasComplexFilters = this.hasComplexFilters(filters);
    
    // Генерируем уникальный ID для кэша
    const cacheId = `creatives-${this.generateCacheKey(filters)}`;
    
    // Определяем TTL в зависимости от типа фильтров
    let ttl = this.config.defaultCacheTtl;
    if (hasSearch) {
      ttl = this.config.searchCacheTtl;
    } else if (hasComplexFilters) {
      ttl = Math.floor(this.config.defaultCacheTtl / 2); // Уменьшаем TTL для сложных фильтров
    }

    return {
      id: cacheId,
      cache: {
        ttl,
        methods: ['get'] as const
      }
    };
  }

  /**
   * Проверка наличия сложных фильтров
   */
  private hasComplexFilters(filters: CreativesFilters): boolean {
    const complexFilterKeys = [
      'advertisingNetworks', 'languages', 'operatingSystems', 
      'browsers', 'devices', 'imageSizes'
    ];
    
    return complexFilterKeys.some(key => {
      const value = filters[key as keyof CreativesFilters];
      return Array.isArray(value) && value.length > 0;
    });
  }

  /**
   * Генерация ключа кэша на основе фильтров
   */
  private generateCacheKey(filters: CreativesFilters): string {
    // Создаем детерминированную строку из фильтров
    const filterString = JSON.stringify(filters, Object.keys(filters).sort());
    
    // Создаем короткий хэш
    let hash = 0;
    for (let i = 0; i < filterString.length; i++) {
      const char = filterString.charCodeAt(i);
      hash = ((hash << 5) - hash) + char;
      hash = hash & hash; // Преобразуем в 32-битное число
    }
    
    // Возвращаем положительный хэш в base36 формате
    return Math.abs(hash).toString(36).substring(0, this.config.maxCacheKeyLength);
  }

  /**
   * Генерация ключа для отслеживания запросов
   */
  private generateRequestKey(filters: CreativesFilters): string {
    return `request-${this.generateCacheKey(filters)}`;
  }

  /**
   * Заглушка для API запроса (будет реализована на следующем этапе)
   */
  private async makeApiRequest(filters: CreativesFilters, cacheConfig: any): Promise<CreativesResponse> {
    console.log('🎯 === makeApiRequest ВЫЗВАН! ===');
    console.log('📋 API запрос с фильтрами:', filters);
    console.log('💾 Конфигурация кэша:', cacheConfig);
    
    // Симулируем небольшую задержку сети для реалистичности
    await new Promise(resolve => setTimeout(resolve, 300));
    
    // Временная заглушка с более детальным логированием
    const mockResponse = {
      data: [],
      total: 0,
      per_page: filters.perPage || 12,
      current_page: filters.page || 1,
      last_page: 1,
      from: 0,
      to: 0
    };
    
    console.log('📤 makeApiRequest возвращает mock ответ:', mockResponse);
    console.log('✅ === makeApiRequest ЗАВЕРШЕН! ===');
    
    return mockResponse;
  }

  /**
   * Постобработка данных после получения от API
   */
  private postprocessData(response: CreativesResponse, filters: CreativesFilters): ProcessedCreativesData {
    // Обработка элементов креативов
    const processedItems = response.data.map(item => ({
      ...item,
      // Добавляем computed свойства
      displayName: this.generateDisplayName(item),
      isRecent: this.isRecentCreative(item),
      // TODO: Добавить проверку избранного на следующих этапах
      isFavorite: false
    }));

    // Подготовка метаданных
    const hasSearch = Boolean(filters.searchKeyword && filters.searchKeyword.length > 0);
    const activeFiltersCount = this.countActiveFilters(filters);
    const cacheKey = this.generateCacheKey(filters);

    return {
      items: processedItems,
      pagination: {
        total: response.total,
        perPage: response.per_page,
        currentPage: response.current_page,
        lastPage: response.last_page,
        from: response.from,
        to: response.to
      },
      meta: {
        hasSearch,
        activeFiltersCount,
        cacheKey
      }
    };
  }

  /**
   * Генерация отображаемого имени креатива
   */
  private generateDisplayName(creative: Creative): string {
    if (creative.name) {
      return creative.name;
    }
    
    // Генерируем имя на основе других данных
    const parts: string[] = [];
    
    if (creative.category) {
      parts.push(creative.category);
    }
    
    if (creative.country) {
      parts.push(creative.country);
    }
    
    return parts.join(' - ') || `Creative #${creative.id}`;
  }

  /**
   * Проверка является ли креатив недавним
   */
  private isRecentCreative(creative: Creative): boolean {
    const createdDate = new Date(creative.created_at);
    const weekAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);
    
    return createdDate > weekAgo;
  }

  /**
   * Подсчет активных фильтров
   */
  private countActiveFilters(filters: CreativesFilters): number {
    let count = 0;
    
    // Исключаем технические параметры из подсчета
    const excludeKeys = ['page', 'perPage', 'sortBy'];
    
    Object.entries(filters).forEach(([key, value]) => {
      if (excludeKeys.includes(key)) return;
      
      if (Array.isArray(value)) {
        if (value.length > 0) count++;
      } else if (value !== '' && value !== null && value !== undefined) {
        // Исключаем значения по умолчанию
        if (key === 'country' && value === 'All Categories') return;
        if (key === 'onlyAdult' && value === false) return;
        
        count++;
      }
    });
    
    return count;
  }

  /**
   * Проверка выполняется ли запрос
   */
  isLoading(filters?: CreativesFilters): boolean {
    if (!filters) {
      // Проверяем есть ли хоть один активный запрос
      return this.loadingStates.size > 0;
    }
    
    const requestKey = this.generateRequestKey(filters);
    return this.loadingStates.get(requestKey) || false;
  }

  /**
   * Отмена всех активных запросов
   */
  cancelAllRequests(): void {
    this.loadingStates.clear();
  }

  /**
   * Получение конфигурации сервиса
   */
  getConfig(): CreativesServiceConfig {
    return { ...this.config };
  }

  /**
   * Обновление конфигурации сервиса
   */
  updateConfig(config: Partial<CreativesServiceConfig>): void {
    this.config = { ...this.config, ...config };
  }

  /**
   * Публичный метод для тестирования предобработки фильтров
   */
  public testPreprocessFilters(filters: CreativesFilters): CreativesFilters {
    return this.preprocessFilters(filters);
  }
}

// Экспортируем типы для использования в других модулях
export type {
  Creative,
  CreativesFilters,
  CreativesResponse, CreativesServiceConfig, ProcessedCreativesData
};

// Экспортируем синглтон сервиса
export const creativesService = new CreativesService();

// Экспортируем класс для создания кастомных инстансов
export default CreativesService; 