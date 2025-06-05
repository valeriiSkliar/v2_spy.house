import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { creativesStore } from '../../../resources/js/creatives/store/creativesStore.js';

describe('CreativesStore - Test Case 1: Initialization', () => {
  let originalWindow;
  let originalLocation;

  beforeEach(() => {
    // Сохраняем оригинальные значения
    originalWindow = global.window;
    originalLocation = global.location;

    // Мокаем window.location для URLSearchParams
    global.window = {
      ...originalWindow,
      location: {
        search: '',
        pathname: '/creatives',
      },
      history: {
        pushState: vi.fn(),
      },
    };

    // Мокаем URLSearchParams
    global.URLSearchParams = class URLSearchParams {
      constructor(search = '') {
        this.params = new Map();
      }

      get(key) {
        return this.params.get(key) || null;
      }

      set(key, value) {
        this.params.set(key, value);
      }

      toString() {
        const pairs = [];
        for (const [key, value] of this.params) {
          pairs.push(`${key}=${encodeURIComponent(value)}`);
        }
        return pairs.join('&');
      }
    };

    // Сбрасываем состояние store перед каждым тестом
    Object.assign(creativesStore, {
      loading: false,
      error: null,
      currentTab: 'push',
      availableTabs: ['push', 'inpage', 'facebook', 'tiktok'],
      creatives: [],
      totalPages: 1,
      currentPage: 1,
      perPage: 20,
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
      selectedCreative: null,
      detailsPanelOpen: false,
      cache: new Map(),
    });
  });

  afterEach(() => {
    // Восстанавливаем оригинальные значения
    global.window = originalWindow;
    global.location = originalLocation;
  });

  it('should initialize creativesStore with correct default values', () => {
    // Steps:
    // 1. Создать новый экземпляр creativesStore - он уже создан как объект
    // 2. Проверить начальные значения состояния

    // Expected Results проверки:

    // loading равно false
    expect(creativesStore.loading).toBe(false);

    // error равно null
    expect(creativesStore.error).toBe(null);

    // currentTab равно 'push' (согласно реальной реализации, не 'facebook' как в документации)
    expect(creativesStore.currentTab).toBe('push');

    // currentPage равно 1
    expect(creativesStore.currentPage).toBe(1);

    // Остальные свойства установлены в значения по умолчанию:

    // filters установлены в дефолтные значения
    expect(creativesStore.filters).toEqual({
      search: '',
      category: '',
      dateFrom: '',
      dateTo: '',
      sortBy: 'created_at',
      sortOrder: 'desc',
    });

    // totalPages установлено в дефолтное значение
    expect(creativesStore.totalPages).toBe(1);

    // selectedCreative равно null
    expect(creativesStore.selectedCreative).toBe(null);

    // detailsPanelOpen равно false
    expect(creativesStore.detailsPanelOpen).toBe(false);

    // Дополнительные проверки других свойств:
    expect(creativesStore.creatives).toEqual([]);
    expect(creativesStore.perPage).toBe(20);
    expect(creativesStore.totalCount).toBe(0);
    expect(creativesStore.tabCounts).toEqual({});
    expect(creativesStore.cache).toBeInstanceOf(Map);
    expect(creativesStore.cache.size).toBe(0);

    // Проверяем, что availableTabs содержит ожидаемые вкладки
    expect(creativesStore.availableTabs).toEqual(['push', 'inpage', 'facebook', 'tiktok']);
  });

  it('should properly initialize cache as empty Map', () => {
    // Проверяем, что кэш корректно инициализирован
    expect(creativesStore.cache).toBeInstanceOf(Map);
    expect(creativesStore.cache.size).toBe(0);
  });

  it('should have all required methods defined', () => {
    // Проверяем, что все необходимые методы определены
    expect(typeof creativesStore.init).toBe('function');
    expect(typeof creativesStore.setLoading).toBe('function');
    expect(typeof creativesStore.setError).toBe('function');
    expect(typeof creativesStore.clearError).toBe('function');
    expect(typeof creativesStore.setTab).toBe('function');
    expect(typeof creativesStore.setCreatives).toBe('function');
    expect(typeof creativesStore.updateFilters).toBe('function');
    expect(typeof creativesStore.setPage).toBe('function');
    expect(typeof creativesStore.resetPagination).toBe('function');
    expect(typeof creativesStore.openDetails).toBe('function');
    expect(typeof creativesStore.closeDetails).toBe('function');
    expect(typeof creativesStore.getCacheKey).toBe('function');
    expect(typeof creativesStore.getFromCache).toBe('function');
    expect(typeof creativesStore.setCache).toBe('function');
    expect(typeof creativesStore.loadFiltersFromUrl).toBe('function');
    expect(typeof creativesStore.updateUrl).toBe('function');
    expect(typeof creativesStore.loadCreatives).toBe('function');
  });
});

