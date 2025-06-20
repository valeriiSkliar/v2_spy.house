/**
 * Blog State Store
 * Centralized state management for blog functionality
 * Replaces global variables and provides reactive state
 */
import loader, { hideInElement, showInElement } from '../components/loader.js';
import { ValidationMethods } from '../validation/validation-constants.js';

export const blogStore = {
  // Loading state
  loading: false,
  currentRequest: null,
  retryCount: 0,

  // Loader management
  currentInlineLoader: null,
  showFullscreenLoader: false,

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

    // Setup URL listeners directly in store
    this.setupUrlListeners();
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
  setLoading(isLoading, options = {}) {
    const { useFullscreenLoader = true, container = null } = options;

    this.loading = isLoading;
    console.log('Loading state changed:', isLoading, options);

    if (isLoading) {
      if (useFullscreenLoader) {
        this.showLoader();
      } else if (container) {
        this.showInlineLoader(container);
      }
    } else {
      this.hideAllLoaders();
    }
  },

  // Centralized loader management methods
  showLoader() {
    this.showFullscreenLoader = true;
    loader.show();
    console.log('Fullscreen loader shown');
  },

  hideLoader() {
    this.showFullscreenLoader = false;
    loader.hide();
    console.log('Fullscreen loader hidden');
  },

  showInlineLoader(container) {
    // Hide any existing inline loader first
    this.hideInlineLoader();

    if (container) {
      this.currentInlineLoader = showInElement(container);
      console.log('Inline loader shown in container:', container);
    }
  },

  hideInlineLoader() {
    if (this.currentInlineLoader) {
      hideInElement(this.currentInlineLoader);
      this.currentInlineLoader = null;
      console.log('Inline loader hidden');
    }
  },

  hideAllLoaders() {
    this.hideLoader();
    this.hideInlineLoader();
    console.log('All loaders hidden');
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

    // Dispatch custom event to notify Manager about state change
    // This maintains separation: Store manages state, Manager handles content loading
    document.dispatchEvent(
      new CustomEvent('blog:state-changed', {
        detail: {
          source: 'popstate',
          filters: this.filters,
          timestamp: Date.now(),
        },
      })
    );
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

  // REMOVED: Navigation methods moved to Manager for better separation of concerns
  // All navigation logic (goToPage, setSearch, setCategory, etc.) is now in BlogAjaxManager

  buildRequestURL(baseUrl) {
    const requestUrl = new URL(baseUrl, window.location.origin);

    // Add filter parameters
    this.filterParams.forEach((value, key) => {
      requestUrl.searchParams.set(key, value);
    });

    return requestUrl.toString();
  },

  // REMOVED: Navigation and content loading methods moved to Manager
  // Methods like navigateToState, handleRedirectResponse, cleanRedirect
  // are now handled by Manager for better separation of concerns

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

  // NEW: Setup URL listeners - centralized URL event handling
  setupUrlListeners() {
    // Listen for browser back/forward navigation
    window.addEventListener('popstate', event => {
      console.log('Popstate event detected, updating blog state via store');
      this.handlePopState();
    });

    // Listen for hash changes (secondary navigation)
    window.addEventListener('hashchange', () => {
      console.log('Hash change detected');
      // Could handle anchor navigation here if needed
    });

    // When page is about to unload, save scroll position
    window.addEventListener('beforeunload', () => {
      const position = window.pageYOffset || document.documentElement.scrollTop;
      sessionStorage.setItem('blog_scroll_position', position.toString());
    });

    // When user returns to tab, check if URL state is in sync
    document.addEventListener('visibilitychange', () => {
      if (!document.hidden) {
        if (!this.isStateInSync()) {
          console.log('Page became visible, syncing state with URL');
          this.updateFromURL();
        }
      }
    });
  },

  // NEW: Restore state from URL with scroll position
  restoreStateFromURL() {
    console.log('Restoring blog state from URL...');

    try {
      // Check if URL has any blog-related parameters
      const urlParams = new URLSearchParams(window.location.search);
      const hasFilters =
        urlParams.has('page') ||
        urlParams.has('category') ||
        urlParams.has('search') ||
        urlParams.has('sort') ||
        urlParams.has('direction');

      if (hasFilters) {
        console.log('URL contains filters, restoring state...');
        this.initFromURL();

        // Restore scroll position if available
        setTimeout(() => {
          const savedPosition = sessionStorage.getItem('blog_scroll_position');
          if (savedPosition) {
            const position = parseInt(savedPosition);
            window.scrollTo({ top: position, behavior: 'smooth' });
          }
        }, 500); // Delay to allow content loading

        return true;
      }
    } catch (error) {
      console.error('Error restoring from URL:', error);
    }

    return false;
  },

  // NEW: Force state synchronization (emergency method) - pure state operation
  forceSyncState() {
    console.log('Force syncing blog state...');
    this.updateFromURL();
    // Content reload should be triggered by Manager, not Store
  },

  // Validation methods
  validateFilters() {
    const { page, search, category } = this.filters;

    // Validate page number
    if (page < 1 || page > 1000) return false;

    // Use centralized search validation
    if (search) {
      const searchValidation = ValidationMethods.validateBlogSearch(search);
      if (!searchValidation.isValid) return false;
    }

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

  // STATE API - Pure state management interface (no business logic)
  // This API provides controlled access to store state
  // Usage: store.stateAPI.methodName() or this.$blog.state.methodName()
  stateAPI: {
    // State getters - read-only access to state
    getLoading() {
      return blogStore.loading;
    },

    getFilters() {
      return { ...blogStore.filters };
    },

    getArticles() {
      return [...blogStore.articles];
    },

    getCategories() {
      return [...blogStore.categories];
    },

    getPagination() {
      return { ...blogStore.pagination };
    },

    getStats() {
      return { ...blogStore.stats };
    },

    getCurrentCategory() {
      return blogStore.currentCategory;
    },

    // State setters - controlled state mutation
    setLoading(isLoading, options = {}) {
      return blogStore.setLoading(isLoading, options);
    },

    setFilters(newFilters) {
      return blogStore.setFilters(newFilters);
    },

    setArticles(articles) {
      return blogStore.setArticles(articles);
    },

    setCategories(categories) {
      return blogStore.setCategories(categories);
    },

    setPagination(paginationData) {
      return blogStore.setPagination(paginationData);
    },

    setStats(newStats) {
      return blogStore.setStats(newStats);
    },

    setCurrentCategory(category) {
      return blogStore.setCurrentCategory(category);
    },

    // Computed state getters
    isFirstPage() {
      return blogStore.isFirstPage;
    },

    isLastPage() {
      return blogStore.isLastPage;
    },

    hasResults() {
      return blogStore.hasResults;
    },

    isFiltered() {
      return blogStore.isFiltered;
    },

    hasActiveSearch() {
      return blogStore.hasActiveSearch;
    },

    // State validation
    validateCurrentState() {
      return blogStore.validateFilters();
    },

    // Reset methods
    resetState() {
      return blogStore.resetState();
    },

    resetFilters() {
      return blogStore.resetFilters();
    },
  },

  // PURE URL API - URL state management only (no navigation logic)
  // This API handles only URL synchronization and state restoration
  // Navigation logic moved to Manager for better separation
  // Usage: store.urlAPI.methodName() or this.$blog.url.methodName()
  urlAPI: {
    // Get current URL state
    getCurrentUrl() {
      return blogStore.getCurrentURL();
    },

    // Check if state is synced with URL
    isStateSynced() {
      return blogStore.isStateInSync();
    },

    // Update URL with current state (pure URL operation)
    updateUrl(pushState = true) {
      return blogStore.updateURL(pushState);
    },

    // Restore state from current URL (pure state operation)
    restoreFromUrl() {
      return blogStore.restoreStateFromURL();
    },

    // Force synchronization (pure state operation)
    forceSync() {
      return blogStore.forceSyncState();
    },

    // REMOVED: Navigation methods moved to Manager
    // handleRedirect, navigateToState, cleanRedirect are now in Manager
  },
};

// Export function to register store with Alpine after initialization
export function initBlogStore(Alpine) {
  Alpine.store('blog', blogStore);

  // Add simplified $blog magic method with clear API separation
  Alpine.magic('blog', () => ({
    // Pure state access via State API
    state: Alpine.store('blog').stateAPI,

    // Quick access to read-only state (for convenience)
    loading: () => Alpine.store('blog').loading,
    filters: () => Alpine.store('blog').filters,
    articles: () => Alpine.store('blog').articles,
    pagination: () => Alpine.store('blog').pagination,
    hasActiveSearch: () => Alpine.store('blog').hasActiveSearch,
    searchStats: () => Alpine.store('blog').searchStats,

    // URL operations (pure state/URL sync)
    url: Alpine.store('blog').urlAPI,

    // Operations API access - delegate to Manager
    operations: () => {
      if (window.blogAjaxManager && window.blogAjaxManager.operationsAPI) {
        return window.blogAjaxManager.operationsAPI;
      }
      console.warn('Blog Manager operations not available');
      return {};
    },
  }));

  // Initialize store after registration
  blogStore.init();
}
