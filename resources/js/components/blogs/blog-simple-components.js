/**
 * Simplified Blog Components
 * Replaces complex blog-page-component with minimal x-data components
 */

import Alpine from 'alpinejs';
import debounce from 'lodash.debounce';
import UIHelpers from '../../validation/core/ui-helpers.js';

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
 * Simple blog search component - pure proxy to store
 */
export function initBlogSearchComponent() {
  Alpine.data('blogSearchComponent', () => ({
    // Initialize component
    init() {
      // Create debounced search function that delegates to store
      this.debouncedSearchFn = debounce(query => {
        this.handleSearch(query);
      }, 300);

      console.log('Blog search component initialized');
    },

    // Computed properties - pure proxies to store
    get searchQuery() {
      return this.$store.blog?.filters?.search || '';
    },

    get isLoading() {
      return this.$store.blog?.loading || false;
    },

    get hasActiveSearch() {
      return this.searchQuery && this.searchQuery.length > 0;
    },

    get searchStatus() {
      const store = this.$store.blog;
      if (!store) return '';

      if (this.isLoading) {
        return 'Поиск...';
      }

      if (this.hasActiveSearch) {
        const count = store.stats?.currentCount || 0;
        return `Найдено: ${count} статей`;
      }

      return '';
    },

    // Simple handlers - delegate to store
    handleSearch(query) {
      const store = this.$store.blog;
      if (!store) return;

      const trimmedQuery = query ? query.trim() : '';

      if (trimmedQuery) {
        store.setSearch(trimmedQuery);
      } else {
        store.clearSearch();
      }
    },

    // Debounced search for input events
    debouncedSearch() {
      const query = this.$refs.searchInput?.value || '';
      this.debouncedSearchFn(query);
    },

    // Handle search on Enter key
    handleSearchEnter() {
      this.debouncedSearchFn.cancel();
      const query = this.$refs.searchInput?.value || '';
      this.handleSearch(query);
    },

    // Clear search - simple proxy
    clearSearch() {
      const store = this.$store.blog;
      if (store && typeof store.clearSearch === 'function') {
        store.clearSearch();
      }

      // Clear UI errors
      const searchInput = this.$refs.searchInput;
      if (searchInput) {
        UIHelpers.clearFieldError(searchInput);
      }
    },

    // Sorting functionality
    get currentSort() {
      return this.$store.blog?.filters?.sort || 'latest';
    },

    get currentDirection() {
      return this.$store.blog?.filters?.direction || 'desc';
    },

    get isPopularSortActive() {
      return this.currentSort === 'popular';
    },

    get isViewsSortActive() {
      return this.currentSort === 'views';
    },

    // Toggle sorting direction for current sort type
    toggleSortDirection(sortType) {
      const store = this.$store.blog;
      if (!store || this.isLoading) return;

      // If clicking the same sort type, toggle direction
      if (this.currentSort === sortType) {
        const newDirection = this.currentDirection === 'desc' ? 'asc' : 'desc';
        store.setSort(sortType, newDirection);
      } else {
        // If different sort type, set with default direction
        store.setSort(sortType, 'desc');
      }
    },

    // Set specific sort with direction
    setSort(sortType, direction = 'desc') {
      const store = this.$store.blog;
      if (!store || this.isLoading) return;

      store.setSort(sortType, direction);
    },

    // Get CSS classes for sort button
    // getSortButtonClasses(sortType) {
    //   // const baseClasses = 'w-100 btn _flex _medium sorting-btn';
    //   const isActive = this.currentSort === sortType;
    //   const isAsc = this.currentDirection === 'asc';

    //   if (isActive && isAsc) {
    //     return `asc`;
    //   } else if (isActive) {
    //     return ``;
    //   }

    //   return baseClasses;
    // },
  }));
}

/**
 * Initialize all simple components
 */
export function initSimpleBlogComponents() {
  initSimpleBlogPage();
  initSimpleBlogPagination();
  initBlogSearchComponent();
}
