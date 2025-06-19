/**
 * Blog State Store
 * Centralized state management for blog functionality
 * Replaces global variables and provides reactive state
 */
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
    // Persist filters whenever they change
    this.persistFilters();
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
    return this.filters.category || this.filters.search || this.filters.sort !== 'latest';
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

    this.setFilters({
      page: parseInt(urlParams.get('page')) || 1,
      category: urlParams.get('category') || '',
      search: urlParams.get('search') || '',
      sort: urlParams.get('sort') || 'latest',
      direction: urlParams.get('direction') || 'desc',
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

  clearSearch() {
    this.setFilters({
      ...this.filters,
      search: '',
      page: 1,
    });
  },

  clearCategory() {
    this.setFilters({
      ...this.filters,
      category: '',
      page: 1,
    });
  },

  // Validation methods
  validateFilters() {
    const { page, search, category } = this.filters;

    // Validate page number
    if (page < 1 || page > 1000) return false;

    // Validate search query
    if (search && (search.length > 255 || search.length < 1)) return false;

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
};

// Export function to register store with Alpine after initialization
export function initBlogStore(Alpine) {
  Alpine.store('blog', blogStore);
  // Initialize store after registration
  blogStore.init();
}
