/**
 * Blog Page Alpine Component
 * Единый компонент для страницы блога с инициализацией серверных данных
 */

import Alpine from 'alpinejs';
import { blogAjaxManager } from '../../managers/blog-ajax-manager';

export function initBlogPageComponent() {
  Alpine.data('blogPage', () => ({
    // Инициализация из серверных данных
    initFromServer(serverData) {
      console.log('Initializing blog page from server data...', serverData);

      // Валидация входных данных
      if (!serverData || typeof serverData !== 'object') {
        console.error('Invalid server data provided:', serverData);
        return;
      }

      const store = this.getStore();
      if (!store) {
        console.error('Blog store not available');
        return;
      }

      try {
        // Валидация и нормализация данных
        const validatedData = this.validateServerData(serverData);

        // NEW: Сначала инициализируем фильтры из URL, если они есть
        const urlHasFilters = this.hasUrlFilters();
        if (urlHasFilters) {
          console.log('URL contains filters, initializing from URL first');
          store.initFromURL();
        }

        // Устанавливаем данные статей в store
        store.setArticles(validatedData.articles);

        // Устанавливаем статистику
        store.setStats({
          totalCount: validatedData.totalCount,
          currentCount: validatedData.currentCount,
        });

        // Устанавливаем пагинацию
        store.setPagination({
          currentPage: validatedData.currentPage,
          totalPages: validatedData.totalPages,
          hasPagination: validatedData.hasPagination,
          hasNext: validatedData.currentPage < validatedData.totalPages,
          hasPrev: validatedData.currentPage > 1,
        });

        // Устанавливаем фильтры (если не было инициализации из URL)
        if (!urlHasFilters) {
          store.setFiltersFromURL({
            search: validatedData.filters.search,
            category: validatedData.filters.category,
            sort: validatedData.filters.sort,
            direction: validatedData.filters.direction,
            page: validatedData.currentPage,
          });

          // Обновляем URL с начальными фильтрами
          store.updateURL(false); // replaceState, не pushState
        }

        // Устанавливаем категории для сайдбара
        store.setCategories(validatedData.categories);

        // Устанавливаем популярные посты
        store.setPopularPosts(validatedData.popularPosts);

        // Сохраняем heroArticle и URL отдельно (они не входят в основной store)
        this.heroArticle = validatedData.heroArticle;
        this.ajaxUrl = validatedData.ajaxUrl;

        // Синхронизируем с AJAX менеджером
        this.syncWithAjaxManager();

        // NEW: Устанавливаем флаг успешной инициализации
        this.initialized = true;

        console.log('Blog page initialized successfully:', {
          articlesCount: store.articles.length,
          heroArticle: this.heroArticle,
          filters: store.filters,
          currentPage: store.pagination.currentPage,
          totalPages: store.pagination.totalPages,
          urlSynced: store.isStateInSync(),
        });
      } catch (error) {
        console.error('Error initializing blog page:', error);
        this.handleInitializationError(error);
      }
    },

    // NEW: Проверка наличия фильтров в URL
    hasUrlFilters() {
      const urlParams = new URLSearchParams(window.location.search);
      return (
        urlParams.has('page') ||
        urlParams.has('category') ||
        urlParams.has('search') ||
        urlParams.has('sort') ||
        urlParams.has('direction')
      );
    },

    // NEW: Инициализация только из URL (для восстановления состояния)
    initFromURL() {
      console.log('Initializing blog page from URL only...');

      const store = this.getStore();
      if (!store) {
        console.error('Blog store not available');
        return;
      }

      try {
        // Инициализируем состояние из URL
        store.initFromURL();

        // Помечаем как инициализированный из URL
        this.urlInitialized = true;

        // Синхронизируем с AJAX менеджером
        this.syncWithAjaxManager();

        console.log('Blog page initialized from URL:', {
          filters: store.filters,
          urlSynced: store.isStateInSync(),
        });

        return true;
      } catch (error) {
        console.error('Error initializing from URL:', error);
        return false;
      }
    },

    // Валидация серверных данных
    validateServerData(serverData) {
      const defaults = {
        articles: [],
        heroArticle: null,
        totalCount: 0,
        currentPage: 1,
        totalPages: 1,
        hasPagination: false,
        filters: {
          search: '',
          category: '',
          sort: 'latest',
          direction: 'desc',
        },
        categories: [],
        popularPosts: [],
        ajaxUrl: '',
      };

      const validated = { ...defaults };

      // Валидация массивов
      if (Array.isArray(serverData.articles)) {
        validated.articles = serverData.articles;
      }
      if (Array.isArray(serverData.categories)) {
        validated.categories = serverData.categories;
      }
      if (Array.isArray(serverData.popularPosts)) {
        validated.popularPosts = serverData.popularPosts;
      }

      // Валидация чисел
      if (typeof serverData.totalCount === 'number' && serverData.totalCount >= 0) {
        validated.totalCount = serverData.totalCount;
      }
      if (typeof serverData.currentPage === 'number' && serverData.currentPage >= 1) {
        validated.currentPage = serverData.currentPage;
      }
      if (typeof serverData.totalPages === 'number' && serverData.totalPages >= 1) {
        validated.totalPages = serverData.totalPages;
      }

      // Валидация boolean
      if (typeof serverData.hasPagination === 'boolean') {
        validated.hasPagination = serverData.hasPagination;
      }

      // Валидация объектов
      if (serverData.heroArticle && typeof serverData.heroArticle === 'object') {
        validated.heroArticle = serverData.heroArticle;
      }
      if (serverData.filters && typeof serverData.filters === 'object') {
        validated.filters = { ...defaults.filters, ...serverData.filters };
      }

      // Валидация строк
      if (typeof serverData.ajaxUrl === 'string' && serverData.ajaxUrl.length > 0) {
        validated.ajaxUrl = serverData.ajaxUrl;
      }

      // Вычисляем currentCount
      validated.currentCount = validated.articles.length + (validated.heroArticle ? 1 : 0);

      return validated;
    },

    // Обработка ошибок инициализации
    handleInitializationError(error) {
      console.error('Blog initialization failed:', error);

      // Устанавливаем минимальное рабочее состояние
      const store = this.getStore();
      if (store) {
        store.setArticles([]);
        store.setStats({ totalCount: 0, currentCount: 0 });
        store.setPagination({
          currentPage: 1,
          totalPages: 1,
          hasPagination: false,
          hasNext: false,
          hasPrev: false,
        });
        store.resetFilters();
      }

      this.heroArticle = null;
      this.ajaxUrl = '';
      this.initialized = false;
    },

    // Локальные данные (не входят в store)
    heroArticle: null,
    ajaxUrl: '',
    initialized: false,
    urlInitialized: false,

    // Синхронизация с AJAX менеджером
    syncWithAjaxManager() {
      if (blogAjaxManager && typeof blogAjaxManager.initFromURL === 'function') {
        blogAjaxManager.initFromURL();
      }

      // NEW: Обеспечиваем глобальный доступ к manager
      if (typeof window !== 'undefined') {
        window.blogAjaxManager = blogAjaxManager;
      }
    },

    // Получение store
    getStore() {
      try {
        return Alpine.store('blog');
      } catch (e) {
        console.error('Blog store not available:', e);
        return null;
      }
    },

    // NEW: Восстановление состояния из URL (для браузерной навигации)
    restoreFromURL() {
      console.log('Restoring blog state from URL...');

      const store = this.getStore();
      if (!store) return false;

      try {
        // Обновляем состояние из URL
        store.updateFromURL();

        // Если есть AJAX URL, перезагружаем контент
        if (this.ajaxUrl) {
          this.loadContentFromCurrentState();
        }

        console.log('State restored from URL:', store.filters);
        return true;
      } catch (error) {
        console.error('Error restoring from URL:', error);
        return false;
      }
    },

    // NEW: Загрузка контента на основе текущего состояния
    loadContentFromCurrentState() {
      if (!this.ajaxUrl) {
        console.warn('No AJAX URL available for content loading');
        return;
      }

      const container = document.getElementById('blog-articles-container');
      if (!container) {
        console.warn('Blog container not found');
        return;
      }

      if (blogAjaxManager && typeof blogAjaxManager.loadContent === 'function') {
        const store = this.getStore();
        const requestUrl = store ? store.buildRequestURL(this.ajaxUrl) : this.ajaxUrl;
        blogAjaxManager.loadContent(container, requestUrl);
      }
    },

    // Проксируем свойства из store для удобства использования в шаблонах
    get loading() {
      const store = this.getStore();
      return store ? store.loading : false;
    },

    get articles() {
      const store = this.getStore();
      return store ? store.articles : [];
    },

    get totalCount() {
      const store = this.getStore();
      return store ? store.stats.totalCount : 0;
    },

    get currentPage() {
      const store = this.getStore();
      return store ? store.pagination.currentPage : 1;
    },

    get totalPages() {
      const store = this.getStore();
      return store ? store.pagination.totalPages : 1;
    },

    get hasPagination() {
      const store = this.getStore();
      return store ? store.pagination.hasPagination : false;
    },

    get filters() {
      const store = this.getStore();
      return store ? store.filters : {};
    },

    get categories() {
      const store = this.getStore();
      return store ? store.categories : [];
    },

    get popularPosts() {
      const store = this.getStore();
      return store ? store.popularPosts : [];
    },

    // NEW: URL-related getters
    get currentUrl() {
      const store = this.getStore();
      return store ? store.getCurrentURL() : window.location.href;
    },

    get isUrlSynced() {
      const store = this.getStore();
      return store ? store.isStateInSync() : false;
    },

    // Геттеры для шаблонов
    get hasArticles() {
      return this.articles.length > 0 || !!this.heroArticle;
    },

    get showNoResults() {
      return !this.hasArticles && !this.loading;
    },

    get showPagination() {
      return this.hasPagination && this.totalPages > 1;
    },

    // Навигационные методы - проксируем к AJAX менеджеру
    goToPage(page) {
      if (this.loading) return;
      console.log(`Navigating to page ${page}`);
      this.loadContentWithManager(() => blogAjaxManager.goToPage(page));
    },

    // NEW: Специальные методы для пагинации
    goToNextPage() {
      if (this.loading) return;
      const store = this.getStore();
      if (store && store.pagination.hasNext) {
        this.goToPage(store.pagination.currentPage + 1);
      }
    },

    goToPrevPage() {
      if (this.loading) return;
      const store = this.getStore();
      if (store && store.pagination.hasPrev) {
        this.goToPage(store.pagination.currentPage - 1);
      }
    },

    goToFirstPage() {
      if (this.loading) return;
      const store = this.getStore();
      if (store && store.pagination.currentPage > 1) {
        this.goToPage(1);
      }
    },

    goToLastPage() {
      if (this.loading) return;
      const store = this.getStore();
      if (store) {
        this.goToPage(store.pagination.totalPages);
      }
    },

    setCategory(categorySlug) {
      if (this.loading) return;
      this.loadContentWithManager(() => blogAjaxManager.setCategory(categorySlug));
    },

    setSearch(searchQuery) {
      if (this.loading) return;
      this.loadContentWithManager(() => blogAjaxManager.setSearch(searchQuery));
    },

    setSort(sortType, direction = 'desc') {
      if (this.loading) return;
      this.loadContentWithManager(() => blogAjaxManager.setSort(sortType, direction));
    },

    // Утилитарные методы
    clearFilters() {
      if (this.loading) return;
      this.loadContentWithManager(() => {
        const store = this.getStore();
        if (store) {
          store.resetFilters();
        }
      });
    },

    clearSearch() {
      if (this.loading) return;
      this.loadContentWithManager(() => {
        const store = this.getStore();
        if (store) {
          store.clearSearch();
        }
      });
    },

    clearCategory() {
      if (this.loading) return;
      this.loadContentWithManager(() => {
        const store = this.getStore();
        if (store) {
          store.clearCategory();
        }
      });
    },

    // Вспомогательный метод для загрузки контента через менеджер
    loadContentWithManager(action) {
      const container = document.getElementById('blog-articles-container');
      if (!container || !this.ajaxUrl) {
        console.warn('Container or AJAX URL not found');
        return;
      }

      if (blogAjaxManager && typeof blogAjaxManager.loadContent === 'function') {
        // Выполняем действие (обновление фильтров)
        if (typeof action === 'function') {
          action();
        }
        // Загружаем контент
        blogAjaxManager.loadContent(container, this.ajaxUrl);
      } else {
        console.warn('Blog AJAX manager not available');
      }
    },
  }));
}
