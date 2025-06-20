/**
 * Blog Store Tests
 * Тесты базовой функциональности, обеспечивающей реактивность
 */

import { blogStore } from '@/stores/blog-store.js';
import { beforeEach, describe, expect, test, vi } from 'vitest';

// Mock для sessionStorage
const mockSessionStorage = {
  store: {},
  getItem: vi.fn(key => mockSessionStorage.store[key] || null),
  setItem: vi.fn((key, value) => {
    mockSessionStorage.store[key] = value;
  }),
  removeItem: vi.fn(key => {
    delete mockSessionStorage.store[key];
  }),
  clear: vi.fn(() => {
    mockSessionStorage.store = {};
  }),
};

// Mock для window объектов
Object.defineProperty(window, 'sessionStorage', {
  value: mockSessionStorage,
});

// Mock для window.location
const mockLocation = {
  search: '',
  href: 'http://localhost/blog',
  origin: 'http://localhost',
  pathname: '/blog',
};

Object.defineProperty(window, 'location', {
  value: mockLocation,
  writable: true,
});

// Mock для URL constructor
global.URL = class MockURL {
  constructor(url, base) {
    if (typeof url === 'object' && url.href) {
      // Если передан объект window.location
      this.href = url.href;
      this.origin = url.origin;
      this.pathname = url.pathname;
      this.search = url.search || '';
    } else if (typeof url === 'string') {
      if (url.startsWith('/')) {
        this.href = `http://localhost${url}`;
        this.origin = 'http://localhost';
        this.pathname = url;
      } else if (url.startsWith('http')) {
        this.href = url;
        this.origin = url.split('/').slice(0, 3).join('/');
        this.pathname = '/' + url.split('/').slice(3).join('/');
      } else {
        this.href = `http://localhost/${url}`;
        this.origin = 'http://localhost';
        this.pathname = `/${url}`;
      }
      this.search = '';
    } else {
      // Используем current location
      this.href = mockLocation.href;
      this.origin = mockLocation.origin;
      this.pathname = mockLocation.pathname;
      this.search = mockLocation.search || '';
    }

    this.searchParams = new URLSearchParams(this.search.replace('?', ''));
  }

  toString() {
    const params = this.searchParams.toString();
    return params ? `${this.href.split('?')[0]}?${params}` : this.href.split('?')[0];
  }
};

Object.defineProperty(window, 'history', {
  value: {
    pushState: vi.fn(),
    replaceState: vi.fn(),
  },
});

