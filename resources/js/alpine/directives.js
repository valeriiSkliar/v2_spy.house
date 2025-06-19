/**
 * Alpine.js Custom Directives for Blog
 * Централизованные директивы для блога
 */

import Alpine from 'alpinejs';

/**
 * Директива для состояний загрузки блога
 * Использование: x-blog-loading="loading"
 */
export function initBlogLoadingDirective() {
  Alpine.directive('blog-loading', (el, { expression }, { evaluateLater, effect }) => {
    const evaluate = evaluateLater(expression || 'loading');

    effect(() => {
      evaluate(loading => {
        if (loading) {
          el.style.opacity = '0.5';
          el.style.pointerEvents = 'none';
          el.setAttribute('aria-busy', 'true');
        } else {
          el.style.opacity = '1';
          el.style.pointerEvents = 'auto';
          el.removeAttribute('aria-busy');
        }
      });
    });
  });
}

/**
 * Директива для фильтров блога с синхронизацией URL
 * Использование: x-blog-filter.search="searchValue"
 */
export function initBlogFilterDirective() {
  Alpine.directive('blog-filter', (el, { modifiers, expression }, { evaluateLater, effect }) => {
    const evaluate = evaluateLater(expression);

    effect(() => {
      evaluate(filterValue => {
        const store = Alpine.store('blog');

        if (modifiers.includes('category')) {
          store.setFilters({ ...store.filters, category: filterValue, page: 1 });
        } else if (modifiers.includes('search')) {
          store.setFilters({ ...store.filters, search: filterValue, page: 1 });
        } else if (modifiers.includes('sort')) {
          store.setFilters({ ...store.filters, sort: filterValue, page: 1 });
        } else if (modifiers.includes('page')) {
          store.setFilters({ ...store.filters, page: parseInt(filterValue) || 1 });
        }
      });
    });
  });
}

/**
 * NEW: Директива для синхронизации элемента с URL состоянием
 * Использование: x-blog-url-sync.search="searchInput" или x-blog-url-sync.category="categorySelect"
 */
export function initBlogUrlSyncDirective() {
  Alpine.directive('blog-url-sync', (el, { modifiers, expression }, { evaluateLater, effect }) => {
    const store = Alpine.store('blog');

    // Инициализация элемента из URL при первом рендере
    setTimeout(() => {
      if (modifiers.includes('search')) {
        el.value = store.filters.search || '';
      } else if (modifiers.includes('category')) {
        el.value = store.filters.category || '';
      } else if (modifiers.includes('sort')) {
        el.value = store.filters.sort || 'latest';
      }

      // Trigger change event to update Alpine state
      el.dispatchEvent(new Event('input', { bubbles: true }));
    }, 50);

    // Слушаем изменения в store и обновляем элемент
    effect(() => {
      if (modifiers.includes('search') && el.value !== store.filters.search) {
        el.value = store.filters.search || '';
      } else if (modifiers.includes('category') && el.value !== store.filters.category) {
        el.value = store.filters.category || '';
      } else if (modifiers.includes('sort') && el.value !== store.filters.sort) {
        el.value = store.filters.sort || 'latest';
      }
    });
  });
}

/**
 * NEW: Директива для восстановления состояния скролла
 * Использование: x-blog-scroll-restore
 */
export function initBlogScrollRestoreDirective() {
  Alpine.directive('blog-scroll-restore', (el, { expression }, { evaluateLater, effect }) => {
    const store = Alpine.store('blog');

    // Сохраняем позицию скролла при изменении фильтров
    const saveScrollPosition = () => {
      store.setUIState({
        ...store.ui,
        scrollPosition: window.pageYOffset || document.documentElement.scrollTop,
      });
      sessionStorage.setItem('blog_scroll_position', store.ui.scrollPosition.toString());
    };

    // Восстанавливаем позицию скролла
    const restoreScrollPosition = () => {
      const savedPosition = sessionStorage.getItem('blog_scroll_position');
      if (savedPosition) {
        const position = parseInt(savedPosition);
        setTimeout(() => {
          window.scrollTo({ top: position, behavior: 'smooth' });
        }, 100);
      }
    };

    // Сохраняем позицию при скролле
    const throttledSave = throttle(saveScrollPosition, 500);
    window.addEventListener('scroll', throttledSave);

    // Восстанавливаем при инициализации
    restoreScrollPosition();

    // Очистка при уничтожении элемента
    el._blogScrollCleanup = () => {
      window.removeEventListener('scroll', throttledSave);
    };
  });
}

