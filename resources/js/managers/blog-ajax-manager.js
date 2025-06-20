import Alpine from 'alpinejs';
import { blogAPI } from '../components/fetcher/ajax-fetcher';

/**
 * Blog AJAX Manager - Thin coordination layer over blogAPI
 *
 * AFTER REFACTORING: This manager is now a lightweight coordinator that:
 * - Delegates all AJAX operations to blogAPI (centralized in ajax-fetcher.js)
 * - Manages state synchronization with Alpine store
 * - Coordinates DOM updates and component reinitialisation
 * - All error handling is centralized in blogAPI.blogErrorHandler
 * - All URL operations delegated to store.urlAPI
 *
 * This follows the pattern: blogAPI (AJAX) -> manager (coordination) -> store (state)
 */
export class BlogAjaxManager {
  constructor() {
    // Initialize store reference, will be set when Alpine is ready
    this.store = null;

    // Try to get store if Alpine is already available
    this.tryInitStore();
  }

  /**
   * Try to initialize store reference
   */
  tryInitStore() {
    if (typeof Alpine !== 'undefined' && Alpine.store) {
      try {
        this.store = Alpine.store('blog');
      } catch (e) {
        // Store not yet registered, will be set later
        this.store = null;
      }
    }
  }

  /**
   * Get store with fallback handling
   */
  getStore() {
    if (!this.store && typeof Alpine !== 'undefined' && Alpine.store) {
      this.tryInitStore();
    }
    return this.store;
  }

  /**
   * Main content loading method - thin layer over blogAPI
   * Coordinates between blogAPI and store state management
   */
  async loadContent(container, url, options = {}) {
    const { scrollToTop = false, validateParams = true, useInlineLoader = true } = options;

    const store = this.getStore();
    if (!store) {
      console.warn('Store not available, falling back to basic functionality');
      return;
    }

    // Prevent multiple simultaneous requests
    if (store.loading && store.currentRequest) {
      store.currentRequest.abort();
    }

    // Validate parameters using store
    if (validateParams && !store.validateFilters()) {
      console.warn('Invalid request parameters detected, redirecting to clean state');
      this.cleanRedirect();
      return;
    }

    console.log('Loading blog content...', { url, options });

    // Set loading state with centralized loader management
    const loaderOptions = useInlineLoader
      ? { useFullscreenLoader: false, container }
      : { useFullscreenLoader: true };
    store.setLoading(true, loaderOptions);

    // Build request URL using store
    const requestUrl = store.buildRequestURL(url);

    try {
      // Use centralized blogAPI method with full error handling
      const response = await blogAPI.loadBlogContent(requestUrl, {
        container,
        validateParams,
      });

      console.log('AJAX response received:', response);

      // Update store with performance metrics
      if (response.loadTime) {
        store.setStats({ loadTime: response.loadTime });
      }

      // Handle redirect response
      if (response.redirect) {
        this.handleRedirectResponseFromServer(response, container, url, options);
        return;
      }

      // Handle validation error
      if (response.error) {
        console.error('Validation error:', response.error);
        this.cleanRedirect();
        return;
      }

      // Update content and state - this is the main coordination logic
      this.updatePageContent(response, container, scrollToTop);
    } catch (error) {
      // Error handling is now centralized in blogAPI
      // Only handle coordination issues here
      if (!error.handled) {
        console.error('Unhandled error in blog content loading:', error);
      }
    } finally {
      store.setLoading(false);
      store.setCurrentRequest(null);
    }
  }

  /**
   * Handle redirect response - now uses Manager's own redirect handling
   */
  handleRedirectResponseFromServer(data, container, url, options) {
    // Use Manager's own redirect handling (moved from store)
    this.handleRedirectResponse(data.url);

    // Update category sidebar state after redirect
    const store = this.getStore();
    if (store) {
      this.updateCategorySidebarState(store.filters.category);
    }
  }

