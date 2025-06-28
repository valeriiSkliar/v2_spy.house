// services/CreativesService.ts
// Оптимизированный сервис для работы с креативами

import type {
  Creative,
  CreativesFilters,
  CreativesResponse,
  CreativesServiceConfig,
  ProcessedCreativesData,
  ValidationResult
} from '@/types/creatives.d';
import { CREATIVES_CONSTANTS } from '@/types/creatives.d';
import axios, { type AxiosResponse, type CancelTokenSource } from 'axios';

/**
 * Конфигурация по умолчанию
 */
const DEFAULT_CONFIG: CreativesServiceConfig = {
  defaultCacheTtl: CREATIVES_CONSTANTS.CACHE_TTL.DEFAULT,
  searchCacheTtl: CREATIVES_CONSTANTS.CACHE_TTL.SEARCH,
  debounceDelay: CREATIVES_CONSTANTS.DEBOUNCE_DELAY,
  maxCacheKeyLength: 20,
  retryAttempts: 3,
  retryDelay: 1000,
};

/**
 * Оптимизированный сервис для управления креативами
 */
class CreativesService {
  private config: CreativesServiceConfig;
  private requestCache = new Map<string, Promise<ProcessedCreativesData>>();
  private loadingStates = new Map<string, CancelTokenSource>();
  private validationCache = new Map<string, ValidationResult>();

  constructor(config: Partial<CreativesServiceConfig> = {}) {
    this.config = { ...DEFAULT_CONFIG, ...config };
  }

  /**
   * Основной метод загрузки креативов
   */
  async loadCreatives(filters: CreativesFilters = {}): Promise<ProcessedCreativesData> {
    const requestKey = this.generateRequestKey(filters);
    
    // Проверяем кэш запросов
    if (this.requestCache.has(requestKey)) {
      return this.requestCache.get(requestKey)!;
    }
    
    // Отменяем предыдущий запрос если есть
    this.cancelRequest(requestKey);
    
    // Создаем cancel token для нового запроса
    const cancelSource = axios.CancelToken.source();
    this.loadingStates.set(requestKey, cancelSource);
    
    // Создаем промис для запроса
    const requestPromise = this.executeRequest(filters, cancelSource);
    
    // Кэшируем промис
    this.requestCache.set(requestKey, requestPromise);
    
    try {
      const result = await requestPromise;
      
      // Очищаем кэш через TTL
      this.scheduleCleanup(requestKey, filters);
      
      return result;
      
    } catch (error) {
      // Удаляем из кэша при ошибке
      this.requestCache.delete(requestKey);
      throw error;
    } finally {
      // Очищаем состояние загрузки
      this.loadingStates.delete(requestKey);
    }
  }

  /**
   * Выполняет запрос с retry логикой
   */
  private async executeRequest(
    filters: CreativesFilters, 
    cancelSource: CancelTokenSource
  ): Promise<ProcessedCreativesData> {
    const processedFilters = this.preprocessFilters(filters);
    let lastError: Error;
    
    for (let attempt = 1; attempt <= this.config.retryAttempts!; attempt++) {
      try {
        // Валидация (с кэшированием)
        await this.validateFilters(processedFilters);
        
        // API запрос
        const response = await this.makeApiRequest(processedFilters, cancelSource);
        
        // Постобработка
        return this.postprocessData(response, processedFilters);
        
      } catch (error) {
        lastError = error as Error;
        
        // Не повторяем для отмененных запросов
        if (axios.isCancel(error)) {
          throw error;
        }
        
        // Не повторяем для ошибок валидации
        if (error instanceof ValidationError) {
          throw error;
        }
        
        // Логируем только в dev режиме
        if (process.env.NODE_ENV === 'development') {
          console.warn(`Попытка ${attempt} не удалась:`, error);
        }
        
        // Ждем перед повтором (кроме последней попытки)
        if (attempt < this.config.retryAttempts!) {
          await this.delay(this.config.retryDelay! * attempt);
        }
      }
    }
    
    throw lastError!;
  }

