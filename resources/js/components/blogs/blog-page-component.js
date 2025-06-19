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

        // Устанавливаем фильтры
        store.setFilters({
          search: validatedData.filters.search,
          category: validatedData.filters.category,
          sort: validatedData.filters.sort,
          direction: validatedData.filters.direction,
          page: validatedData.currentPage,
        });

        // Устанавливаем категории для сайдбара
        store.setCategories(validatedData.categories);

        // Устанавливаем популярные посты
        store.setPopularPosts(validatedData.popularPosts);

        // Сохраняем heroArticle и URL отдельно (они не входят в основной store)
        this.heroArticle = validatedData.heroArticle;
        this.ajaxUrl = validatedData.ajaxUrl;

        // Синхронизируем с AJAX менеджером
        this.syncWithAjaxManager();

        console.log('Blog page initialized successfully:', {
          articlesCount: store.articles.length,
          heroArticle: this.heroArticle,
          filters: store.filters,
          currentPage: store.pagination.currentPage,
          totalPages: store.pagination.totalPages,
        });
      } catch (error) {
        console.error('Error initializing blog page:', error);
        this.handleInitializationError(error);
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
    },

    // Локальные данные (не входят в store)
    heroArticle: null,
    ajaxUrl: '',

    // Синхронизация с AJAX менеджером
    syncWithAjaxManager() {
      if (blogAjaxManager && typeof blogAjaxManager.initFromURL === 'function') {
        blogAjaxManager.initFromURL();
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
      this.loadContentWithManager(() => blogAjaxManager.goToPage(page));
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