  /**
   * Update page content and store state
   * Preserves existing DOM manipulation principles
   */
  updatePageContent(data, container, scrollToTop) {
    try {
      const store = this.getStore();

      // Update main content (preserve existing DOM approach)
      if (data.html) {
        container.innerHTML = data.html;
      }

      // Update store data if available
      if (store) {
        store.setStats({
          currentCount: data.count || 0,
          totalCount: data.totalCount || 0,
        });

        store.setPagination({
          currentPage: data.currentPage || 1,
          totalPages: data.totalPages || 1,
          hasPagination: data.hasPagination || false,
          hasNext: data.currentPage < data.totalPages,
          hasPrev: data.currentPage > 1,
        });

        if (data.currentCategory) {
          store.setCurrentCategory(data.currentCategory);
        }
      }

      // Update category sidebar state based on current filters
      this.updateCategorySidebarState(store.filters.category);

      // Update container classes (preserve existing approach)
      if (data.count === 0 && data.totalCount === 0) {
        container.classList.add('blog-list__no-results');
      } else {
        container.classList.remove('blog-list__no-results');
      }

      // Update pagination content (preserve existing DOM approach)
      // this.updatePaginationContent(data);

      // Reinitialize components (preserve existing approach)
      this.reinitializeComponents();

      // Scroll to top if needed (preserve existing behavior)
      if (scrollToTop) {
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }

      // Update URL for SEO (preserve existing approach)
      this.updateUrlForSEO(data);
    } catch (error) {
      console.error('Error updating page content:', error);
      // Use centralized error handling from blogAPI
      if (container) {
        container.innerHTML = blogAPI.blogErrorHandler.generateErrorHtml(error);
      }
    }
  }

  /**
   * Update pagination content (preserve existing DOM approach)
   */
  updatePaginationContent(data) {
    const paginationContainer = document.getElementById('blog-pagination-container');
    if (!paginationContainer) return;

    if (data.hasPagination && data.pagination) {
      paginationContainer.innerHTML = data.pagination;
      paginationContainer.style.display = 'block';
    } else {
      paginationContainer.innerHTML = '';
      paginationContainer.style.display = 'none';
    }
  }

  /**
   * Reinitialize components (preserve existing approach)
   */
  reinitializeComponents() {
    try {
      // Destroy existing slick carousels
      this.destroyExistingCarousels();

      // Reinitialize pagination click handlers
      this.reinitializePagination();

      // Reinitialize carousels with delay
      setTimeout(() => {
        // Import and call carousel initialization functions
        import('../components/blogs')
          .then(({ initAlsowInterestingArticlesCarousel, initReadOftenArticlesCarousel }) => {
            initAlsowInterestingArticlesCarousel();
            initReadOftenArticlesCarousel();
          })
          .catch(() => {
            console.warn('Could not reinitialize carousels');
          });
      }, 100);
    } catch (error) {
      console.error('Error reinitializing components:', error);
    }
  }

  /**
   * Reinitialize pagination click handlers after AJAX content load
   */
  reinitializePagination() {
    try {
      // Import and call pagination initialization function
      import('../pages/blogs')
        .then(({ initPaginationClickHandlers }) => {
          initPaginationClickHandlers();
          console.log('Pagination handlers reinitialized');
        })
        .catch(() => {
          console.warn('Could not reinitialize pagination handlers');
        });
    } catch (error) {
      console.error('Error reinitializing pagination:', error);
    }
  }

  /**
   * Destroy existing carousels (preserve existing approach)
   */
  destroyExistingCarousels() {
    const carousels = [
      '#alsow-interesting-articles-carousel-container',
      '#read-often-articles-carousel-container',
    ];

    carousels.forEach(selector => {
      const $carousel = $(selector);
      if ($carousel.length && $carousel.hasClass('slick-initialized')) {
        try {
          $carousel.slick('destroy');
        } catch (error) {
          console.warn(`Error destroying carousel ${selector}:`, error);
        }
      }
    });
  }

  /**
   * Update category sidebar state (preserve existing approach)
   */
  updateCategorySidebarState(categorySlug) {
    const sidebar = document.querySelector('[data-blog-sidebar]');
    if (!sidebar) return;

    // Remove active class from all elements
    sidebar.querySelectorAll('.blog-nav li').forEach(li => {
      li.classList.remove('is-active');
    });

    // Add active class to selected category
    const targetLink = sidebar.querySelector(`[data-category-slug="${categorySlug || ''}"]`);
    if (targetLink) {
      targetLink.closest('li').classList.add('is-active');
    }
  }

  /**
   * Clean redirect - delegate to store URL API
   */
  cleanRedirect() {
    const store = this.getStore();
    if (store && store.urlAPI && typeof store.urlAPI.cleanRedirect === 'function') {
      // Use centralized URL API
      store.urlAPI.cleanRedirect();
    } else {
      // Fallback if store URL API is not available
      console.warn('Store URL API not available, using fallback redirect');
      const cleanUrl = new URL(window.location.pathname, window.location.origin);
      window.history.pushState({}, '', cleanUrl.toString());
      window.location.reload();
    }
  }

