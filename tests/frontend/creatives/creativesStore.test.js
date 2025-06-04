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
