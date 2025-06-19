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
    };
  });
}