  /**
   * Валидирует фильтры с кэшированием
   */
  private async validateFilters(filters: CreativesFilters): Promise<void> {
    const validationKey = this.generateValidationKey(filters);
    
    // Проверяем кэш валидации
    if (this.validationCache.has(validationKey)) {
      const cached = this.validationCache.get(validationKey)!;
      if (!cached.isValid) {
        throw new ValidationError('Ошибка валидации фильтров', cached.errors);
      }
      return;
    }
    
    try {
      const response = await axios.get('/api/creatives/filters/validate', { 
        params: filters,
        timeout: 5000 
      });
      
      if (response.data.status === 'success') {
        const validationResult: ValidationResult = {
          isValid: true,
          errors: {},
          rejectedValues: response.data.validation?.rejectedValues || [],
          sanitizedCount: response.data.validation?.sanitizedCount || 0,
          originalCount: response.data.validation?.originalCount || 0,
          validatedCount: response.data.validation?.validatedCount || 0,
        };
        
        // Кэшируем результат валидации на 1 минуту
        this.validationCache.set(validationKey, validationResult);
        setTimeout(() => this.validationCache.delete(validationKey), 60000);
        
        if (validationResult.rejectedValues.length > 0 && process.env.NODE_ENV === 'development') {
          console.warn('Отклоненные значения фильтров:', validationResult.rejectedValues);
        }
      }
    } catch (error) {
      if (process.env.NODE_ENV === 'development') {
        console.warn('Ошибка валидации фильтров:', error);
      }
      // Продолжаем с исходными фильтрами если валидация не удалась
    }
  }

  /**
   * Выполняет API запрос
   */
  private async makeApiRequest(
    filters: CreativesFilters,
    cancelSource: CancelTokenSource
  ): Promise<CreativesResponse> {
    const response: AxiosResponse = await axios.get('/api/creatives', {
      params: filters,
      cancelToken: cancelSource.token,
      timeout: 10000,
    });

    if (response.status !== 200) {
      throw new Error(`API вернул статус ${response.status}`);
    }

    const apiData = response.data;
    
    if (!apiData || apiData.status !== 'success' || !apiData.data) {
      throw new Error('Неверная структура ответа от API');
    }

    const { items, pagination, meta } = apiData.data;
    
    if (!Array.isArray(items) || !pagination || !meta) {
      throw new Error('Отсутствуют обязательные поля в ответе API');
    }

    return apiData;
  }

  /**
   * Постобработка данных
   */
  private postprocessData(response: CreativesResponse, filters: CreativesFilters): ProcessedCreativesData {
    const processedItems = response.data.items.map(this.enrichCreative);
    
    return {
      items: processedItems,
      pagination: response.data.pagination,
      meta: {
        hasSearch: response.data.meta.hasSearch,
        activeFiltersCount: response.data.meta.activeFiltersCount,
        cacheKey: response.data.meta.cacheKey || this.generateRequestKey(filters),
      }
    };
  }

  /**
   * Обогащает креатив дополнительными свойствами
   */
  private enrichCreative = (creative: Creative): Creative => ({
    ...creative,
    displayName: this.generateDisplayName(creative),
    isRecent: this.isRecentCreative(creative),
    isFavorite: false, // TODO: реализовать логику избранного
  });

  /**
   * Предварительная обработка фильтров
   */
  private preprocessFilters(filters: CreativesFilters): CreativesFilters {
    const processed: CreativesFilters = {};

    // Обрабатываем каждое поле
    Object.entries(filters).forEach(([key, value]) => {
      if (this.isValidFilterValue(value)) {
        if (Array.isArray(value)) {
          const cleanArray = value.filter(item => item !== '' && item !== null);
          if (cleanArray.length > 0) {
            (processed as any)[key] = cleanArray;
          }
        } else {
          (processed as any)[key] = value;
        }
      }
    });

    // Дефолтные значения
    const defaults: Partial<CreativesFilters> = {
      page: 1,
      perPage: CREATIVES_CONSTANTS.DEFAULT_PAGE_SIZE,
      sortBy: 'creation' as const
    };
    
    // Объединяем с приоритетом для processed значений
    return { ...defaults, ...processed } as CreativesFilters;
  }

  /**
   * Проверяет валидность значения фильтра
   */
  private isValidFilterValue(value: any): boolean {
    return value !== '' && value !== null && value !== undefined;
  }

