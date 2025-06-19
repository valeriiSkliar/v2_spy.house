/**
 * Blog Page Alpine Component
 * Легкая обертка над blogStore - единый источник данных
 * Отвечает только за инициализацию серверных данных и интеграцию с шаблоном
 */

import Alpine from 'alpinejs';
import { blogAjaxManager } from '../../managers/blog-ajax-manager';

export function initBlogPageComponent() {
  Alpine.data('blogPage', () => ({
    // Инициализация из серверных данных
    initFromServer(serverData) {
      console.log('Initializing blog page from server data...', serverData);

      const store = this.getStore();
      if (!store) {
        console.error('Blog store not available');
        return;
      }

      // Устанавливаем данные статей в store
      store.setArticles(serverData.articles || []);

      // Устанавливаем статистику
      store.setStats({
        totalCount: serverData.totalCount || 0,
        currentCount: (serverData.articles || []).length + (serverData.heroArticle ? 1 : 0),
      });

      // Устанавливаем пагинацию
      store.setPagination({
        currentPage: serverData.currentPage || 1,
        totalPages: serverData.totalPages || 1,
        hasPagination: serverData.hasPagination || false,
        hasNext: (serverData.currentPage || 1) < (serverData.totalPages || 1),
        hasPrev: (serverData.currentPage || 1) > 1,
      });

      // Устанавливаем фильтры
      store.setFilters({
        search: serverData.filters?.search || '',
        category: serverData.filters?.category || '',
        sort: serverData.filters?.sort || 'latest',
        direction: serverData.filters?.direction || 'desc',
        page: serverData.currentPage || 1,
      });

      // Устанавливаем категории для сайдбара
      store.setCategories(serverData.categories || []);

      // Устанавливаем популярные посты
      store.setPopularPosts(serverData.popularPosts || []);

      // Сохраняем heroArticle и URL отдельно (они не входят в основной store)
      this.heroArticle = serverData.heroArticle || null;
      this.ajaxUrl = serverData.ajaxUrl || '';

      // Синхронизируем с AJAX менеджером
      this.syncWithAjaxManager();

      console.log('Blog page initialized:', {
        articlesCount: store.articles.length,
        heroArticle: this.heroArticle,
        filters: store.filters,
        currentPage: store.pagination.currentPage,
        totalPages: store.pagination.totalPages,
      });
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
      return this.getStore()?.loading || false;
    },

    get articles() {
      return this.getStore()?.articles || [];
    },

    get totalCount() {
      return this.getStore()?.stats.totalCount || 0;
    },

    get currentPage() {
      return this.getStore()?.pagination.currentPage || 1;
    },

    get totalPages() {
      return this.getStore()?.pagination.totalPages || 1;
    },

    get hasPagination() {
      return this.getStore()?.pagination.hasPagination || false;
    },

    get filters() {
      return this.getStore()?.filters || {};
    },

    get categories() {
      return this.getStore()?.categories || [];
    },

    get popularPosts() {
      return this.getStore()?.popularPosts || [];
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
