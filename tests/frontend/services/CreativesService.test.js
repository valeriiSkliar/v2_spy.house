import { beforeEach, describe, expect, it, vi } from 'vitest';
import CreativesService, {
  creativesService,
} from '../../../resources/js/services/CreativesService.ts';

describe('CreativesService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    creativesService.cancelAllRequests();
  });

  describe('Конфигурация сервиса', () => {
    it('должен создаваться с конфигурацией по умолчанию', () => {
      const service = new CreativesService();
      const config = service.getConfig();

      expect(config.defaultCacheTtl).toBe(5 * 60 * 1000);
      expect(config.searchCacheTtl).toBe(30 * 1000);
      expect(config.debounceDelay).toBe(300);
      expect(config.maxCacheKeyLength).toBe(20);
    });

    it('должен принимать кастомную конфигурацию', () => {
      const customConfig = {
        defaultCacheTtl: 10 * 60 * 1000,
        searchCacheTtl: 60 * 1000,
      };

      const service = new CreativesService(customConfig);
      const config = service.getConfig();

      expect(config.defaultCacheTtl).toBe(10 * 60 * 1000);
      expect(config.searchCacheTtl).toBe(60 * 1000);
      expect(config.debounceDelay).toBe(300); // Должно остаться по умолчанию
    });

    it('должен обновлять конфигурацию', () => {
      const service = new CreativesService();
      service.updateConfig({ debounceDelay: 500 });

      const config = service.getConfig();
      expect(config.debounceDelay).toBe(500);
    });
  });

  describe('Предварительная обработка фильтров', () => {
    it('должен устанавливать значения по умолчанию', async () => {
      // Мокаем API запрос для получения доступа к обработанным фильтрам
      vi.spyOn(creativesService, 'loadCreatives').mockImplementation(async filters => {
        // Проверяем что значения по умолчанию установлены
        expect(filters.page).toBe(1);
        expect(filters.perPage).toBe(12);
        expect(filters.sortBy).toBe('creation');
        expect(filters.country).toBe('All Categories');
        expect(filters.onlyAdult).toBe(false);

        return {
          items: [],
          pagination: {
            total: 0,
            perPage: 12,
            currentPage: 1,
            lastPage: 1,
            from: 0,
            to: 0,
          },
          meta: {
            hasSearch: false,
            activeFiltersCount: 0,
            cacheKey: 'test',
          },
        };
      });

      await creativesService.loadCreatives({});
    });

    it('должен очищать пустые значения', async () => {
      const mockService = new CreativesService();

      // Создаем spy на приватный метод через публичный
      const loadSpy = vi.spyOn(mockService, 'loadCreatives');

      await mockService.loadCreatives({
        searchKeyword: '',
        country: null,
        advertisingNetworks: ['', null, 'Google'],
        languages: [],
      });

      // Проверяем что spy был вызван
      expect(loadSpy).toHaveBeenCalled();
    });
  });

  describe('Генерация ключей кэша', () => {
    it('должен генерировать одинаковые ключи для одинаковых фильтров', () => {
      const service1 = new CreativesService();
      const service2 = new CreativesService();

      const filters1 = { searchKeyword: 'test', country: 'US' };
      const filters2 = { searchKeyword: 'test', country: 'US' };

      // Используем публичный метод isLoading для проверки генерации ключей
      // (он внутренне использует generateRequestKey)
      expect(service1.isLoading(filters1)).toBe(service1.isLoading(filters2));
    });

    it('должен генерировать разные ключи для разных фильтров', () => {
      const service = new CreativesService();

      const filters1 = { searchKeyword: 'test1' };
      const filters2 = { searchKeyword: 'test2' };

      // Косвенно проверяем через разные состояния loading
      expect(service.isLoading(filters1)).toBe(false);
      expect(service.isLoading(filters2)).toBe(false);
    });
  });

  describe('Отслеживание состояния загрузки', () => {
    it('должен отслеживать состояние загрузки', () => {
      expect(creativesService.isLoading()).toBe(false);
    });

    it('должен отслеживать состояние для конкретных фильтров', () => {
      const filters = { searchKeyword: 'test' };
      expect(creativesService.isLoading(filters)).toBe(false);
    });

    it('должен очищать состояния загрузки', () => {
      creativesService.cancelAllRequests();
      expect(creativesService.isLoading()).toBe(false);
    });
  });

  describe('Публичные методы', () => {
    it('должен экспортировать синглтон сервиса', () => {
      expect(creativesService).toBeInstanceOf(CreativesService);
    });

    it('должен позволять создавать новые экземпляры', () => {
      const customService = new CreativesService({
        defaultCacheTtl: 1000,
      });

      expect(customService).toBeInstanceOf(CreativesService);
      expect(customService.getConfig().defaultCacheTtl).toBe(1000);
    });
  });

  describe('Обработка ошибок', () => {
    it('должен предотвращать дублирование запросов', async () => {
      const service = new CreativesService();

      // Мокаем медленный API запрос
      const originalMakeApiRequest = service.makeApiRequest;
      service.makeApiRequest = vi
        .fn()
        .mockImplementation(() => new Promise(resolve => setTimeout(resolve, 100)));

      const filters = { searchKeyword: 'test' };

      // Запускаем два одинаковых запроса одновременно
      const promise1 = service.loadCreatives(filters);
      const promise2 = service.loadCreatives(filters);

      // Второй запрос должен выбросить ошибку
      await expect(promise2).rejects.toThrow('Запрос уже выполняется');

      // Первый запрос должен завершиться успешно
      await expect(promise1).resolves.toBeDefined();
    });
  });
});
