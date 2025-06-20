/**
 * Simplified Alpine.js Directives for Blog
 * Essential directives with minimal duplication
 */

import Alpine from 'alpinejs';

/**
 * Loading state directive
 */
export function initBlogLoadingDirective() {
  Alpine.directive('blog-loading', (el, { expression }, { evaluateLater, effect }) => {
    const evaluate = evaluateLater(expression);

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
 * Pagination directive - handles all pagination navigation
 */
export function initBlogPaginateDirective() {
  Alpine.directive('blog-paginate', (el, { modifiers, expression }, { evaluateLater, effect }) => {
    const evaluate = expression ? evaluateLater(expression) : null;

    const handleClick = e => {
      e.preventDefault();
      const store = Alpine.store('blog');
      if (!store || store.loading) return;

      if (modifiers.includes('next')) {
        store.goToNextPage();
      } else if (modifiers.includes('prev')) {
        store.goToPrevPage();
      } else if (modifiers.includes('first')) {
        store.goToFirstPage();
      } else if (modifiers.includes('last')) {
        store.goToLastPage();
      } else if (expression && evaluate) {
        evaluate(page => store.goToPage(parseInt(page)));
      }
    };

    el.addEventListener('click', handleClick);

    // Update disabled state
    effect(() => {
      const store = Alpine.store('blog');
      if (!store) return;

      let disabled = store.loading;
      
      if (modifiers.includes('next')) disabled = disabled || !store.pagination.hasNext;
      else if (modifiers.includes('prev')) disabled = disabled || !store.pagination.hasPrev;
      else if (modifiers.includes('first')) disabled = disabled || store.pagination.currentPage === 1;
      else if (modifiers.includes('last')) disabled = disabled || store.pagination.currentPage === store.pagination.totalPages;

      el.classList.toggle('disabled', disabled);
      el.setAttribute('aria-disabled', disabled);
    });
  });
}

/**
 * Navigation directive - handles category, search, and sort changes
 */
export function initBlogNavigateDirective() {
  Alpine.directive('blog-navigate', (el, { modifiers, expression }, { evaluateLater, effect }) => {
    const evaluate = evaluateLater(expression);

    el.addEventListener('click', e => {
      e.preventDefault();
      const store = Alpine.store('blog');
      if (!store || store.loading) return;

      evaluate(value => {
        if (modifiers.includes('category')) {
          store.setCategory(value);
        } else if (modifiers.includes('search')) {
          store.setSearch(value);
        } else if (modifiers.includes('sort')) {
          store.setSort(value);
        } else if (typeof value === 'object') {
          store.navigate(value);
        }
      });
    });
  });
}

/**
 * Initialize all directives
 */
export function initBlogDirectives() {
  initBlogLoadingDirective();
  initBlogPaginateDirective();
  initBlogNavigateDirective();
}