  // ERROR HANDLING REMOVED: Now handled centrally in blogAPI.blogErrorHandler

  /**
   * Update URL for SEO (preserve existing approach)
   */
  updateUrlForSEO(data) {
    const store = this.getStore();

    if (data.currentCategory) {
      document.title = `${data.currentCategory.name} - Блог`;
    } else if (store && store.hasActiveSearch) {
      document.title = `Поиск "${store.filters.search}" - Блог`;
    } else if (data.totalCount !== undefined) {
      document.title = `Блог - ${data.totalCount} статей`;
    }
  }

  /**
   * Load content based on current store state - thin coordination method
   */
  loadFromCurrentState() {
    const store = this.getStore();
    if (!store) return;

    const container = document.getElementById('blog-articles-container');
    if (!container) {
      console.warn('Blog container not found');
      return;
    }

    // Use simplified coordination - all AJAX logic is in blogAPI
    const baseUrl = '/api/blog/list';

    console.log('Loading content from current state via blogAPI...');

    this.loadContent(container, baseUrl, {
      scrollToTop: true,
      validateParams: true,
      useInlineLoader: true, // Use inline loader for state changes
    });
  }

  /**
   * Initialize from current URL - delegate to store URL API
   */
  initFromURL() {
    const store = this.getStore();
    if (store && store.urlAPI) {
      // Use centralized URL API to restore state from URL
      store.urlAPI.restoreFromUrl();
    } else {
      // If Alpine isn't ready, defer initialization
      console.warn('Alpine store not ready, deferring URL initialization');
      document.addEventListener('alpine:init', () => {
        const deferredStore = this.getStore();
        if (deferredStore && deferredStore.urlAPI) {
          deferredStore.urlAPI.restoreFromUrl();
        }
      });
    }
  }

  // MANAGER API - Business operations interface
  // This API provides high-level operations that coordinate between blogAPI and store
  // Usage: blogAjaxManager.operationsAPI.methodName()
  get operationsAPI() {
    return {
      // Navigation operations - moved from store for better separation
      goToPage: page => this.goToPage(page),
      goToNextPage: () => this.goToNextPage(),
      goToPrevPage: () => this.goToPrevPage(),
      goToFirstPage: () => this.goToFirstPage(),
      goToLastPage: () => this.goToLastPage(),

      // Filter operations
      setCategory: categorySlug => this.setCategory(categorySlug),
      setSearch: searchQuery => this.setSearch(searchQuery),
      setSort: (sortType, direction) => this.setSort(sortType, direction),
      clearSearch: () => this.clearSearch(),
      clearCategory: () => this.clearCategory(),
      clearFilters: () => this.clearFilters(),

      // Content operations
      loadContent: (container, url, options) => this.loadContent(container, url, options),
      loadFromCurrentState: () => this.loadFromCurrentState(),

      // State operations
      refreshContent: () => this.refreshContent(),
      validateAndNavigate: filters => this.validateAndNavigate(filters),

      // Navigation and redirect operations - moved from store
      handleRedirect: redirectUrl => this.handleRedirectResponse(redirectUrl),
      navigateToState: (filters, replaceState) => this.navigateToState(filters, replaceState),
      cleanRedirect: () => this.cleanRedirect(),
    };
  }

  // Navigation methods - moved from store to manager for better separation
  goToPage(page) {
    const store = this.getStore();
    if (!store) return false;

    const targetPage = parseInt(page);
    if (targetPage < 1 || targetPage > store.pagination.totalPages || store.loading) {
      return false;
    }

    // Update filters via state API
    store.stateAPI.setFilters({ ...store.filters, page: targetPage });

    // Load content using manager coordination
    this.loadFromCurrentState();
    return true;
  }

  goToNextPage() {
    const store = this.getStore();
    if (!store) return false;

    if (store.pagination.hasNext && !store.loading) {
      return this.goToPage(store.pagination.currentPage + 1);
    }
    return false;
  }

  goToPrevPage() {
    const store = this.getStore();
    if (!store) return false;

    if (store.pagination.hasPrev && !store.loading) {
      return this.goToPage(store.pagination.currentPage - 1);
    }
    return false;
  }

  goToFirstPage() {
    const store = this.getStore();
    if (!store) return false;

    if (store.pagination.currentPage > 1 && !store.loading) {
      return this.goToPage(1);
    }
    return false;
  }

  goToLastPage() {
    const store = this.getStore();
    if (!store) return false;

    if (store.pagination.currentPage < store.pagination.totalPages && !store.loading) {
      return this.goToPage(store.pagination.totalPages);
    }
    return false;
  }

