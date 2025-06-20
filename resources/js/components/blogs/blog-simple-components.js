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
      if (data.currentCategory) store.setCurrentCategory(data.currentCategory);

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

        // Синхронизируем filters с server data
        if (data.filters) {
          const updatedFilters = { ...store.filters };
          if (data.filters.page !== currentPage) updatedFilters.page = currentPage;
          if (data.filters.category !== undefined) updatedFilters.category = data.filters.category;
          if (data.filters.search !== undefined) updatedFilters.search = data.filters.search;
          if (data.filters.sort !== undefined) updatedFilters.sort = data.filters.sort;
          if (data.filters.direction !== undefined)
            updatedFilters.direction = data.filters.direction;

          store.setFilters(updatedFilters);
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

    // Navigation methods - delegate to Manager via operations API
    goToPage(page) {
      return this.$blog.operations()?.goToPage(page) || false;
    },
    setCategory(slug) {
      return this.$blog.operations()?.setCategory(slug) || false;
    },
    setSearch(query) {
      return this.$blog.operations()?.setSearch(query) || false;
    },
    clearFilters() {
      return this.$blog.operations()?.clearFilters() || false;
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

    // Navigation methods - delegate to Manager via operations API
    goToPage(page) {
      return this.$blog.operations()?.goToPage(page) || false;
    },
    goToNext() {
      return this.$blog.operations()?.goToNextPage() || false;
    },
    goToPrev() {
      return this.$blog.operations()?.goToPrevPage() || false;
    },
    goToFirst() {
      return this.$blog.operations()?.goToFirstPage() || false;
    },
    goToLast() {
      return this.$blog.operations()?.goToLastPage() || false;
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

    // Simple handlers - delegate to Manager operations API
    handleSearch(query) {
      const operations = this.$blog.operations();
      if (!operations) return;

      const trimmedQuery = query ? query.trim() : '';

      // Validate minimum length to match server-side validation (min:3)
      if (trimmedQuery && trimmedQuery.length < 3) {
        console.log('Search query too short, ignoring:', trimmedQuery);
        return; // Don't proceed with search if less than 3 characters
      }

      if (trimmedQuery) {
        operations.setSearch(trimmedQuery);
      } else {
        operations.clearSearch();
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

    // Clear search - delegate to Manager
    clearSearch() {
      const operations = this.$blog.operations();
      if (operations) {
        operations.clearSearch();
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

    // Toggle sorting direction for current sort type - delegate to Manager
    toggleSortDirection(sortType) {
      const operations = this.$blog.operations();
      if (!operations || this.isLoading) return;

      // If clicking the same sort type, toggle direction
      if (this.currentSort === sortType) {
        const newDirection = this.currentDirection === 'desc' ? 'asc' : 'desc';
        operations.setSort(sortType, newDirection);
      } else {
        // If different sort type, set with default direction
        operations.setSort(sortType, 'desc');
      }
    },

    // Set specific sort with direction - delegate to Manager
    setSort(sortType, direction = 'desc') {
      const operations = this.$blog.operations();
      if (!operations || this.isLoading) return;

      operations.setSort(sortType, direction);
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
 * Simple blog categories component - handles category navigation
 */
export function initBlogCategoriesComponent() {
  Alpine.data('blogCategoriesComponent', () => ({
    // Initialize component
    init() {
      console.log('Blog categories component initialized');
    },

    // Computed properties - pure proxies to store
    get categories() {
      return this.$store.blog?.categories || [];
    },

    get currentCategory() {
      return this.$store.blog?.currentCategory || null;
    },

    get isLoading() {
      return this.$store.blog?.loading || false;
    },

    get activeCategory() {
      return this.$store.blog?.filters?.category || '';
    },

    // Check if category is currently selected
    isCategoryActive(categorySlug) {
      return this.activeCategory === categorySlug;
    },

    // Check if "All categories" is active
    isAllCategoriesActive() {
      return !this.activeCategory || this.activeCategory === '';
    },

    // Handle category selection - delegate to Manager
    selectCategory(categorySlug) {
      const operations = this.$blog.operations();
      if (!operations || this.isLoading) return;

      // If already selected, do nothing
      if (this.isCategoryActive(categorySlug)) {
        console.log('Category already selected:', categorySlug);
        return;
      }

      console.log('Selecting category:', categorySlug);
      operations.setCategory(categorySlug);
    },

    // Clear category selection (show all) - delegate to Manager
    clearCategory() {
      const operations = this.$blog.operations();
      if (!operations || this.isLoading) return;

      if (this.isAllCategoriesActive()) {
        console.log('All categories already selected');
        return;
      }

      console.log('Clearing category selection');
      operations.clearCategory();
    },

    // Get category display information
    getCategoryInfo(category) {
      return {
        id: category.id,
        name: category.name,
        slug: category.slug,
        postsCount: category.posts_count || 0,
        isActive: this.isCategoryActive(category.slug),
      };
    },

    // Get CSS classes for category link
    getCategoryClasses(categorySlug) {
      const baseClasses = 'category-link';
      const isActive = this.isCategoryActive(categorySlug);
      const isLoading = this.isLoading;

      return [baseClasses, isActive ? 'is-active' : '', isLoading ? 'is-loading' : '']
        .filter(Boolean)
        .join(' ');
    },

    // Get CSS classes for "All categories" link
    getAllCategoriesClasses() {
      const baseClasses = 'category-link all-categories';
      const isActive = this.isAllCategoriesActive();
      const isLoading = this.isLoading;

      return [baseClasses, isActive ? 'is-active' : '', isLoading ? 'is-loading' : '']
        .filter(Boolean)
        .join(' ');
    },
  }));
}

/**
 * Initialize all simple components
 */
export function initSimpleBlogComponents() {
  initSimpleBlogPage();
  initSimpleBlogPagination();
  initBlogSearchComponent();
  initBlogCategoriesComponent();
}