/**
 * NEW: Директива для навигации с сохранением состояния
 * Использование: x-blog-navigate="{ page: 2 }" или x-blog-navigate="{ category: 'tech' }"
 */
export function initBlogNavigateDirective() {
  Alpine.directive('blog-navigate', (el, { expression }, { evaluateLater, effect }) => {
    const evaluate = evaluateLater(expression);

    el.addEventListener('click', e => {
      e.preventDefault();

      evaluate(navigationData => {
        const store = Alpine.store('blog');

        if (navigationData && typeof navigationData === 'object') {
          // Используем navigateToState для автоматической синхронизации с URL
          store.navigateToState(navigationData, false);
        }
      });
    });
  });
}

/**
 * NEW: Директива для пагинации блога
 * Использование: x-blog-paginate="pageNumber" или x-blog-paginate.next x-blog-paginate.prev
 */
export function initBlogPaginateDirective() {
  Alpine.directive('blog-paginate', (el, { modifiers, expression }, { evaluateLater, effect }) => {
    // Создаем evaluate только если есть expression
    const evaluate = expression ? evaluateLater(expression) : null;

    const handleClick = e => {
      e.preventDefault();
      e.stopPropagation();

      const store = Alpine.store('blog');
      if (!store || store.loading) return;

      if (modifiers.includes('next')) {
        const nextPage = store.pagination.currentPage + 1;
        if (nextPage <= store.pagination.totalPages) {
          store.setFilters({ ...store.filters, page: nextPage });
        }
      } else if (modifiers.includes('prev')) {
        const prevPage = store.pagination.currentPage - 1;
        if (prevPage >= 1) {
          store.setFilters({ ...store.filters, page: prevPage });
        }
      } else if (modifiers.includes('first')) {
        store.setFilters({ ...store.filters, page: 1 });
      } else if (modifiers.includes('last')) {
        store.setFilters({ ...store.filters, page: store.pagination.totalPages });
      } else if (expression && evaluate) {
        // Конкретный номер страницы
        evaluate(page => {
          const targetPage = parseInt(page);
          if (targetPage >= 1 && targetPage <= store.pagination.totalPages) {
            store.setFilters({ ...store.filters, page: targetPage });
          }
        });
      }
    };

    // Обработчик клика
    el.addEventListener('click', handleClick);

    // Обработчик клавиатуры для доступности
    el.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        handleClick(e);
      }
    });

    // Установка ARIA атрибутов для доступности
    el.setAttribute('role', 'button');
    el.setAttribute('tabindex', '0');

    // Обновление состояния disabled
    effect(() => {
      const store = Alpine.store('blog');
      if (!store) return;

      let disabled = false;

      if (modifiers.includes('next')) {
        disabled = !store.pagination.hasNext || store.loading;
      } else if (modifiers.includes('prev')) {
        disabled = !store.pagination.hasPrev || store.loading;
      } else if (modifiers.includes('first')) {
        disabled = store.pagination.currentPage === 1 || store.loading;
      } else if (modifiers.includes('last')) {
        disabled = store.pagination.currentPage === store.pagination.totalPages || store.loading;
      }

      if (disabled) {
        el.setAttribute('aria-disabled', 'true');
        el.classList.add('disabled');
      } else {
        el.removeAttribute('aria-disabled');
        el.classList.remove('disabled');
      }
    });
  });
}

/**
 * NEW: Директива для отслеживания изменений URL
 * Использование: x-blog-url-watcher
 */
export function initBlogUrlWatcherDirective() {
  Alpine.directive('blog-url-watcher', (el, { expression }, { evaluateLater, effect }) => {
    const store = Alpine.store('blog');

    // Слушаем изменения URL через popstate
    const handlePopState = () => {
      console.log('URL changed, updating blog state');
      store.handlePopState();
    };

    window.addEventListener('popstate', handlePopState);

    // Очистка при уничтожении
    el._blogUrlWatcherCleanup = () => {
      window.removeEventListener('popstate', handlePopState);
    };
  });
}

/**
 * Утилитарная функция throttle
 */
function throttle(func, limit) {
  let inThrottle;
  return function () {
    const args = arguments;
    const context = this;
    if (!inThrottle) {
      func.apply(context, args);
      inThrottle = true;
      setTimeout(() => (inThrottle = false), limit);
    }
  };
}

/**
 * Инициализация всех директив
 */
export function initBlogDirectives() {
  initBlogLoadingDirective();
  initBlogFilterDirective();
  initBlogUrlSyncDirective();
  initBlogScrollRestoreDirective();
  initBlogNavigateDirective();
  initBlogPaginateDirective();
  initBlogUrlWatcherDirective();
}