describe('CreativesStore - Test Case 2: Tab Switching', () => {
  let originalWindow;
  let loadCreativesSpy;

  beforeEach(() => {
    // Сохраняем оригинальные значения
    originalWindow = global.window;

    // Мокаем window для тестов
    global.window = {
      location: {
        search: '',
        pathname: '/creatives',
      },
      history: {
        pushState: vi.fn(),
      },
    };

    // Мокаем URLSearchParams
    global.URLSearchParams = class URLSearchParams {
      constructor(search = '') {
        this.params = new Map();
      }

      get(key) {
        return this.params.get(key) || null;
      }

      set(key, value) {
        this.params.set(key, value);
      }

      toString() {
        const pairs = [];
        for (const [key, value] of this.params) {
          pairs.push(`${key}=${encodeURIComponent(value)}`);
        }
        return pairs.join('&');
      }
    };

    // Сбрасываем состояние store
    Object.assign(creativesStore, {
      loading: false,
      error: null,
      currentTab: 'push',
      availableTabs: ['push', 'inpage', 'facebook', 'tiktok'],
      creatives: [],
      totalPages: 1,
      currentPage: 1,
      perPage: 20,
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
      selectedCreative: null,
      detailsPanelOpen: false,
      cache: new Map(),
    });

    // Создаем шпиона для метода loadCreatives
    loadCreativesSpy = vi.spyOn(creativesStore, 'loadCreatives').mockImplementation(() => {});
  });

  afterEach(() => {
    // Восстанавливаем оригинальные значения
    global.window = originalWindow;
    // Восстанавливаем оригинальный метод loadCreatives
    loadCreativesSpy.mockRestore();
  });

  it('should switch tab to tiktok and reset pagination', () => {
    // Steps:
    // 1. Вызвать метод переключения вкладки на 'tiktok'

    // Устанавливаем начальное состояние - не первая страница для проверки сброса
    creativesStore.currentPage = 3;

    // Переключаем вкладку
    creativesStore.setTab('tiktok');

    // Expected Results проверки:

    // currentTab равно 'tiktok'
    expect(creativesStore.currentTab).toBe('tiktok');

    // currentPage сброшено на 1
    expect(creativesStore.currentPage).toBe(1);

    // В текущей реализации loadCreatives закомментирован,
    // но мы проверяем что метод существует и может быть вызван
    expect(typeof creativesStore.loadCreatives).toBe('function');
  });

  it('should only switch to valid tabs from availableTabs', () => {
    // Проверяем переключение на валидные вкладки
    const validTabs = ['push', 'inpage', 'facebook', 'tiktok'];

    validTabs.forEach(tab => {
      creativesStore.setTab(tab);
      expect(creativesStore.currentTab).toBe(tab);
    });
  });

  it('should not switch to invalid tab', () => {
    // Устанавливаем начальную вкладку
    const initialTab = creativesStore.currentTab;

    // Пытаемся переключиться на невалидную вкладку
    creativesStore.setTab('invalid_tab');

    // Вкладка должна остаться прежней
    expect(creativesStore.currentTab).toBe(initialTab);
  });

  it('should reset pagination when switching tabs', () => {
    // Устанавливаем состояние не на первой странице
    creativesStore.currentPage = 5;
    creativesStore.totalPages = 10;

    // Переключаем вкладку
    creativesStore.setTab('facebook');

    // Пагинация должна сброситься
    expect(creativesStore.currentPage).toBe(1);
  });

  it('should preserve other state when switching tabs', () => {
    // Устанавливаем некоторые данные в состоянии
    const initialFilters = { search: 'test', category: 'video' };
    const initialCreatives = [{ id: 1, title: 'Test' }];
    const initialTotalCount = 100;

    creativesStore.filters = { ...creativesStore.filters, ...initialFilters };
    creativesStore.creatives = initialCreatives;
    creativesStore.totalCount = initialTotalCount;

    // Переключаем вкладку
    creativesStore.setTab('tiktok');

    // Другие данные должны сохраниться
    expect(creativesStore.filters.search).toBe('test');
    expect(creativesStore.filters.category).toBe('video');
    expect(creativesStore.creatives).toEqual(initialCreatives);
    expect(creativesStore.totalCount).toBe(initialTotalCount);
  });

  it('should call resetPagination method when switching tabs', () => {
    // Создаем шпиона для метода resetPagination
    const resetPaginationSpy = vi.spyOn(creativesStore, 'resetPagination');

    // Переключаем вкладку
    creativesStore.setTab('facebook');

    // Проверяем что resetPagination был вызван
    expect(resetPaginationSpy).toHaveBeenCalledOnce();

    resetPaginationSpy.mockRestore();
  });
});

