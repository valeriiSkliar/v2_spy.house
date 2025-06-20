/**
 * Simplified Blog Components
 * Replaces complex blog-page-component with minimal x-data components
 */

import Alpine from 'alpinejs';

/**
 * Simple blog page data - minimal replacement for blog-page-component
 */
export function initSimpleBlogPage() {
  Alpine.data('blogPageSimple', (serverData = {}) => ({
    // Local state
    heroArticle: null,
    ajaxUrl: '',
    initialized: false,

    // Initialize from server data
    init() {
      if (serverData && typeof serverData === 'object') {
        this.initFromServerData(serverData);
      }
    },

    initFromServerData(data) {
      const store = Alpine.store('blog');
      if (!store) return;

      // Set basic data
      this.heroArticle = data.heroArticle || null;
      this.ajaxUrl = data.ajaxUrl || '';

      // Update store with server data
      if (data.articles) store.setArticles(data.articles);
      if (data.categories) store.setCategories(data.categories);
      if (data.popularPosts) store.setPopularPosts(data.popularPosts);

      // Set pagination and sync with filters.page
      if (data.totalPages || data.currentPage) {
        const currentPage = data.currentPage || 1;
        store.setPagination({
          currentPage: currentPage,
          totalPages: data.totalPages || 1,
          hasPagination: (data.totalPages || 1) > 1,
          hasNext: currentPage < (data.totalPages || 1),
          hasPrev: currentPage > 1,
        });

        // Синхронизируем filters.page с pagination.currentPage
        if (data.filters && data.filters.page !== currentPage) {
          store.setFilters({ ...store.filters, page: currentPage });
        }
      }

      // Set stats
      store.setStats({
        totalCount: data.totalCount || 0,
        currentCount: (data.articles ? data.articles.length : 0) + (this.heroArticle ? 1 : 0),
      });

      this.initialized = true;
    },

    // Proxy getters for template access
    get loading() {
      return Alpine.store('blog')?.loading || false;
    },
    get articles() {
      return Alpine.store('blog')?.articles || [];
    },
    get filters() {
      return Alpine.store('blog')?.filters || {};
    },
    get pagination() {
      return Alpine.store('blog')?.pagination || {};
    },
    get hasArticles() {
      return this.articles.length > 0 || !!this.heroArticle;
    },
    get showNoResults() {
      return !this.hasArticles && !this.loading;
    },

    // Navigation methods - delegate to store
    goToPage(page) {
      return Alpine.store('blog')?.goToPage(page);
    },
    setCategory(slug) {
      return Alpine.store('blog')?.setCategory(slug);
    },
    setSearch(query) {
      return Alpine.store('blog')?.setSearch(query);
    },
    clearFilters() {
      return Alpine.store('blog')?.resetFilters();
    },
  }));
}

/**
 * Simple blog pagination - minimal replacement for blog-pagination-component
 */
export function initSimpleBlogPagination() {
  Alpine.data('blogPaginationSimple', () => ({
    // Computed properties from store
    get currentPage() {
      return Alpine.store('blog')?.pagination?.currentPage || 1;
    },
    get totalPages() {
      return Alpine.store('blog')?.pagination?.totalPages || 1;
    },
    get hasNext() {
      return Alpine.store('blog')?.pagination?.hasNext || false;
    },
    get hasPrev() {
      return Alpine.store('blog')?.pagination?.hasPrev || false;
    },
    get loading() {
      return Alpine.store('blog')?.loading || false;
    },

    // Navigation methods - delegate to store
    goToPage(page) {
      return Alpine.store('blog')?.goToPage(page);
    },
    goToNext() {
      return Alpine.store('blog')?.goToNextPage();
    },
    goToPrev() {
      return Alpine.store('blog')?.goToPrevPage();
    },
    goToFirst() {
      return Alpine.store('blog')?.goToFirstPage();
    },
    goToLast() {
      return Alpine.store('blog')?.goToLastPage();
    },

    // Generate visible pages for pagination UI
    getVisiblePages() {
      const current = parseInt(this.currentPage) || 1;
      const total = parseInt(this.totalPages) || 1;
      const delta = 2;

      if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1);
      }

      const left = Math.max(1, current - delta);
      const right = Math.min(total, current + delta);
      const pages = [];

      if (left > 1) {
        pages.push(1);
        if (left > 2) pages.push('...');
      }

      for (let i = left; i <= right; i++) {
        pages.push(i);
      }

      if (right < total) {
        if (right < total - 1) pages.push('...');
        pages.push(total);
      }

      return pages;
    },
  }));
}

/**
 * Initialize all simple components
 */
export function initSimpleBlogComponents() {
  initSimpleBlogPage();
  initSimpleBlogPagination();
}
