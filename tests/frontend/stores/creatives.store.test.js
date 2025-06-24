import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useFiltersStore } from '../../../resources/js/stores/creatives.ts';

// Мокаем CreativesService
vi.mock('../../../resources/js/services/CreativesService.ts', () => ({
  creativesService: {
    loadCreatives: vi.fn(),
    isLoading: vi.fn(filters => {
      // Если filters не передан, проверяем общее состояние загрузки
      // Если передан, проверяем конкретный запрос
      // По умолчанию возвращаем false для тестов
      return false;
    }),
    cancelAllRequests: vi.fn(),
    getConfig: vi.fn(() => ({
      defaultCacheTtl: 5 * 60 * 1000,
      searchCacheTtl: 30 * 1000,
    })),
  },
}));

// Мокаем useCreativesUrlSync
vi.mock('../../../resources/js/composables/useCreativesUrlSync.ts', () => ({
  useCreativesUrlSync: vi.fn(() => ({
    state: { value: {} },
    urlParams: {},
    syncWithFilterState: vi.fn(),
    getFilterStateUpdates: vi.fn(() => ({})),
    getActiveTabFromUrl: vi.fn(() => 'push'),
  })),
}));

describe('Creatives Store Integration', () => {
  let store;

  beforeEach(async () => {
    // Создаем новый Pinia instance для каждого теста
    setActivePinia(createPinia());
    store = useFiltersStore();
    vi.clearAllMocks();

    // Сбрасываем мок creativesService.isLoading к изначальному состоянию
    const { creativesService } = await import('../../../resources/js/services/CreativesService.ts');
    creativesService.isLoading.mockReturnValue(false);

    // Сбрасываем состояние загрузки
    store.creativesLoading = false;
    store.clearCreatives();
  });

  describe('Базовая интеграция Store', () => {
    it('должен инициализироваться с пустыми данными креативов', () => {
      expect(store.creativesData).toBeNull();
      expect(store.creativesLoading).toBe(false);
      expect(store.creativesError).toBeNull();
      expect(store.creatives).toEqual([]);
      expect(store.hasCreatives).toBe(false);
    });

    it('должен иметь корректную структуру пагинации по умолчанию', () => {
      expect(store.pagination).toEqual({
        total: 0,
        perPage: 12,
        currentPage: 1,
        lastPage: 1,
        from: 0,
        to: 0,
      });
    });

    it('должен иметь корректные метаданные по умолчанию', () => {
      expect(store.requestMeta).toEqual({
        hasSearch: false,
        activeFiltersCount: 0,
        cacheKey: '',
      });
    });
  });

  describe('Преобразование фильтров', () => {
    it('должен корректно преобразовывать FilterState в CreativesFilters', () => {
      // Устанавливаем некоторые фильтры
      store.setSearchKeyword('test banner');
      store.setCountry('US');
      store.setSortBy('activity');
      store.addToMultiSelect('advertisingNetworks', 'Google');
      store.addToMultiSelect('languages', 'en');
      store.toggleAdultFilter();

      const creativesFilters = store.mapFiltersToCreativesFilters();

      expect(creativesFilters).toEqual({
        searchKeyword: 'test banner',
        country: 'US',
        dateCreation: undefined,
        sortBy: 'activity',
        periodDisplay: undefined,
        advertisingNetworks: ['Google'],
        languages: ['en'],
        operatingSystems: undefined,
        browsers: undefined,
        devices: undefined,
        imageSizes: undefined,
        onlyAdult: true,
        page: 1,
        perPage: 12,
      });
    });

    it('должен исключать дефолтные значения', () => {
      const creativesFilters = store.mapFiltersToCreativesFilters();

      expect(creativesFilters.country).toBeUndefined();
      expect(creativesFilters.dateCreation).toBeUndefined();
      expect(creativesFilters.sortBy).toBe('creation');
      expect(creativesFilters.onlyAdult).toBe(false);
    });

    it('должен исключать пустые массивы', () => {
      const creativesFilters = store.mapFiltersToCreativesFilters();

      expect(creativesFilters.advertisingNetworks).toBeUndefined();
      expect(creativesFilters.languages).toBeUndefined();
      expect(creativesFilters.operatingSystems).toBeUndefined();
    });
  });

  describe('Загрузка креативов', () => {
    it('должен устанавливать состояние загрузки при loadCreatives', async () => {
      const mockData = {
        items: [
          {
            id: 1,
            name: 'Test Creative',
            category: 'banner',
            country: 'US',
            file_url: 'test.jpg',
            created_at: '2024-01-01',
          },
        ],
        pagination: {
          total: 1,
          perPage: 12,
          currentPage: 1,
          lastPage: 1,
          from: 1,
          to: 1,
        },
        meta: {
          hasSearch: false,
          activeFiltersCount: 0,
          cacheKey: 'test-key',
        },
      };

      // Мокаем успешный ответ
      const { creativesService } = await import(
        '../../../resources/js/services/CreativesService.ts'
      );
      creativesService.loadCreatives.mockResolvedValue(mockData);

      // Запускаем загрузку
      const loadPromise = store.loadCreatives();

      // Проверяем состояние загрузки
      expect(store.creativesLoading).toBe(true);

      // Ждем завершения
      await loadPromise;

      // Проверяем результат
      expect(store.creativesLoading).toBe(false);
      expect(store.creativesData).toEqual(mockData);
      expect(store.creatives).toEqual(mockData.items);
      expect(store.hasCreatives).toBe(true);
      expect(store.creativesError).toBeNull();
    });

    it('должен обрабатывать ошибки загрузки', async () => {
      const errorMessage = 'Network error';

      // Мокаем ошибку
      const { creativesService } = await import(
        '../../../resources/js/services/CreativesService.ts'
      );
      creativesService.loadCreatives.mockRejectedValue(new Error(errorMessage));

      // Запускаем загрузку
      await store.loadCreatives();

      // Проверяем обработку ошибки
      expect(store.creativesLoading).toBe(false);
      expect(store.creativesError).toBe(errorMessage);
      expect(store.creativesData).toBeNull();
      expect(store.hasError).toBe(true);
    });

    it('должен предотвращать дублированные запросы', async () => {
      const { creativesService } = await import(
        '../../../resources/js/services/CreativesService.ts'
      );

      // Сначала сбрасываем моки
      creativesService.isLoading.mockReturnValue(false);
      creativesService.loadCreatives.mockResolvedValue({
        items: [],
        pagination: {},
        meta: {},
      });

      // Устанавливаем одинаковые фильтры
      store.setSearchKeyword('test');

      // Первый запрос
      const promise1 = store.loadCreatives();

      // Эмулируем что сервис сообщает о выполнении запроса
      creativesService.isLoading.mockReturnValue(true);

      // Второй запрос с теми же фильтрами должен быть пропущен
      await store.loadCreatives();

      // Завершаем первый запрос
      await promise1;

      // Проверяем что сервис вызывался только один раз (второй был пропущен)
      expect(creativesService.loadCreatives).toHaveBeenCalledTimes(1);
    });
  });

  describe('Управление пагинацией', () => {
    beforeEach(() => {
      // Устанавливаем мок данные с пагинацией
      store.creativesData = {
        items: [],
        pagination: {
          total: 100,
          perPage: 12,
          currentPage: 2,
          lastPage: 9,
          from: 13,
          to: 24,
        },
        meta: {
          hasSearch: false,
          activeFiltersCount: 0,
          cacheKey: 'test',
        },
      };
    });

    it('должен загружать следующую страницу', async () => {
      const { creativesService } = await import(
        '../../../resources/js/services/CreativesService.ts'
      );
      creativesService.loadCreatives.mockResolvedValue({
        items: [],
        pagination: { currentPage: 3, lastPage: 9 },
        meta: {},
      });

      await store.loadNextPage();

      expect(creativesService.loadCreatives).toHaveBeenCalledWith(
        expect.objectContaining({ page: 3 })
      );
    });

    it('не должен загружать следующую страницу если это последняя', async () => {
      // Устанавливаем последнюю страницу
      store.creativesData.pagination.currentPage = 9;
      store.creativesData.pagination.lastPage = 9;

      const { creativesService } = await import(
        '../../../resources/js/services/CreativesService.ts'
      );

      await store.loadNextPage();

      expect(creativesService.loadCreatives).not.toHaveBeenCalled();
    });
  });

  describe('Computed свойства', () => {
    it('должен корректно определять состояние загрузки', () => {
      // Тестируем состояние Store (основная логика)
      expect(store.isLoading).toBe(false);

      // Когда creativesLoading = true, isLoading должен быть true
      store.creativesLoading = true;
      expect(store.isLoading).toBe(true);

      // Когда creativesLoading = false, isLoading зависит от creativesService.isLoading()
      store.creativesLoading = false;
      expect(store.isLoading).toBe(false); // По умолчанию мок возвращает false

      // Примечание: тестирование изменения creativesService.isLoading() во время выполнения
      // сложно из-за особенностей Vue reactive system с мокированными функциями.
      // Реальная интеграция с сервисом тестируется в интеграционных тестах.
    });

    it('должен интегрироваться с creativesService.isLoading при инициализации', async () => {
      // Этот тест проверяет, что computed свойство корректно вызывает creativesService.isLoading()
      const { creativesService } = await import(
        '../../../resources/js/services/CreativesService.ts'
      );

      // Проверяем, что функция вызывается при обращении к computed свойству
      const initialValue = store.isLoading;
      expect(creativesService.isLoading).toHaveBeenCalled();

      // Проверяем, что результат соответствует логике: creativesLoading || creativesService.isLoading()
      expect(initialValue).toBe(false); // store.creativesLoading = false, мок возвращает false
    });

    it('должен корректно определять наличие поиска', () => {
      store.creativesData = {
        items: [],
        pagination: {},
        meta: {
          hasSearch: true,
          activeFiltersCount: 2,
          cacheKey: 'test',
        },
      };

      expect(store.hasSearch).toBe(true);
      expect(store.activeFiltersCount).toBe(2);
    });
  });

  describe('Методы управления', () => {
    it('должен очищать данные креативов', () => {
      // Устанавливаем начальные данные
      store.creativesData = { items: [], pagination: {}, meta: {} };
      store.creativesError = 'Test error';
      store.creativesLoading = true;

      // Очищаем
      store.clearCreatives();

      // Проверяем результат
      expect(store.creativesData).toBeNull();
      expect(store.creativesError).toBeNull();
      expect(store.creativesLoading).toBe(false);
    });

    it('должен отменять активные запросы', async () => {
      const { creativesService } = await import(
        '../../../resources/js/services/CreativesService.ts'
      );

      store.cancelRequests();

      expect(creativesService.cancelAllRequests).toHaveBeenCalled();
      expect(store.creativesLoading).toBe(false);
    });

    it('должен обновлять креативы', async () => {
      const { creativesService } = await import(
        '../../../resources/js/services/CreativesService.ts'
      );
      creativesService.loadCreatives.mockResolvedValue({
        items: [],
        pagination: {},
        meta: {},
      });

      await store.refreshCreatives();

      expect(creativesService.loadCreatives).toHaveBeenCalledWith(
        expect.objectContaining({ page: 1 })
      );
    });
  });
});
