import Alpine from 'alpinejs';
import { blogAPI } from '../components/fetcher/ajax-fetcher';
import { ValidationMethods } from '../validation/validation-constants.js';

/**
 * Blog AJAX Manager
 * Unified AJAX management with state integration
 * Uses existing ajax-fetcher infrastructure and DOM manipulation principles
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
   * Main content loading method
   * Replaces reloadBlogContent with store integration
   * Uses centralized blogAPI with built-in retry logic
   */
  async loadContent(container, url, options = {}) {
    const { scrollToTop = true, validateParams = true } = options;

    const store = this.getStore();
    if (!store) {
      console.warn('Store not available, falling back to basic functionality');
      return;
    }

    // Prevent multiple simultaneous requests
    if (store.loading && store.currentRequest) {
      store.currentRequest.abort();
    }

    // Validate parameters using store and centralized validation
    if (validateParams) {
      if (!store.validateFilters()) {
        console.warn('Invalid request parameters detected, redirecting to clean state');
        this.cleanRedirect();
        return;
      }
      
      // Validation is handled by middleware, store validation is sufficient for client-side checks
    }

    console.log('Loading blog content...', { url, options });

    // Log search state if active
    if (store.hasActiveSearch) {
      console.log('Search active:', store.filters.search);
    }

    // Set loading state - UI components will react to store.loading
    store.setLoading(true);

    // Build request URL using store
    const requestUrl = store.buildRequestURL(url);

    console.log('Request URL built:', requestUrl);

    try {
      const startTime = Date.now();

      // Используем централизованный blogAPI вместо собственного makeRequest
      const response = await blogAPI.loadArticles(requestUrl);

      const loadTime = Date.now() - startTime;
      store.setStats({ loadTime });

      console.log('AJAX response received:', response);

      // Handle redirect response
      if (response.redirect) {
        this.handleRedirectResponse(response, container, url, options);
        return;
      }

      // Handle validation error
      if (response.error) {
        console.error('Validation error:', response.error);
        this.cleanRedirect();
        return;
      }

      // Update content and state
      this.updatePageContent(response, container, scrollToTop);
    } catch (error) {
      console.error('Error fetching blog articles:', error);
      this.showErrorMessage(container, error);
    } finally {
      store.setLoading(false);
      store.setCurrentRequest(null);
      // UI components will react to store.loading = false
    }
  }

  /**
   * Handle redirect response with store integration
   */
  handleRedirectResponse(data, container, url, options) {
    const store = this.getStore();
    if (!store) return;

    // Delegate URL handling to store
    if (typeof store.handleRedirectResponse === 'function') {
      store.handleRedirectResponse(data.url);
    } else {
      console.warn('Store does not support redirect handling');
    }

    // Update category sidebar state
    this.updateCategorySidebarState(store.filters.category);
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
      this.showErrorMessage(container, error);
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
   * Clean redirect (delegate to store)
   */
  cleanRedirect() {
    const store = this.getStore();
    if (store && typeof store.cleanRedirect === 'function') {
      store.cleanRedirect();
    } else {
      // Fallback if store is not available
      const cleanUrl = new URL(window.location.pathname, window.location.origin);
      window.history.pushState({}, '', cleanUrl.toString());
      window.location.reload();
    }
  }

  /**
   * Show error message (preserve existing approach)
   */
  showErrorMessage(container, error) {
    const errorHtml = `
            <div class="blog-error-message empty-landing">
                <h3>Произошла ошибка при загрузке</h3>
                <p>Пожалуйста, обновите страницу или попробуйте позже.</p>
                <button onclick="window.location.reload()" class="btn _flex _green _medium min-120">Обновить страницу</button>
            </div>
        `;
    container.innerHTML = errorHtml;
  }

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
   * Load content based on current store state
   */
  loadFromCurrentState() {
    const store = this.getStore();
    if (!store) return;

    const container = document.getElementById('blog-articles-container');
    if (!container) {
      console.warn('Blog container not found');
      return;
    }

    // Build URL for request based on current filters
    const baseUrl = '/api/blog/list';
    const requestUrl = store.buildRequestURL(baseUrl);

    console.log('Loading content from current state:', requestUrl);

    this.loadContent(container, baseUrl, {
      scrollToTop: true,
      showLoader: true,
      validateParams: true,
    });
  }

  /**
   * Initialize from current URL
   */
  initFromURL() {
    const store = this.getStore();
    if (store) {
      store.updateFromURL();
    } else {
      // If Alpine isn't ready, defer initialization
      console.warn('Alpine store not ready, deferring URL initialization');
      document.addEventListener('alpine:init', () => {
        const deferredStore = this.getStore();
        if (deferredStore) {
          deferredStore.updateFromURL();
        }
      });
    }
  }
}

// Create and export singleton instance
export const blogAjaxManager = new BlogAjaxManager();