  setCategory(categorySlug) {
    const store = this.getStore();
    if (!store || store.loading) return false;

    // Update filters via state API and navigate
    const newFilters = { ...store.filters, category: categorySlug, page: 1 };
    return this.validateAndNavigate(newFilters);
  }

  async setSearch(searchQuery) {
    const store = this.getStore();
    if (!store) return false;

    if (store.loading) {
      console.warn('Search blocked: content is loading');
      return false;
    }

    const query = searchQuery ? searchQuery.trim() : '';

    // Client-side validation to prevent server-side validation failures
    if (query && query.length > 0 && query.length < 3) {
      console.warn('Search query too short (min 3 characters):', query);
      return false;
    }

    // Use centralized search validation
    if (query) {
      const { ValidationMethods } = await import('../validation/validation-constants.js');
      const searchValidation = ValidationMethods.validateBlogSearch(query);
      if (!searchValidation.isValid) {
        console.warn('Search validation failed:', searchValidation.errors);
        return false;
      }
    }

    console.log('Setting search query:', query);

    // Update filters and navigate
    const newFilters = { ...store.filters, search: query, page: 1 };
    return this.validateAndNavigate(newFilters);
  }

  setSort(sortType, direction = 'desc') {
    const store = this.getStore();
    if (!store || store.loading) return false;

    const newFilters = { ...store.filters, sort: sortType, direction, page: 1 };
    return this.validateAndNavigate(newFilters);
  }

  clearSearch() {
    const store = this.getStore();
    if (!store) return false;

    console.log('Clearing search from manager');
    const newFilters = { ...store.filters, search: '', page: 1 };
    return this.validateAndNavigate(newFilters);
  }

  clearCategory() {
    const store = this.getStore();
    if (!store) return false;

    const newFilters = { ...store.filters, category: '', page: 1 };
    return this.validateAndNavigate(newFilters);
  }

  clearFilters() {
    const store = this.getStore();
    if (!store) return false;

    const defaultFilters = {
      page: 1,
      category: '',
      search: '',
      sort: 'latest',
      direction: 'desc',
    };
    return this.validateAndNavigate(defaultFilters);
  }

  // NEW: Unified navigation method with validation
  validateAndNavigate(filters) {
    const store = this.getStore();
    if (!store) return false;

    // Update filters via state API
    store.stateAPI.setFilters(filters);

    // Update URL via URL API
    if (store.urlAPI) {
      store.urlAPI.updateUrl(true);
    }

    // Load content
    this.loadFromCurrentState();
    return true;
  }

  // NEW: Refresh current content
  refreshContent() {
    console.log('Refreshing content via manager...');
    this.loadFromCurrentState();
  }

  // NEW: Handle redirect responses from server - moved from store
  handleRedirectResponse(redirectUrl) {
    console.log('Manager handling redirect to:', redirectUrl);

    const store = this.getStore();
    if (!store) return;

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

    // Update state via State API
    store.stateAPI.setFilters(redirectFilters);

    // Update URL via URL API
    if (store.urlAPI) {
      store.urlAPI.updateUrl(false); // Replace state since we're handling redirect
    }

    // Load content via Manager
    this.loadFromCurrentState();
  }

  // NEW: Navigate to specific state - moved from store
  navigateToState(filters, replaceState = false) {
    const store = this.getStore();
    if (!store) return;

    // Update state via State API
    store.stateAPI.setFilters(filters);

    // Update URL via URL API
    if (store.urlAPI) {
      store.urlAPI.updateUrl(!replaceState);
    }

    // Load content via Manager
    this.loadFromCurrentState();
  }

  // NEW: Clean redirect - moved from store
  cleanRedirect() {
    console.log('Manager performing clean redirect');

    const store = this.getStore();
    if (store) {
      // Show loader before page reload
      store.stateAPI.setLoading(true);

      // Reset filters to defaults via State API
      const defaultFilters = {
        page: 1,
        category: '',
        search: '',
        sort: 'latest',
        direction: 'desc',
      };
      store.stateAPI.setFilters(defaultFilters);
    }

    // Create clean URL and reload
    const cleanUrl = new URL(window.location.pathname, window.location.origin);
    window.history.pushState({}, '', cleanUrl.toString());
    window.location.reload();
  }
}

// Create and export singleton instance
// This manager is now a coordination layer with clear API for operations
export const blogAjaxManager = new BlogAjaxManager();
