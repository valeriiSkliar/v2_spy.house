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
 * Директива для фильтров блога
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
        }
      });
    });
  });
}

/**
 * Инициализация всех директив
 */
export function initBlogDirectives() {
  initBlogLoadingDirective();
  initBlogFilterDirective();
}
