/**
 * Alpine.js Magic Methods for Blog
 * Глобальные магические методы для удобного доступа к функциональности блога
 */

import Alpine from 'alpinejs';
import { blogAjaxManager } from '../managers/blog-ajax-manager';

/**
 * Magic method $blog
 * Предоставляет удобный доступ к store и основным методам блога
 * Использование: $blog.loading(), $blog.goToPage(2)
 */
export function initBlogMagicMethods() {
  Alpine.magic('blog', () => {
    return {
      // Прямой доступ к store и manager
      store: Alpine.store('blog'),
      manager: blogAjaxManager,

      // Quick access methods для получения данных
      loading: () => Alpine.store('blog').loading,
      filters: () => Alpine.store('blog').filters,
      stats: () => Alpine.store('blog').stats,
      pagination: () => Alpine.store('blog').pagination,
      articles: () => Alpine.store('blog').articles,
      categories: () => Alpine.store('blog').categories,

      // Navigation methods - используют blogAjaxManager
      goToPage: page => blogAjaxManager.goToPage(page),
      setCategory: slug => blogAjaxManager.setCategory(slug),
      setSearch: query => blogAjaxManager.setSearch(query),
      setSort: (type, direction) => blogAjaxManager.setSort(type, direction),

      // Helper methods для очистки фильтров
      clearFilters: () => Alpine.store('blog').resetFilters(),
      clearSearch: () => Alpine.store('blog').clearSearch(),
      clearCategory: () => Alpine.store('blog').clearCategory(),

      // Utility methods
      loadContent: (container, url, options) =>
        blogAjaxManager.loadContent(container, url, options),

      // State helpers
      hasResults: () => Alpine.store('blog').hasResults,
      isFiltered: () => Alpine.store('blog').isFiltered,
      isFirstPage: () => Alpine.store('blog').isFirstPage,
      isLastPage: () => Alpine.store('blog').isLastPage,

      // NEW: URL Synchronization methods
      url: {
        // Получить текущий URL с фильтрами
        current: () => Alpine.store('blog').getCurrentURL(),

        // Обновить URL без перезагрузки страницы
        update: (pushState = true) => Alpine.store('blog').updateURL(pushState),

        // Инициализировать состояние из URL
        initFromURL: () => Alpine.store('blog').initFromURL(),

        // Синхронизировать состояние с URL
        syncFromURL: () => Alpine.store('blog').updateFromURL(),

        // Проверить синхронизацию состояния с URL
        isInSync: () => Alpine.store('blog').isStateInSync(),

        // Навигация с обновлением URL и состояния
        navigateTo: (filters, replaceState = false) =>
          Alpine.store('blog').navigateToState(filters, replaceState),

        // Получить параметры фильтров как URLSearchParams
        getParams: () => Alpine.store('blog').filterParams,

        // Построить URL для AJAX запроса
        buildRequest: baseUrl => Alpine.store('blog').buildRequestURL(baseUrl),
      },

      // NEW: History and State management
      history: {
        // Сохранить текущее состояние в истории
        push: (state = null) => {
          const store = Alpine.store('blog');
          const stateData = state || {
            blogFilters: {
              page: store.filters.page,
              category: store.filters.category || '',
              search: store.filters.search || '',
              sort: store.filters.sort || 'latest',
              direction: store.filters.direction || 'desc',
            },
            timestamp: Date.now(),
          };
          window.history.pushState(stateData, '', store.getCurrentURL());
        },

        // Заменить текущее состояние в истории
        replace: (state = null) => {
          const store = Alpine.store('blog');
          const stateData = state || {
            blogFilters: {
              page: store.filters.page,
              category: store.filters.category || '',
              search: store.filters.search || '',
              sort: store.filters.sort || 'latest',
              direction: store.filters.direction || 'desc',
            },
            timestamp: Date.now(),
          };
          window.history.replaceState(stateData, '', store.getCurrentURL());
        },

        // Вернуться назад
        back: () => window.history.back(),

        // Перейти вперед
        forward: () => window.history.forward(),

        // Перейти на определенное количество шагов
        go: steps => window.history.go(steps),
      },

      // NEW: Scroll position management
      scroll: {
        // Сохранить текущую позицию скролла
        save: () => {
          const store = Alpine.store('blog');
          const position = window.pageYOffset || document.documentElement.scrollTop;
          store.setUIState({ ...store.ui, scrollPosition: position });
          sessionStorage.setItem('blog_scroll_position', position.toString());
        },

        // Восстановить сохраненную позицию скролла
        restore: (smooth = true) => {
          const savedPosition = sessionStorage.getItem('blog_scroll_position');
          if (savedPosition) {
            const position = parseInt(savedPosition);
            window.scrollTo({
              top: position,
              behavior: smooth ? 'smooth' : 'auto',
            });
          }
        },

        // Скроллить к верху страницы
        toTop: (smooth = true) => {
          window.scrollTo({
            top: 0,
            behavior: smooth ? 'smooth' : 'auto',
          });
        },

        // Получить текущую позицию скролла
        position: () => window.pageYOffset || document.documentElement.scrollTop,
      },

      // NEW: Utility methods for state management
      state: {
        // Экспорт текущего состояния
        export: () => ({
          filters: Alpine.store('blog').filters,
          ui: Alpine.store('blog').ui,
          url: Alpine.store('blog').getCurrentURL(),
          timestamp: Date.now(),
        }),

        // Импорт состояния
        import: state => {
          const store = Alpine.store('blog');
          if (state.filters) {
            store.setFilters(state.filters);
          }
          if (state.ui) {
            store.setUIState(state.ui);
          }
        },

        // Сброс всего состояния
        reset: () => {
          Alpine.store('blog').resetState();
          sessionStorage.removeItem('blog_filters');
          sessionStorage.removeItem('blog_scroll_position');
        },

        // Проверка валидности состояния
        isValid: () => Alpine.store('blog').validateFilters(),

        // Сохранение состояния в sessionStorage
        persist: () => Alpine.store('blog').persistFilters(),
      },
    };
  });
}
