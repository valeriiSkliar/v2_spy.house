import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { creativesStore } from '../../../resources/js/creatives/store/creativesStore.js';

// Мокаем fetch
global.fetch = vi.fn();

// Мокаем window объекты
Object.defineProperty(window, 'location', {
  value: {
    pathname: '/creatives',
    search: '',
  },
  writable: true,
});

Object.defineProperty(window, 'history', {
  value: {
    pushState: vi.fn(),
  },
  writable: true,
});

describe('CreativesStore', () => {
  let store;

  beforeEach(() => {
    // Создаем свежую копию store для каждого теста
    store = Object.create(creativesStore);
    
    // Копируем начальные значения
    Object.assign(store, {
      loading: false,
      error: null,
      currentTab: 'push',
      availableTabs: ['push', 'inpage', 'facebook', 'tiktok'],
      creatives: [],
      totalPages: 1,
      currentPage: 1,
      perPage: 12,
      totalCount: 0,
      tabCounts: {},
      filters: {
        search: '',
        category: '',
        dateFrom: '',
        dateTo: '',
        sortBy: 'created_at',
        sortOrder: 'desc',
      },
      searchQuery: '',
      selectedCountry: '',
      selectedCreative: null,
      detailsPanelOpen: false,
      cache: new Map(),
    });

    // Очищаем моки
    vi.clearAllMocks();
    fetch.mockClear();

    // Сбрасываем window объекты
    window.location.search = '';
    window.creativesTabCounts = undefined;

    // Мокаем CSRF token
    document.head.innerHTML = '<meta name="csrf-token" content="test-token">';
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('Инициализация', () => {
    it('должен иметь правильные начальные значения', () => {
      expect(store.loading).toBe(false);
      expect(store.error).toBe(null);
      expect(store.currentTab).toBe('push');
      expect(store.perPage).toBe(12);
      expect(store.currentPage).toBe(1);
      expect(store.creatives).toEqual([]);
    });

    it('должен загрузить фильтры из URL при инициализации', () => {
      window.location.search = '?tab=facebook&page=2&perPage=24&search=test';

      store.loadFiltersFromUrl();

      expect(store.currentTab).toBe('facebook');
      expect(store.currentPage).toBe(2);
      expect(store.perPage).toBe(24);
      expect(store.filters.search).toBe('test');
      expect(store.searchQuery).toBe('test');
    });

    it('должен обрабатывать некорректные URL параметры', () => {
      window.location.search = '?tab=invalid&page=abc&perPage=-5';

      store.loadFiltersFromUrl();

      expect(store.currentTab).toBe('push'); // остается по умолчанию
      expect(store.currentPage).toBe(1); // остается по умолчанию
      expect(store.perPage).toBe(12); // остается по умолчанию
    });
  });

  describe('Управление состоянием', () => {
    it('должен устанавливать состояние загрузки', () => {
      store.setLoading(true);
      expect(store.loading).toBe(true);

      store.setLoading(false);
      expect(store.loading).toBe(false);
    });

    it('должен устанавливать и очищать ошибки', () => {
      const error = 'Тестовая ошибка';

      store.setError(error);
      expect(store.error).toBe(error);

      store.clearError();
      expect(store.error).toBe(null);
    });

    it('должен устанавливать данные креативов', () => {
      const testData = {
        data: [{ id: 1 }, { id: 2 }],
        last_page: 5,
        current_page: 2,
        total: 100,
        per_page: 20,
        tab_counts: { push: 50, inpage: 30 },
      };

      store.setCreatives(testData);

      expect(store.creatives).toEqual(testData.data);
      expect(store.totalPages).toBe(5);
      expect(store.currentPage).toBe(2);
      expect(store.totalCount).toBe(100);
      expect(store.perPage).toBe(20);
      expect(store.tabCounts).toEqual(testData.tab_counts);
    });
  });

  describe('Управление вкладками', () => {
    it('должен переключать вкладки', async () => {
      // Мокаем loadCreatives
      const loadCreativesSpy = vi.spyOn(store, 'loadCreatives').mockImplementation(() => {});

      store.setTab('facebook');

      expect(store.currentTab).toBe('facebook');
      expect(store.currentPage).toBe(1); // должен сбросить пагинацию
      expect(loadCreativesSpy).toHaveBeenCalled();
    });

    it('должен игнорировать некорректные вкладки', () => {
      const originalTab = store.currentTab;

      store.setTab('invalid-tab');

      expect(store.currentTab).toBe(originalTab);
    });
  });

  describe('Управление perPage', () => {
    it('должен обновлять perPage и сбрасывать пагинацию', async () => {
      const loadCreativesSpy = vi.spyOn(store, 'loadCreatives').mockImplementation(() => {});
      const updateUrlSpy = vi.spyOn(store, 'updateUrl').mockImplementation(() => {});

      store.currentPage = 3;
      store.setPerPage('24');

      expect(store.perPage).toBe(24);
      expect(store.currentPage).toBe(1);
      expect(updateUrlSpy).toHaveBeenCalled();
      expect(loadCreativesSpy).toHaveBeenCalled();
    });

    it('должен обрабатывать некорректные значения perPage', () => {
      const originalPerPage = store.perPage;

      store.setPerPage('abc');
      expect(store.perPage).toBe(originalPerPage);

      store.setPerPage('-5');
      expect(store.perPage).toBe(originalPerPage);

      store.setPerPage('0');
      expect(store.perPage).toBe(originalPerPage);
    });

    it('должен конвертировать строковые числа в числа', async () => {
      vi.spyOn(store, 'loadCreatives').mockImplementation(() => {});
      vi.spyOn(store, 'updateUrl').mockImplementation(() => {});

      store.setPerPage('48');

      expect(store.perPage).toBe(48);
      expect(typeof store.perPage).toBe('number');
    });
  });

  describe('Управление фильтрами', () => {
    it('должен обновлять фильтры', () => {
      const updateUrlSpy = vi.spyOn(store, 'updateUrl').mockImplementation(() => {});

      const newFilters = {
        search: 'test query',
        category: 'test category',
      };

      store.updateFilters(newFilters);

      expect(store.filters.search).toBe('test query');
      expect(store.filters.category).toBe('test category');
      expect(store.currentPage).toBe(1); // должен сбросить пагинацию
      expect(updateUrlSpy).toHaveBeenCalled();
    });

    it('должен обновлять поисковый запрос', async () => {
      const loadCreativesSpy = vi.spyOn(store, 'loadCreatives').mockImplementation(() => {});
      const updateUrlSpy = vi.spyOn(store, 'updateUrl').mockImplementation(() => {});

      store.updateSearchQuery('новый поиск');

      expect(store.searchQuery).toBe('новый поиск');
      expect(store.filters.search).toBe('новый поиск');
      expect(store.currentPage).toBe(1);
      expect(updateUrlSpy).toHaveBeenCalled();
      expect(loadCreativesSpy).toHaveBeenCalled();
    });

    it('должен обновлять выбранную страну', async () => {
      const loadCreativesSpy = vi.spyOn(store, 'loadCreatives').mockImplementation(() => {});
      const updateUrlSpy = vi.spyOn(store, 'updateUrl').mockImplementation(() => {});

      store.updateSelectedCountry('RU');

      expect(store.selectedCountry).toBe('RU');
      expect(store.filters.category).toBe('RU');
      expect(store.currentPage).toBe(1);
      expect(updateUrlSpy).toHaveBeenCalled();
      expect(loadCreativesSpy).toHaveBeenCalled();
    });
  });

  describe('Управление пагинацией', () => {
    beforeEach(() => {
      store.totalPages = 10;
    });

    it('должен устанавливать корректную страницу', async () => {
      const loadCreativesSpy = vi.spyOn(store, 'loadCreatives').mockImplementation(() => {});
      const updateUrlSpy = vi.spyOn(store, 'updateUrl').mockImplementation(() => {});

      store.setPage(5);

      expect(store.currentPage).toBe(5);
      expect(updateUrlSpy).toHaveBeenCalled();
      expect(loadCreativesSpy).toHaveBeenCalled();
    });

    it('должен игнорировать некорректные номера страниц', () => {
      store.setPage(0);
      expect(store.currentPage).toBe(1); // не должно измениться

      store.setPage(15);
      expect(store.currentPage).toBe(1); // не должно измениться

      store.setPage(-1);
      expect(store.currentPage).toBe(1); // не должно измениться
    });

    it('должен сбрасывать пагинацию', () => {
      store.currentPage = 5;
      store.resetPagination();
      expect(store.currentPage).toBe(1);
    });
  });

  describe('Управление деталями', () => {
    it('должен открывать и закрывать панель деталей', () => {
      const creative = { id: 1, title: 'Test Creative' };

      store.openDetails(creative);
      expect(store.selectedCreative).toEqual(creative);
      expect(store.detailsPanelOpen).toBe(true);

      store.closeDetails();
      expect(store.selectedCreative).toBe(null);
      expect(store.detailsPanelOpen).toBe(false);
    });
  });

  describe('Кэширование', () => {
    it('должен генерировать правильный ключ кэша', () => {
      store.currentTab = 'facebook';
      store.currentPage = 2;
      store.perPage = 24;
      store.filters = { search: 'test' };

      const key = store.getCacheKey();
      const expectedKey = JSON.stringify({
        tab: 'facebook',
        page: 2,
        perPage: 24,
        search: 'test',
      });

      expect(key).toBe(expectedKey);
    });

    it('должен управлять кэшем', () => {
      const key = 'test-key';
      const data = { test: 'data' };

      store.setCache(key, data);
      expect(store.getFromCache(key)).toEqual(data);

      expect(store.getFromCache('non-existent')).toBeUndefined();
    });

    it('должен ограничивать размер кэша', () => {
      // Заполняем кэш более чем 50 элементами
      for (let i = 0; i < 55; i++) {
        store.setCache(`key-${i}`, { data: i });
      }

      expect(store.cache.size).toBeLessThanOrEqual(50);
      expect(store.getFromCache('key-0')).toBeUndefined(); // первые элементы должны быть удалены
      expect(store.getFromCache('key-54')).toBeDefined(); // последние должны остаться
    });
  });

  describe('Загрузка креативов', () => {
    it('должен загружать креативы через API', async () => {
      const mockResponse = {
        data: [{ id: 1 }, { id: 2 }],
        last_page: 3,
        current_page: 1,
        total: 50,
        per_page: 12,
      };

      fetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockResponse),
      });

      await store.loadCreatives();

      expect(fetch).toHaveBeenCalledWith(
        expect.stringContaining('/api/creatives'),
        expect.objectContaining({
          method: 'GET',
          headers: expect.objectContaining({
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': 'test-token',
          }),
        })
      );

      expect(store.creatives).toEqual(mockResponse.data);
      expect(store.loading).toBe(false);
    });

    it('должен использовать кэш если данные уже загружены', async () => {
      const cachedData = { data: [{ id: 1 }] };
      const cacheKey = store.getCacheKey();
      store.setCache(cacheKey, cachedData);

      await store.loadCreatives();

      expect(fetch).not.toHaveBeenCalled();
      expect(store.creatives).toEqual(cachedData.data);
    });

    it('должен обрабатывать ошибки API', async () => {
      fetch.mockRejectedValueOnce(new Error('Network error'));

      await store.loadCreatives();

      expect(store.error).toContain('Ошибка загрузки креативов');
      expect(store.loading).toBe(false);
    });

    it('должен обрабатывать HTTP ошибки', async () => {
      fetch.mockResolvedValueOnce({
        ok: false,
        status: 500,
      });

      await store.loadCreatives();

      expect(store.error).toContain('Ошибка загрузки креативов');
      expect(store.loading).toBe(false);
    });

    it('должен корректно формировать URL параметры', async () => {
      store.currentTab = 'facebook';
      store.currentPage = 2;
      store.perPage = 24;
      store.filters = {
        search: 'test query',
        category: 'RU',
        sortBy: 'updated_at',
      };

      fetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve({ data: [] }),
      });

      await store.loadCreatives();

      const calledUrl = fetch.mock.calls[0][0];
      expect(calledUrl).toContain('tab=facebook');
      expect(calledUrl).toContain('page=2');
      expect(calledUrl).toContain('per_page=24');
      expect(calledUrl).toContain('search=test+query');
      expect(calledUrl).toContain('category=RU');
      expect(calledUrl).toContain('sortBy=updated_at');
    });
  });

  describe('Загрузка счетчиков вкладок', () => {
    it('должен загружать счетчики через API', async () => {
      const mockCounts = { push: 100, inpage: 50, facebook: 25 };

      fetch.mockResolvedValueOnce({
        ok: true,
        json: () => Promise.resolve(mockCounts),
      });

      await store.loadTabCounts();

      expect(fetch).toHaveBeenCalledWith('/api/creatives/tab-counts', expect.any(Object));
      expect(store.tabCounts).toEqual(mockCounts);
    });

    it('должен использовать fallback из window при ошибке API', async () => {
      window.creativesTabCounts = { push: 80, inpage: 40 };
      fetch.mockRejectedValueOnce(new Error('Network error'));

      await store.loadTabCounts();

      expect(store.tabCounts).toEqual(window.creativesTabCounts);
    });
  });

  describe('Централизованная обработка изменений', () => {
    it('должен обрабатывать изменение perPage', async () => {
      const setPerPageSpy = vi.spyOn(store, 'setPerPage').mockImplementation(() => {});

      store.handleFieldChange('perPage', '24');

      expect(setPerPageSpy).toHaveBeenCalledWith('24');
    });

    it('должен обрабатывать изменение searchQuery', async () => {
      const updateSearchQuerySpy = vi
        .spyOn(store, 'updateSearchQuery')
        .mockImplementation(() => {});

      store.handleFieldChange('searchQuery', 'test');

      expect(updateSearchQuerySpy).toHaveBeenCalledWith('test');
    });

    it('должен обрабатывать изменение selectedCountry', async () => {
      const updateSelectedCountrySpy = vi
        .spyOn(store, 'updateSelectedCountry')
        .mockImplementation(() => {});

      store.handleFieldChange('selectedCountry', 'RU');

      expect(updateSelectedCountrySpy).toHaveBeenCalledWith('RU');
    });

    it('должен обрабатывать неизвестные поля', async () => {
      const resetPaginationSpy = vi.spyOn(store, 'resetPagination').mockImplementation(() => {});
      const updateUrlSpy = vi.spyOn(store, 'updateUrl').mockImplementation(() => {});
      const loadCreativesSpy = vi.spyOn(store, 'loadCreatives').mockImplementation(() => {});

      store.handleFieldChange('unknownField', 'value');

      expect(resetPaginationSpy).toHaveBeenCalled();
      expect(updateUrlSpy).toHaveBeenCalled();
      expect(loadCreativesSpy).toHaveBeenCalled();
    });
  });

  describe('Обновление URL', () => {
    it('должен корректно обновлять URL', () => {
      store.currentTab = 'facebook';
      store.currentPage = 3;
      store.perPage = 24;
      store.filters = {
        search: 'test',
        category: 'RU',
        sortBy: 'created_at',
      };

      store.updateUrl();

      expect(window.history.pushState).toHaveBeenCalledWith(
        {},
        '',
        expect.stringContaining('tab=facebook')
      );
      expect(window.history.pushState).toHaveBeenCalledWith(
        {},
        '',
        expect.stringContaining('page=3')
      );
      expect(window.history.pushState).toHaveBeenCalledWith(
        {},
        '',
        expect.stringContaining('perPage=24')
      );
    });

    it('должен пропускать пустые значения фильтров', () => {
      store.filters = {
        search: '',
        category: 'RU',
        dateFrom: null,
        dateTo: undefined,
      };

      store.updateUrl();

      const calledUrl = window.history.pushState.mock.calls[0][2];
      expect(calledUrl).toContain('category=RU');
      expect(calledUrl).not.toContain('search=');
      expect(calledUrl).not.toContain('dateFrom=');
      expect(calledUrl).not.toContain('dateTo=');
    });
  });
});
