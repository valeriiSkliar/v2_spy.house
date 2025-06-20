/**
 * Blog State Store
 * Centralized state management for blog functionality
 * Replaces global variables and provides reactive state
 */
import { ValidationMethods } from '../validation/validation-constants.js';
export const blogStore = {
  // Loading state
  loading: false,
  currentRequest: null,
  retryCount: 0,

  // Content data
  articles: [],
  categories: [],
  popularPosts: [],
  currentCategory: null,

  // Pagination
  pagination: {
    currentPage: 1,
    totalPages: 1,
    hasPagination: false,
    hasNext: false,
    hasPrev: false,
  },

  // Filters (will be made persistent after Alpine initialization)
  filters: {
    page: 1,
    category: '',
    search: '',
    sort: 'latest',
    direction: 'desc',
  },

  // UI state
  ui: {
    showSidebar: true,
    activeTab: 'all',
    scrollPosition: 0,
  },

  // Statistics
  stats: {
    totalCount: 0,
    currentCount: 0,
    loadTime: 0,
  },

  // Comments state
  comments: {
    list: [],
    loading: false,
    pagination: {
      currentPage: 1,
      totalPages: 1,
      hasPages: false,
    },
    replyMode: {
      active: false,
      parentId: null,
      authorName: '',
    },
    form: {
      submitting: false,
      content: '',
      errors: {},
    },
  },

  // Rating state
  rating: {
    current: 0,
    userRating: null,
    isRated: false,
    submitting: false,
    hasRated: false,
  },

  // Carousels state
  carousels: {
    alsowInteresting: {
      initialized: false,
      slides: [],
      loading: false,
      error: null,
    },
    readOften: {
      initialized: false,
      slides: [],
      loading: false,
      error: null,
    },
  },

  // Initialize method to set up persistence after Alpine is ready
  init() {
    // Load persisted filters from sessionStorage
    const persistedFilters = sessionStorage.getItem('blog_filters');
    if (persistedFilters) {
      try {
        this.filters = { ...this.filters, ...JSON.parse(persistedFilters) };
      } catch (e) {
        console.warn('Could not parse persisted blog filters:', e);
      }
    }

    // Initialize from URL on first load
    this.initFromURL();

    // NOTE: popstate listener is handled by app-blog.js to avoid conflicts
  },

  // Save filters to sessionStorage
  persistFilters() {
    try {
      sessionStorage.setItem('blog_filters', JSON.stringify(this.filters));
    } catch (e) {
      console.warn('Could not persist blog filters:', e);
    }
  },

  // Actions
  setLoading(state) {
    this.loading = state;
  },

  setCurrentRequest(request) {
    this.currentRequest = request;
  },

  setRetryCount(count) {
    this.retryCount = count;
  },

  setArticles(articles) {
    this.articles = articles;
  },

  setCategories(categories) {
    this.categories = categories;
  },

  setPopularPosts(popularPosts) {
    this.popularPosts = popularPosts;
  },

  setCurrentCategory(category) {
    this.currentCategory = category;
  },

  setPagination(paginationData) {
    this.pagination = {
      ...this.pagination,
      ...paginationData,
    };
  },

  setFilters(newFilters) {
    this.filters = {
      ...this.filters,
      ...newFilters,
    };

    // Синхронизируем pagination.currentPage с filters.page
    if (newFilters.page !== undefined) {
      this.pagination.currentPage = newFilters.page;
    }

    // Persist filters whenever they change
    this.persistFilters();

    // Update URL when filters change
    this.updateURL(true);
  },

  resetFilters() {
    this.filters = {
      page: 1,
      category: '',
      search: '',
      sort: 'latest',
      direction: 'desc',
    };
    // Persist reset filters
    this.persistFilters();

    // Update URL when filters are reset
    this.updateURL(true);
  },

  setUIState(newUIState) {
    this.ui = {
      ...this.ui,
      ...newUIState,
    };
  },

  setStats(newStats) {
    this.stats = {
      ...this.stats,
      ...newStats,
    };
  },

  // Computed properties
  get isFirstPage() {
    return this.pagination.currentPage === 1;
  },

  get isLastPage() {
    return this.pagination.currentPage === this.pagination.totalPages;
  },

  get hasResults() {
    return this.stats.currentCount > 0;
  },

  get isFiltered() {
    return !!(this.filters.category || this.filters.search || this.filters.sort !== 'latest');
  },

  get filterParams() {
    const params = new URLSearchParams();

    if (this.filters.page > 1) params.set('page', this.filters.page);
    if (this.filters.category) params.set('category', this.filters.category);
    if (this.filters.search) params.set('search', this.filters.search);
    if (this.filters.sort !== 'latest') params.set('sort', this.filters.sort);
    if (this.filters.direction !== 'desc') params.set('direction', this.filters.direction);

    return params;
  },

  // Utility methods
  updateFromURL() {
    const urlParams = new URLSearchParams(window.location.search);

    this.setFiltersFromURL({
      page: parseInt(urlParams.get('page')) || 1,
      category: urlParams.get('category') || '',
      search: urlParams.get('search') || '',
      sort: urlParams.get('sort') || 'latest',
      direction: urlParams.get('direction') || 'desc',
    });
  },

  // NEW: Initialize from URL without triggering URL update
  initFromURL() {
    const urlParams = new URLSearchParams(window.location.search);

    const urlFilters = {
      page: parseInt(urlParams.get('page')) || 1,
      category: urlParams.get('category') || '',
      search: urlParams.get('search') || '',
      sort: urlParams.get('sort') || 'latest',
      direction: urlParams.get('direction') || 'desc',
    };

    // Set filters without triggering URL update or persistence
    this.filters = { ...this.filters, ...urlFilters };

    console.log('Blog store initialized from URL:', urlFilters);
  },

  // NEW: Set filters from URL without triggering URL update
  setFiltersFromURL(newFilters) {
    this.filters = {
      ...this.filters,
      ...newFilters,
    };
    // Persist filters but don't update URL
    this.persistFilters();
  },

  // NEW: Handle browser back/forward navigation
  handlePopState() {
    console.log('Handling popstate event - updating from URL');
    this.updateFromURL();

    // Trigger content reload if AJAX manager is available
    if (
      window.blogAjaxManager &&
      typeof window.blogAjaxManager.loadFromCurrentState === 'function'
    ) {
      window.blogAjaxManager.loadFromCurrentState();
    }
  },

  // NEW: Update URL without triggering popstate
  updateURL(pushState = true) {
    const url = new URL(window.location);

    console.log('updateURL called with filters:', this.filters);

    // Clear existing blog-related parameters first
    url.searchParams.delete('page');
    url.searchParams.delete('category');
    url.searchParams.delete('search');
    url.searchParams.delete('sort');
    url.searchParams.delete('direction');

    // Add current filter values
    const params = this.filterParams;
    console.log('filterParams:', Array.from(params.entries()));

    params.forEach((value, key) => {
      url.searchParams.set(key, value);
    });

    console.log('Final URL before history update:', url.toString());

    // Create a simple, cloneable state object (avoid complex objects)
    const stateData = {
      blogFilters: {
        page: this.filters.page,
        category: this.filters.category || '',
        search: this.filters.search || '',
        sort: this.filters.sort || 'latest',
        direction: this.filters.direction || 'desc',
      },
      timestamp: Date.now(),
    };

    // Update browser history
    if (pushState) {
      window.history.pushState(stateData, '', url.toString());
    } else {
      window.history.replaceState(stateData, '', url.toString());
    }

    console.log('URL updated:', url.toString());
    console.log('Current window.location.href:', window.location.href);
  },

  // NEW: Get current URL with filters
  getCurrentURL() {
    const url = new URL(window.location);

    // Clear existing params
    url.searchParams.delete('page');
    url.searchParams.delete('category');
    url.searchParams.delete('search');
    url.searchParams.delete('sort');
    url.searchParams.delete('direction');

    // Add current filters
    this.filterParams.forEach((value, key) => {
      url.searchParams.set(key, value);
    });

    return url.toString();
  },

  clearSearch() {
    console.log('Clearing search from store');
    this.navigate({
      ...this.filters,
      search: '',
      page: 1,
    });
  },

  clearCategory() {
    this.navigate({
      ...this.filters,
      category: '',
      page: 1,
    });
  },

  buildRequestURL(baseUrl) {
    const requestUrl = new URL(baseUrl, window.location.origin);

    // Add filter parameters
    this.filterParams.forEach((value, key) => {
      requestUrl.searchParams.set(key, value);
    });

    return requestUrl.toString();
  },

  resetToPage(page = 1) {
    this.setFilters({
      ...this.filters,
      page,
    });
  },

  // Unified navigation method - consolidates navigation from all components
  navigate(filters) {
    // Update filters without URL update (to avoid double update)
    this.filters = {
      ...this.filters,
      ...filters,
    };
    this.persistFilters();

    // Update URL once
    this.updateURL(true);

    // Load content
    this.loadContent();
  },

  // Navigation helper methods
  goToPage(page) {
    const targetPage = parseInt(page);
    if (targetPage < 1 || targetPage > this.pagination.totalPages || this.loading) {
      return false;
    }
    this.navigate({ ...this.filters, page: targetPage });
    return true;
  },

  goToNextPage() {
    if (this.pagination.hasNext && !this.loading) {
      return this.goToPage(this.pagination.currentPage + 1);
    }
    return false;
  },

  goToPrevPage() {
    if (this.pagination.hasPrev && !this.loading) {
      return this.goToPage(this.pagination.currentPage - 1);
    }
    return false;
  },

  goToFirstPage() {
    if (this.pagination.currentPage > 1 && !this.loading) {
      return this.goToPage(1);
    }
    return false;
  },

  goToLastPage() {
    if (this.pagination.currentPage < this.pagination.totalPages && !this.loading) {
      return this.goToPage(this.pagination.totalPages);
    }
    return false;
  },

  setCategory(categorySlug) {
    if (this.loading) return false;
    this.navigate({ ...this.filters, category: categorySlug, page: 1 });
    return true;
  },

  setSearch(searchQuery) {
    if (this.loading) {
      console.warn('Search blocked: content is loading');
      return false;
    }

    const query = searchQuery ? searchQuery.trim() : '';

    // Search validation is handled by middleware, basic client-side check is sufficient

    console.log('Setting search query:', query);

    this.navigate({
      ...this.filters,
      search: query,
      page: 1,
      // Сбрасываем категорию при поиске, если нужно
      // category: query ? '' : this.filters.category
    });

    return true;
  },

  setSort(sortType, direction = 'desc') {
    if (this.loading) return false;
    this.navigate({ ...this.filters, sort: sortType, direction, page: 1 });
    return true;
  },

  // Content loading method
  loadContent() {
    if (
      window.blogAjaxManager &&
      typeof window.blogAjaxManager.loadFromCurrentState === 'function'
    ) {
      window.blogAjaxManager.loadFromCurrentState();
    }
  },

  // NEW: Navigate to specific URL state
  navigateToState(filters, replaceState = false) {
    // Update filters
    this.filters = { ...this.filters, ...filters };
    this.persistFilters();

    // Update URL
    this.updateURL(!replaceState);

    // Trigger content reload
    this.loadContent();
  },

  // NEW: Handle redirect responses from server
  handleRedirectResponse(redirectUrl) {
    console.log('Store handling redirect to:', redirectUrl);

    const url = new URL(redirectUrl);
    const urlParams = new URLSearchParams(url.search);

    // Extract filters from redirect URL
    const redirectFilters = {
      category: urlParams.get('category') || '',
      page: parseInt(urlParams.get('page')) || 1,
      search: urlParams.get('search') || '',
      sort: urlParams.get('sort') || 'latest',
      direction: urlParams.get('direction') || 'desc',
    };

    // Update filters without triggering URL update (since we're already updating)
    this.filters = { ...this.filters, ...redirectFilters };
    this.persistFilters();

    // Update browser history
    window.history.pushState({ 
      blogFilters: redirectFilters,
      timestamp: Date.now()
    }, '', redirectUrl);

    // Trigger content reload
    this.loadContent();
  },

  // NEW: Clean redirect (clear all parameters)
  cleanRedirect() {
    console.log('Store performing clean redirect');
    
    // Reset filters to defaults
    this.resetFilters();
    
    // Create clean URL
    const cleanUrl = new URL(window.location.pathname, window.location.origin);
    window.history.pushState({}, '', cleanUrl.toString());
    
    // Reload page
    window.location.reload();
  },

  // NEW: Check if current state matches URL
  isStateInSync() {
    const urlParams = new URLSearchParams(window.location.search);

    const urlFilters = {
      page: parseInt(urlParams.get('page')) || 1,
      category: urlParams.get('category') || '',
      search: urlParams.get('search') || '',
      sort: urlParams.get('sort') || 'latest',
      direction: urlParams.get('direction') || 'desc',
    };

    return JSON.stringify(urlFilters) === JSON.stringify(this.filters);
  },

  // Validation methods
  validateFilters() {
    const { page, search, category } = this.filters;

    // Validate page number
    if (page < 1 || page > 1000) return false;

    // Search validation is handled by middleware, basic client-side check for length is sufficient
    if (search && (search.trim().length < 2 || search.trim().length > 255)) return false;

    // Validate category slug
    if (category && !/^[a-zA-Z0-9\-_]*$/.test(category)) return false;

    return true;
  },

  // State reset methods
  resetState() {
    this.loading = false;
    this.currentRequest = null;
    this.retryCount = 0;
    this.articles = [];
    this.categories = [];
    this.popularPosts = [];
    this.currentCategory = null;
    this.pagination = {
      currentPage: 1,
      totalPages: 1,
      hasPagination: false,
      hasNext: false,
      hasPrev: false,
    };
    this.stats = {
      totalCount: 0,
      currentCount: 0,
      loadTime: 0,
    };
    // Reset new states
    this.resetCommentsState();
    this.resetRatingState();
    this.resetCarouselsState();
  },

  // Comments actions
  setComments(comments) {
    this.comments.list = comments;
  },

  setCommentsLoading(loading) {
    this.comments.loading = loading;
  },

  setCommentsPagination(paginationData) {
    this.comments.pagination = {
      ...this.comments.pagination,
      ...paginationData,
    };
  },

  setReplyMode(parentId, authorName) {
    this.comments.replyMode = {
      active: true,
      parentId,
      authorName,
    };
  },

  clearReplyMode() {
    this.comments.replyMode = {
      active: false,
      parentId: null,
      authorName: '',
    };
  },

  setCommentForm(formData) {
    this.comments.form = {
      ...this.comments.form,
      ...formData,
    };
  },

  setCommentFormSubmitting(submitting) {
    this.comments.form.submitting = submitting;
  },

  resetCommentsState() {
    this.comments = {
      list: [],
      loading: false,
      pagination: {
        currentPage: 1,
        totalPages: 1,
        hasPages: false,
      },
      replyMode: {
        active: false,
        parentId: null,
        authorName: '',
      },
      form: {
        submitting: false,
        content: '',
        errors: {},
      },
    };
  },

  // Rating actions
  setRating(rating, userRating = null, isRated = false) {
    this.rating = {
      current: rating,
      userRating,
      isRated,
      submitting: false,
      hasRated: isRated,
    };
  },

  setRatingSubmitting(submitting) {
    this.rating.submitting = submitting;
  },

  updateRating(newRating, userRating = null) {
    this.rating.current = newRating;
    if (userRating !== null) {
      this.rating.userRating = userRating;
      this.rating.isRated = true;
      this.rating.hasRated = true;
    }
    this.rating.submitting = false;
  },

  resetRatingState() {
    this.rating = {
      current: 0,
      userRating: null,
      isRated: false,
      submitting: false,
      hasRated: false,
    };
  },

  // Carousels actions
  setCarouselState(carouselName, state) {
    if (this.carousels[carouselName]) {
      this.carousels[carouselName] = {
        ...this.carousels[carouselName],
        ...state,
      };
    }
  },

  setCarouselInitialized(carouselName, initialized) {
    if (this.carousels[carouselName]) {
      this.carousels[carouselName].initialized = initialized;
    }
  },

  setCarouselLoading(carouselName, loading) {
    if (this.carousels[carouselName]) {
      this.carousels[carouselName].loading = loading;
    }
  },

  setCarouselError(carouselName, error) {
    if (this.carousels[carouselName]) {
      this.carousels[carouselName].error = error;
    }
  },

  resetCarouselsState() {
    this.carousels = {
      alsowInteresting: {
        initialized: false,
        slides: [],
        loading: false,
        error: null,
      },
      readOften: {
        initialized: false,
        slides: [],
        loading: false,
        error: null,
      },
    };
  },

  // Computed properties for new states
  get hasComments() {
    return this.comments.list.length > 0;
  },

  get isCommentsLoading() {
    return this.comments.loading;
  },

  get isReplyMode() {
    return this.comments.replyMode.active;
  },

  get canRate() {
    return !this.rating.isRated && !this.rating.submitting;
  },

  get isRatingSubmitting() {
    return this.rating.submitting;
  },

  get areCarouselsReady() {
    return this.carousels.alsowInteresting.initialized && this.carousels.readOften.initialized;
  },

  // NEW: Check if search is active
  get hasActiveSearch() {
    return this.filters.search && this.filters.search.length > 0;
  },

  // NEW: Get search statistics
  get searchStats() {
    if (!this.hasActiveSearch) return null;

    return {
      query: this.filters.search,
      totalResults: this.stats.totalCount,
      currentResults: this.stats.currentCount,
      hasResults: this.stats.currentCount > 0,
    };
  },
};

// Export function to register store with Alpine after initialization
export function initBlogStore(Alpine) {
  Alpine.store('blog', blogStore);

  // Add simplified $blog magic method
  Alpine.magic('blog', () => ({
    store: Alpine.store('blog'),
    // Direct access to navigation methods
    goToPage: page => Alpine.store('blog').goToPage(page),
    setCategory: slug => Alpine.store('blog').setCategory(slug),
    setSearch: query => Alpine.store('blog').setSearch(query),
    clearSearch: () => Alpine.store('blog').clearSearch(),
    setSort: (type, direction) => Alpine.store('blog').setSort(type, direction),
    // Quick access to state
    loading: () => Alpine.store('blog').loading,
    filters: () => Alpine.store('blog').filters,
    articles: () => Alpine.store('blog').articles,
    pagination: () => Alpine.store('blog').pagination,
    // Search-specific helpers
    hasActiveSearch: () => Alpine.store('blog').hasActiveSearch,
    searchStats: () => Alpine.store('blog').searchStats,
  }));

  // Initialize store after registration
  blogStore.init();
}