  /**
   * Генерирует ключ запроса для кэширования
   */
  private generateRequestKey(filters: CreativesFilters): string {
    const normalized = this.normalizeFilters(filters);
    const str = JSON.stringify(normalized, Object.keys(normalized).sort());
    return this.createHash(str).substring(0, this.config.maxCacheKeyLength);
  }

  /**
   * Генерирует ключ для кэша валидации
   */
  private generateValidationKey(filters: CreativesFilters): string {
    return 'validation_' + this.generateRequestKey(filters);
  }

  /**
   * Нормализует фильтры для консистентного хэширования
   */
  private normalizeFilters(filters: CreativesFilters): CreativesFilters {
    const normalized: CreativesFilters = {};
    
    Object.entries(filters).forEach(([key, value]) => {
      if (Array.isArray(value)) {
        // Сортируем массивы для консистентности
        normalized[key as keyof CreativesFilters] = [...value].sort() as any;
      } else {
        normalized[key as keyof CreativesFilters] = value;
      }
    });
    
    return normalized;
  }

  /**
   * Создает хэш строки
   */
  private createHash(str: string): string {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
      const char = str.charCodeAt(i);
      hash = ((hash << 5) - hash) + char;
      hash = hash & hash; // 32-битное число
    }
    return Math.abs(hash).toString(36);
  }

  /**
   * Планирует очистку кэша
   */
  private scheduleCleanup(requestKey: string, filters: CreativesFilters): void {
    const hasSearch = Boolean(filters.searchKeyword);
    const ttl = hasSearch ? this.config.searchCacheTtl : this.config.defaultCacheTtl;
    
    setTimeout(() => {
      this.requestCache.delete(requestKey);
    }, ttl);
  }

  /**
   * Отменяет запрос
   */
  private cancelRequest(requestKey: string): void {
    const cancelSource = this.loadingStates.get(requestKey);
    if (cancelSource) {
      cancelSource.cancel('Новый запрос отменил предыдущий');
      this.loadingStates.delete(requestKey);
    }
  }

  /**
   * Задержка
   */
  private delay(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms));
  }

  /**
   * Генерирует отображаемое имя креатива
   */
  private generateDisplayName(creative: Creative): string {
    if (creative.name) return creative.name;
    
    const parts = [creative.category, creative.country].filter(Boolean);
    return parts.join(' - ') || `Creative #${creative.id}`;
  }

  /**
   * Проверяет является ли креатив недавним
   */
  private isRecentCreative(creative: Creative): boolean {
    const createdDate = new Date(creative.created_at);
    const weekAgo = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);
    return createdDate > weekAgo;
  }

  // Публичные методы

  /**
   * Проверяет состояние загрузки
   */
  isLoading(filters?: CreativesFilters): boolean {
    if (!filters) {
      return this.loadingStates.size > 0;
    }
    
    const requestKey = this.generateRequestKey(filters);
    return this.loadingStates.has(requestKey);
  }

  /**
   * Отменяет все активные запросы
   */
  cancelAllRequests(): void {
    this.loadingStates.forEach(cancelSource => {
      cancelSource.cancel('Все запросы отменены');
    });
    this.loadingStates.clear();
    this.requestCache.clear();
  }

  /**
   * Очищает все кэши
   */
  clearCache(): void {
    this.requestCache.clear();
    this.validationCache.clear();
  }

  /**
   * Получает конфигурацию
   */
  getConfig(): CreativesServiceConfig {
    return { ...this.config };
  }

  /**
   * Обновляет конфигурацию
   */
  updateConfig(config: Partial<CreativesServiceConfig>): void {
    this.config = { ...this.config, ...config };
  }

  /**
   * Получает статистику кэша
   */
  getCacheStats() {
    return {
      requestCacheSize: this.requestCache.size,
      validationCacheSize: this.validationCache.size,
      activeRequests: this.loadingStates.size,
    };
  }
}

/**
 * Ошибка валидации
 */
class ValidationError extends Error {
  constructor(message: string, public errors: Record<string, string>) {
    super(message);
    this.name = 'ValidationError';
  }
}

// Экспорт
export { ValidationError };
export const creativesService = new CreativesService();
export default CreativesService;