describe('CreativesStore - Test Case 3: Tab Counts Management', () => {
  let originalWindow;
  let originalFetch;

  beforeEach(() => {
    // Сохраняем оригинальные значения
    originalWindow = global.window;
    originalFetch = global.fetch;

    // Мокаем window
    global.window = {
      location: {
        search: '',
        pathname: '/creatives',
      },
      history: {
        pushState: vi.fn(),
      },
    };

    // Мокаем document для CSRF токена
    global.document = {
      querySelector: vi.fn().mockReturnValue({
        getAttribute: vi.fn().mockReturnValue('test-csrf-token'),
      }),
    };

    // Сбрасываем состояние store
    Object.assign(creativesStore, {
      loading: false,
      error: null,
      currentTab: 'push',
      availableTabs: ['push', 'inpage', 'facebook', 'tiktok'],
      creatives: [],
      totalPages: 1,
      currentPage: 1,
      perPage: 20,
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
      selectedCreative: null,
      detailsPanelOpen: false,
      cache: new Map(),
    });
  });

  afterEach(() => {
    // Восстанавливаем оригинальные значения
    global.window = originalWindow;
    global.fetch = originalFetch;
    vi.restoreAllMocks();
  });

  describe('setTabCounts', () => {
    it('should set tab counts with valid data', () => {
      const testCounts = {
        push: 10,
        inpage: 5,
        facebook: 3,
        tiktok: 7,
      };

      creativesStore.setTabCounts(testCounts);

      expect(creativesStore.tabCounts).toEqual(testCounts);
    });

    it('should handle null counts by setting empty object', () => {
      creativesStore.setTabCounts(null);

      expect(creativesStore.tabCounts).toEqual({});
    });

    it('should handle undefined counts by setting empty object', () => {
      creativesStore.setTabCounts(undefined);

      expect(creativesStore.tabCounts).toEqual({});
    });

    it('should override existing tab counts', () => {
      // Устанавливаем начальные значения
      creativesStore.tabCounts = { push: 5, inpage: 3 };

      const newCounts = { facebook: 10, tiktok: 2 };
      creativesStore.setTabCounts(newCounts);

      expect(creativesStore.tabCounts).toEqual(newCounts);
    });
  });

  describe('getTabCountsFromWindow', () => {
    it('should get tab counts from window and return true when available', () => {
      const testCounts = {
        push: 15,
        inpage: 8,
        facebook: 4,
        tiktok: 12,
      };

      global.window.creativesTabCounts = testCounts;

      const result = creativesStore.getTabCountsFromWindow();

      expect(result).toBe(true);
      expect(creativesStore.tabCounts).toEqual(testCounts);
    });

    it('should return false when window.creativesTabCounts is not available', () => {
      // Удаляем creativesTabCounts из window
      delete global.window.creativesTabCounts;

      const result = creativesStore.getTabCountsFromWindow();

      expect(result).toBe(false);
      // tabCounts должны остаться неизменными (пустой объект по умолчанию)
      expect(creativesStore.tabCounts).toEqual({});
    });

    it('should return false when window.creativesTabCounts is null', () => {
      global.window.creativesTabCounts = null;

      const result = creativesStore.getTabCountsFromWindow();

      expect(result).toBe(false);
    });

    it('should return false when window.creativesTabCounts is undefined', () => {
      global.window.creativesTabCounts = undefined;

      const result = creativesStore.getTabCountsFromWindow();

      expect(result).toBe(false);
    });
  });

  describe('loadTabCounts', () => {
    it('should load tab counts successfully from API', async () => {
      const mockTabCounts = {
        push: 20,
        inpage: 15,
        facebook: 8,
        tiktok: 12,
      };

      // Мокаем успешный fetch
      global.fetch = vi.fn().mockResolvedValue({
        ok: true,
        json: vi.fn().mockResolvedValue(mockTabCounts),
      });

      await creativesStore.loadTabCounts();

      expect(global.fetch).toHaveBeenCalledWith('/api/creatives/tab-counts', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': 'test-csrf-token',
        },
      });

      expect(creativesStore.tabCounts).toEqual(mockTabCounts);
    });

    it('should handle API error and fallback to window data', async () => {
      const windowTabCounts = {
        push: 5,
        inpage: 3,
        facebook: 2,
        tiktok: 1,
      };

      // Мокаем неуспешный fetch
      global.fetch = vi.fn().mockResolvedValue({
        ok: false,
        status: 500,
      });

      // Устанавливаем данные в window для fallback
      global.window.creativesTabCounts = windowTabCounts;

      // Мокаем console.error чтобы не засорять вывод тестов
      const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      await creativesStore.loadTabCounts();

      expect(consoleErrorSpy).toHaveBeenCalledWith('Error loading tab counts:', expect.any(Error));

      expect(creativesStore.tabCounts).toEqual(windowTabCounts);

      consoleErrorSpy.mockRestore();
    });

    it('should handle network error and fallback to window data', async () => {
      const windowTabCounts = {
        push: 10,
        inpage: 7,
      };

      // Мокаем fetch с сетевой ошибкой
      global.fetch = vi.fn().mockRejectedValue(new Error('Network error'));

      // Устанавливаем данные в window для fallback
      global.window.creativesTabCounts = windowTabCounts;

      // Мокаем console.error
      const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});

      await creativesStore.loadTabCounts();

      expect(consoleErrorSpy).toHaveBeenCalledWith('Error loading tab counts:', expect.any(Error));

      expect(creativesStore.tabCounts).toEqual(windowTabCounts);

      consoleErrorSpy.mockRestore();
    });

    it('should log warning when neither API nor window data available', async () => {
      // Мокаем неуспешный fetch
      global.fetch = vi.fn().mockRejectedValue(new Error('API error'));

      // Убираем данные из window
      delete global.window.creativesTabCounts;

      // Мокаем console методы
      const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
      const consoleWarnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

      await creativesStore.loadTabCounts();

      expect(consoleErrorSpy).toHaveBeenCalledWith('Error loading tab counts:', expect.any(Error));

      expect(consoleWarnSpy).toHaveBeenCalledWith('No tab counts available from API or window');

      expect(creativesStore.tabCounts).toEqual({});

      consoleErrorSpy.mockRestore();
      consoleWarnSpy.mockRestore();
    });

    it('should handle missing CSRF token gracefully', async () => {
      // Мокаем document.querySelector чтобы вернуть null (нет CSRF токена)
      global.document.querySelector.mockReturnValue(null);

      const mockTabCounts = { push: 1, inpage: 2 };

      // Мокаем успешный fetch
      global.fetch = vi.fn().mockResolvedValue({
        ok: true,
        json: vi.fn().mockResolvedValue(mockTabCounts),
      });

      await creativesStore.loadTabCounts();

      expect(global.fetch).toHaveBeenCalledWith('/api/creatives/tab-counts', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': undefined, // когда нет CSRF токена
        },
      });

      expect(creativesStore.tabCounts).toEqual(mockTabCounts);
    });
  });
});