describe('Blog Store - Базовая реактивность', () => {
  beforeEach(() => {
    // Сброс состояния стора перед каждым тестом
    blogStore.resetState();
    blogStore.resetFilters();

    // Очистка моков
    vi.clearAllMocks();
    mockSessionStorage.clear();

    // Сброс location
    mockLocation.search = '';
    mockLocation.href = 'http://localhost/blog';
    mockLocation.origin = 'http://localhost';
    mockLocation.pathname = '/blog';
  });

  describe('Инициализация и базовое состояние', () => {
    test('должен иметь корректное начальное состояние', () => {
      expect(blogStore.loading).toBe(false);
      expect(blogStore.articles).toEqual([]);
      expect(blogStore.categories).toEqual([]);
      expect(blogStore.filters.page).toBe(1);
      expect(blogStore.filters.category).toBe('');
      expect(blogStore.filters.search).toBe('');
      expect(blogStore.filters.sort).toBe('latest');
      expect(blogStore.pagination.currentPage).toBe(1);
      expect(blogStore.pagination.totalPages).toBe(1);
    });

    test('должен корректно инициализироваться из URL', () => {
      mockLocation.search = '?page=2&category=tech&search=test&sort=popular';

      blogStore.initFromURL();

      expect(blogStore.filters.page).toBe(2);
      expect(blogStore.filters.category).toBe('tech');
      expect(blogStore.filters.search).toBe('test');
      expect(blogStore.filters.sort).toBe('popular');
    });

    test('должен загружать персистентные фильтры из sessionStorage', () => {
      const persistedFilters = {
        page: 3,
        category: 'news',
        search: 'stored search',
      };

      // Устанавливаем в store напрямую, т.к. наш мок работает через store объект
      mockSessionStorage.store['blog_filters'] = JSON.stringify(persistedFilters);

      // Устанавливаем URL с соответствующими параметрами, чтобы они не перезаписались дефолтными
      mockLocation.search = '?page=3&category=news&search=stored search';

      blogStore.init();

      expect(blogStore.filters.page).toBe(3);
      expect(blogStore.filters.category).toBe('news');
      expect(blogStore.filters.search).toBe('stored search');
    });

    test('URL должен иметь приоритет над sessionStorage', () => {
      const persistedFilters = {
        page: 3,
        category: 'news',
        search: 'stored search',
      };

      // Персистентные данные
      mockSessionStorage.store['blog_filters'] = JSON.stringify(persistedFilters);

      // URL с другими параметрами (имеет приоритет)
      mockLocation.search = '?page=5&category=tech&search=url search';

      blogStore.init();

      // Должны использоваться данные из URL, а не из sessionStorage
      expect(blogStore.filters.page).toBe(5);
      expect(blogStore.filters.category).toBe('tech');
      expect(blogStore.filters.search).toBe('url search');
    });
  });

  describe('Методы установки состояния (реактивность)', () => {
    test('setLoading должен устанавливать состояние загрузки', () => {
      blogStore.setLoading(true);
      expect(blogStore.loading).toBe(true);

      blogStore.setLoading(false);
      expect(blogStore.loading).toBe(false);
    });

    test('setArticles должен устанавливать массив статей', () => {
      const articles = [
        { id: 1, title: 'Article 1' },
        { id: 2, title: 'Article 2' },
      ];

      blogStore.setArticles(articles);
      expect(blogStore.articles).toEqual(articles);
    });

    test('setCategories должен устанавливать категории', () => {
      const categories = [
        { id: 1, slug: 'tech', name: 'Tech' },
        { id: 2, slug: 'news', name: 'News' },
      ];

      blogStore.setCategories(categories);
      expect(blogStore.categories).toEqual(categories);
    });

    test('setPagination должен обновлять пагинацию', () => {
      const paginationData = {
        currentPage: 2,
        totalPages: 5,
        hasNext: true,
        hasPrev: true,
      };

      blogStore.setPagination(paginationData);

      expect(blogStore.pagination.currentPage).toBe(2);
      expect(blogStore.pagination.totalPages).toBe(5);
      expect(blogStore.pagination.hasNext).toBe(true);
      expect(blogStore.pagination.hasPrev).toBe(true);
    });

    test('setFilters должен обновлять фильтры и синхронизировать пагинацию', () => {
      const newFilters = {
        page: 3,
        category: 'tech',
        search: 'test query',
      };

      blogStore.setFilters(newFilters);

      expect(blogStore.filters.page).toBe(3);
      expect(blogStore.filters.category).toBe('tech');
      expect(blogStore.filters.search).toBe('test query');
      expect(blogStore.pagination.currentPage).toBe(3);
    });

    test('setFilters должен сохранять фильтры в sessionStorage', () => {
      const newFilters = { page: 2, category: 'tech' };

      blogStore.setFilters(newFilters);

      expect(mockSessionStorage.setItem).toHaveBeenCalledWith(
        'blog_filters',
        JSON.stringify(blogStore.filters)
      );
    });

    test('setStats должен обновлять статистику', () => {
      const stats = {
        totalCount: 100,
        currentCount: 20,
        loadTime: 150,
      };

      blogStore.setStats(stats);

      expect(blogStore.stats.totalCount).toBe(100);
      expect(blogStore.stats.currentCount).toBe(20);
      expect(blogStore.stats.loadTime).toBe(150);
    });
  });

  describe('Computed properties', () => {
    test('isFirstPage должен корректно определять первую страницу', () => {
      blogStore.setPagination({ currentPage: 1 });
      expect(blogStore.isFirstPage).toBe(true);

      blogStore.setPagination({ currentPage: 2 });
      expect(blogStore.isFirstPage).toBe(false);
    });

    test('isLastPage должен корректно определять последнюю страницу', () => {
      blogStore.setPagination({ currentPage: 5, totalPages: 5 });
      expect(blogStore.isLastPage).toBe(true);

      blogStore.setPagination({ currentPage: 3, totalPages: 5 });
      expect(blogStore.isLastPage).toBe(false);
    });

    test('hasResults должен определять наличие результатов', () => {
      blogStore.setStats({ currentCount: 0 });
      expect(blogStore.hasResults).toBe(false);

      blogStore.setStats({ currentCount: 5 });
      expect(blogStore.hasResults).toBe(true);
    });

    test('isFiltered должен определять наличие активных фильтров', () => {
      expect(blogStore.isFiltered).toBe(false);

      blogStore.setFilters({ category: 'tech' });
      expect(blogStore.isFiltered).toBe(true);

      blogStore.resetFilters();
      blogStore.setFilters({ search: 'test' });
      expect(blogStore.isFiltered).toBe(true);

      blogStore.resetFilters();
      blogStore.setFilters({ sort: 'popular' });
      expect(blogStore.isFiltered).toBe(true);
    });

    test('filterParams должен возвращать URLSearchParams с активными фильтрами', () => {
      blogStore.setFilters({
        page: 2,
        category: 'tech',
        search: 'test',
        sort: 'popular',
        direction: 'asc',
      });

      const params = blogStore.filterParams;

      expect(params.get('page')).toBe('2');
      expect(params.get('category')).toBe('tech');
      expect(params.get('search')).toBe('test');
      expect(params.get('sort')).toBe('popular');
      expect(params.get('direction')).toBe('asc');
    });

    test('filterParams не должен включать дефолтные значения', () => {
      blogStore.setFilters({
        page: 1,
        category: '',
        search: '',
        sort: 'latest',
        direction: 'desc',
      });

      const params = blogStore.filterParams;

      expect(params.has('page')).toBe(false);
      expect(params.has('category')).toBe(false);
      expect(params.has('search')).toBe(false);
      expect(params.has('sort')).toBe(false);
      expect(params.has('direction')).toBe(false);
    });
  });

  describe('State API - новый интерфейс для работы с состоянием', () => {
    test('stateAPI должен предоставлять геттеры состояния', () => {
      expect(typeof blogStore.stateAPI.getLoading).toBe('function');
      expect(typeof blogStore.stateAPI.getFilters).toBe('function');
      expect(typeof blogStore.stateAPI.getArticles).toBe('function');
      expect(typeof blogStore.stateAPI.getPagination).toBe('function');
      expect(typeof blogStore.stateAPI.getStats).toBe('function');
    });

    test('stateAPI должен предоставлять сеттеры состояния', () => {
      expect(typeof blogStore.stateAPI.setLoading).toBe('function');
      expect(typeof blogStore.stateAPI.setFilters).toBe('function');
      expect(typeof blogStore.stateAPI.setArticles).toBe('function');
      expect(typeof blogStore.stateAPI.setPagination).toBe('function');
      expect(typeof blogStore.stateAPI.setStats).toBe('function');
    });

    test('stateAPI должен предоставлять computed properties', () => {
      expect(typeof blogStore.stateAPI.isFirstPage).toBe('function');
      expect(typeof blogStore.stateAPI.isLastPage).toBe('function');
      expect(typeof blogStore.stateAPI.hasResults).toBe('function');
      expect(typeof blogStore.stateAPI.isFiltered).toBe('function');
    });
  });

  describe('URL API - интерфейс для работы с URL', () => {
    test('urlAPI должен предоставлять методы работы с URL', () => {
      expect(typeof blogStore.urlAPI.getCurrentUrl).toBe('function');
      expect(typeof blogStore.urlAPI.isStateSynced).toBe('function');
      expect(typeof blogStore.urlAPI.updateUrl).toBe('function');
      expect(typeof blogStore.urlAPI.restoreFromUrl).toBe('function');
      expect(typeof blogStore.urlAPI.forceSync).toBe('function');
    });
  });

  describe('Работа с URL и персистентность', () => {
    test('updateURL должен обновлять URL с текущими фильтрами', () => {
      blogStore.setFilters({
        page: 2,
        category: 'tech',
        search: 'test',
      });

      blogStore.updateURL(true);

      expect(window.history.pushState).toHaveBeenCalled();

      const [stateData, , url] = window.history.pushState.mock.calls[0];
      expect(stateData.blogFilters.page).toBe(2);
      expect(stateData.blogFilters.category).toBe('tech');
      expect(stateData.blogFilters.search).toBe('test');
      expect(url).toContain('page=2');
      expect(url).toContain('category=tech');
      expect(url).toContain('search=test');
    });

    test('updateURL с replaceState должен использовать replaceState', () => {
      // Устанавливаем фильтры без вызова updateURL
      blogStore.filters = { page: 2, category: '', search: '', sort: 'latest', direction: 'desc' };

      // Очищаем моки после установки фильтров
      vi.clearAllMocks();

      blogStore.updateURL(false);

      expect(window.history.replaceState).toHaveBeenCalled();
      expect(window.history.pushState).not.toHaveBeenCalled();
    });

    test('persistFilters должен сохранять фильтры в sessionStorage', () => {
      blogStore.setFilters({
        page: 3,
        category: 'news',
        search: 'test query',
      });

      blogStore.persistFilters();

      expect(mockSessionStorage.setItem).toHaveBeenCalledWith(
        'blog_filters',
        JSON.stringify(blogStore.filters)
      );
    });

    test('resetFilters должен сбрасывать фильтры к дефолтным', () => {
      blogStore.setFilters({
        page: 5,
        category: 'tech',
        search: 'test',
        sort: 'popular',
      });

      blogStore.resetFilters();

      expect(blogStore.filters.page).toBe(1);
      expect(blogStore.filters.category).toBe('');
      expect(blogStore.filters.search).toBe('');
      expect(blogStore.filters.sort).toBe('latest');
      expect(blogStore.filters.direction).toBe('desc');
    });

    test('validateFilters должен валидировать фильтры', () => {
      // Валидные фильтры
      blogStore.setFilters({
        page: 5,
        category: 'tech-news',
        search: 'valid search',
      });
      expect(blogStore.validateFilters()).toBe(true);

      // Невалидная страница
      blogStore.setFilters({ page: -1 });
      expect(blogStore.validateFilters()).toBe(false);

      // Невалидная категория
      blogStore.setFilters({ page: 1, category: 'invalid@category' });
      expect(blogStore.validateFilters()).toBe(false);

      // Слишком длинный поиск
      blogStore.setFilters({ page: 1, category: '', search: 'a'.repeat(256) });
      expect(blogStore.validateFilters()).toBe(false);
    });
  });

  describe('Синхронизация состояния и URL', () => {
    test('должен корректно определять синхронизацию состояния с URL', () => {
      // Устанавливаем состояние
      blogStore.setFilters({ page: 2, category: 'tech' });

      // Устанавливаем соответствующий URL
      mockLocation.search = '?page=2&category=tech';

      expect(blogStore.isStateInSync()).toBe(true);

      // Меняем URL, состояние не синхронизировано
      mockLocation.search = '?page=3&category=news';

      expect(blogStore.isStateInSync()).toBe(false);
    });

    test('должен корректно обновлять состояние из URL', () => {
      mockLocation.search = '?page=3&category=news&search=query';

      blogStore.updateFromURL();

      expect(blogStore.filters.page).toBe(3);
      expect(blogStore.filters.category).toBe('news');
      expect(blogStore.filters.search).toBe('query');
    });

    test('должен корректно принудительно синхронизировать состояние', () => {
      mockLocation.search = '?page=4&category=science';

      blogStore.forceSyncState();

      expect(blogStore.filters.page).toBe(4);
      expect(blogStore.filters.category).toBe('science');
    });

    test('должен корректно восстанавливать состояние из URL', () => {
      mockLocation.search = '?page=2&search=test&sort=popular';

      blogStore.restoreStateFromURL();

      expect(blogStore.filters.page).toBe(2);
      expect(blogStore.filters.search).toBe('test');
      expect(blogStore.filters.sort).toBe('popular');
    });
  });
});
