// import $ from "jquery";
import { apiTokenHandler } from '../api-token/api-token';

/**
 * Ajax fetcher for making API requests
 * Automatically handles authentication tokens
 */
const ajaxFetcher = {
  /**
   * Make a GET request
   * @param {string} url - The URL to fetch
   * @param {object} data - Query parameters
   * @param {object} settings - Settings object
   * @returns {Promise} jQuery ajax promise
   */
  get: (
    url,
    data,
    {
      successCallback = null,
      errorCallback = null,
      beforeSendCallback = null,
      completeCallback = null,
    }
  ) =>
    $.ajax({
      url,
      method: 'GET',
      data,
      success: successCallback,
      error: errorCallback,
      beforeSend: beforeSendCallback,
      complete: completeCallback,
    }),

  /**
   * Make a POST request
   * @param {string} url - The URL to fetch
   * @param {object} data - Body data
   * @returns {Promise} jQuery ajax promise
   */
  post: (url, data) =>
    $.ajax({
      url,
      method: 'POST',
      data,
      contentType: 'application/json',
    }),

  /**
   * Make a PUT request
   * @param {string} url - The URL to fetch
   * @param {object} data - Body data
   * @returns {Promise} jQuery ajax promise
   */
  put: (url, data) =>
    $.ajax({
      url,
      method: 'PUT',

      data,
      contentType: 'application/json',
    }),

  /**
   * Make a DELETE request
   * @param {string} url - The URL to fetch
   * @returns {Promise} jQuery ajax promise
   */
  delete: url => $.ajax({ url, method: 'DELETE' }),

  /**
   * Send form data
   * @param {string} url - The URL to send form data to
   * @param {FormData} formData - Form data to send
   * @returns {Promise} jQuery ajax promise
   */
  form: (
    url,
    formData,
    method = 'POST',
    settings = {
      successCallback: null,
      errorCallback: null,
      beforeSendCallback: null,
      completeCallback: null,
    }
  ) => {
    if (method !== 'POST') {
      // Add _method=PUT for Laravel to handle PUT requests
      formData.append('_method', method);
    }
    return $.ajax({
      url,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      ...settings,
    });
  },

  /**
   * Send form data
   * @param {string} url - The URL to send form data to
   * @param {FormData} formData - Form data to send
   * @returns {Promise} jQuery ajax promise
   */
  submit: (
    url,
    {
      data = null,
      successCallback = null,
      errorCallback = null,
      beforeSendCallback = null,
      completeCallback = null,
    } = {}
  ) => {
    const ajaxOptions = {
      url,
      method: 'POST',
      data: data,
      success: successCallback,
      error: errorCallback,
      beforeSend: beforeSendCallback,
      complete: completeCallback,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
      },
    };

    if (data instanceof FormData) {
      ajaxOptions.processData = false;
      ajaxOptions.contentType = false;
    }

    return $.ajax(ajaxOptions);
  },
};

/**
 * Initialize ajax interceptor for API requests
 * Sets up authorization headers and handles 401 errors
 */
const initAjaxFetcher = () => {
  // Set up default headers with token
  updateAjaxHeaders();

  // Add a pre-request interceptor to check token expiration
  // Use $.ajaxSetup instead of ajaxPrefilter since the latter might not be available
  $.ajaxSetup({
    beforeSend: async function (jqXHR, settings) {
      // Check if this is an API request (skip for non-API requests)
      const isApiRequest = settings.url.includes('/api/');
      if (!isApiRequest) return;

      // For API requests, check if token is about to expire
      if (apiTokenHandler.hasToken() && apiTokenHandler.isTokenExpiredOrExpiring()) {
        try {
          // Refresh the token before making the request
          console.log('Token is expiring soon. Refreshing before request...');
          await apiTokenHandler.refreshToken();

          // Update the request headers with the new token
          const token = apiTokenHandler.getToken();
          if (token) {
            settings.headers = settings.headers || {};
            settings.headers['Authorization'] = 'Bearer ' + token;
          }
        } catch (error) {
          console.error('Failed to refresh token before request:', error);
          // Let the request proceed and potentially fail with 401
        }
      }
    },
  });

  // Set up ajax error handler for 401 errors
  $(document).ajaxError(async function (event, jqXHR, ajaxSettings, thrownError) {
    // Handle 401 Unauthorized errors
    if (jqXHR.status === 401) {
      console.log('Received 401 response. Attempting token refresh...');
      try {
        // Try to refresh the token
        await apiTokenHandler.refreshToken();

        // Update headers for all future requests
        updateAjaxHeaders();

        // Retry the original request
        console.log('Token refreshed. Retrying original request...');
        return $.ajax({
          ...ajaxSettings,
          headers: {
            ...ajaxSettings.headers,
            Authorization: 'Bearer ' + apiTokenHandler.getToken(),
          },
        });
      } catch (error) {
        console.error('Token refresh failed after 401 error', error);

        // If the refresh fails with a 401/403, redirect to login
        if (error.message.includes('401') || error.message.includes('403')) {
          console.warn('Authentication error. You may need to log in again.');
          // Uncomment to redirect to login
          // window.location.href = '/login';
        }
      }
    }
  });
};

/**
 * Update Ajax headers with current token
 * Called on init and after token refresh
 */
const updateAjaxHeaders = () => {
  const token = apiTokenHandler.getToken();
  if (!token) {
    console.warn('API token not found. Some API requests may fail.');
  }

  $.ajaxSetup({
    credentials: 'same-origin',
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '',
      Authorization: token ? 'Bearer ' + token : '',
    },
  });
};

// Initialize automatically
document.addEventListener('DOMContentLoaded', initAjaxFetcher);

/**
 * Blog-specific API methods for centralized HTTP handling
 *
 * AFTER REFACTORING: This is now the SINGLE SOURCE for all blog AJAX operations
 * - Centralizes all HTTP requests, retry logic, and error handling
 * - All manager classes should delegate AJAX operations to these methods
 * - Provides unified interface with consistent error handling
 * - Contains complete error handling logic that was previously scattered
 */
const blogAPI = {
  // Configuration
  MAX_RETRIES: 3,
  RETRY_DELAY: 1000,

  /**
   * Enhanced error handler for blog requests
   */
  blogErrorHandler: {
    handleLoadError: xhr => {
      console.error('Blog load error:', xhr);
      if (xhr.status === 422) {
        // Validation error - redirect to clean state
        window.location.href = window.location.pathname;
      }
    },

    // NEW: Generate error HTML for display in containers
    generateErrorHtml: error => {
      return `
        <div class="blog-error-message empty-landing">
          <h3>Произошла ошибка при загрузке</h3>
          <p>Пожалуйста, обновите страницу или попробуйте позже.</p>
          <button onclick="window.location.reload()" class="btn _flex _green _medium min-120">Обновить страницу</button>
        </div>
      `;
    },

    // NEW: Handle various types of load errors with appropriate actions
    handleContentLoadError: (xhr, container) => {
      console.error('Content load error:', xhr);

      if (xhr.status === 422) {
        // Validation error - redirect to clean state
        window.location.href = window.location.pathname;
        return;
      }

      // For other errors, show error message in container
      if (container) {
        container.innerHTML = blogAPI.blogErrorHandler.generateErrorHtml(xhr);
      }
    },

    handleCommentError: xhr => {
      console.error('Comment error:', xhr);
      let message = 'Ошибка при отправке комментария';

      if (xhr.status === 401) {
        message = 'Необходимо войти в систему для отправки комментариев';
      } else if (xhr.status === 422) {
        try {
          const errors = JSON.parse(xhr.responseText);
          message = errors.message || errors.errors?.content?.[0] || message;
        } catch (e) {
          // Keep default message
        }
      }

      return message;
    },

    handleRatingError: xhr => {
      console.error('Rating error:', xhr);
      if (xhr.status === 401) {
        return 'Необходимо войти в систему для оценки статей';
      }
      return 'Ошибка при сохранении оценки';
    },
  },

  /**
   * Load blog articles with retry logic
   */
  loadArticles: async (url, params = {}, retryCount = 0) => {
    try {
      return await new Promise((resolve, reject) => {
        ajaxFetcher.get(url, params, {
          beforeSendCallback: () => console.log('Loading articles...'),
          successCallback: resolve,
          errorCallback: xhr => {
            blogAPI.blogErrorHandler.handleLoadError(xhr);
            reject(xhr);
          },
        });
      });
    } catch (error) {
      if (retryCount < blogAPI.MAX_RETRIES) {
        console.log(`Retrying articles load (${retryCount + 1}/${blogAPI.MAX_RETRIES})...`);
        await new Promise(resolve => setTimeout(resolve, blogAPI.RETRY_DELAY * (retryCount + 1)));
        return blogAPI.loadArticles(url, params, retryCount + 1);
      }
      throw error;
    }
  },

  /**
   * NEW: Load blog content with full error handling and validation
   * This method consolidates all AJAX logic that was scattered in the manager
   * Used by BlogAjaxManager as the single point for content loading
   */
  loadBlogContent: async (url, options = {}) => {
    const { container = null, retryCount = 0, validateParams = true } = options;

    try {
      const startTime = Date.now();

      // Load articles using existing method with retry logic
      const response = await blogAPI.loadArticles(url, {}, retryCount);

      const loadTime = Date.now() - startTime;

      // Add performance metrics to response
      return {
        ...response,
        loadTime,
        success: true,
      };
    } catch (error) {
      console.error('Error in loadBlogContent:', error);

      // Handle error using centralized error handler
      if (container) {
        blogAPI.blogErrorHandler.handleContentLoadError(error, container);
      }

      // Re-throw for manager to handle if needed
      throw {
        ...error,
        handled: !!container, // Mark if error was displayed to user
        success: false,
      };
    }
  },

  /**
   * Load comments with pagination
   */
  loadComments: (slug, params = {}) => {
    const url = `/api/blog/${slug}/comments`;
    return new Promise((resolve, reject) => {
      ajaxFetcher.get(url, params, {
        successCallback: resolve,
        errorCallback: reject,
      });
    });
  },

  /**
   * Submit comment with validation and error handling
   */
  submitComment: (slug, formData) => {
    const url = `/api/blog/${slug}/comment`;
    return new Promise((resolve, reject) => {
      ajaxFetcher.form(url, formData, 'POST', {
        successCallback: resolve,
        errorCallback: xhr => {
          const errorMessage = blogAPI.blogErrorHandler.handleCommentError(xhr);
          reject({ xhr, message: errorMessage });
        },
      });
    });
  },

  /**
   * Submit reply to comment
   */
  submitReply: (slug, formData) => {
    const url = `/api/blog/${slug}/reply`;
    return new Promise((resolve, reject) => {
      ajaxFetcher.form(url, formData, 'POST', {
        successCallback: resolve,
        errorCallback: xhr => {
          const errorMessage = blogAPI.blogErrorHandler.handleCommentError(xhr);
          reject({ xhr, message: errorMessage });
        },
      });
    });
  },

  /**
   * Submit rating with authentication check
   */
  submitRating: (slug, rating) => {
    const url = `/blog/${slug}/rate`;
    return new Promise((resolve, reject) => {
      ajaxFetcher.post(url, JSON.stringify({ rating }), {
        beforeSendCallback: xhr => {
          xhr.setRequestHeader('Content-Type', 'application/json');
          xhr.setRequestHeader(
            'X-CSRF-TOKEN',
            document.querySelector('meta[name="csrf-token"]')?.content || ''
          );
        },
        successCallback: resolve,
        errorCallback: xhr => {
          if (
            xhr.getResponseHeader('Location') &&
            xhr.getResponseHeader('Location').includes('login')
          ) {
            window.location.href = xhr.getResponseHeader('Location');
            return;
          }
          const errorMessage = blogAPI.blogErrorHandler.handleRatingError(xhr);
          reject({ xhr, message: errorMessage });
        },
      });
    });
  },

  /**
   * Search articles
   */
  searchArticles: (query, params = {}) => {
    const url = '/api/blog/search';
    const searchParams = { ...params, search: query };
    return new Promise((resolve, reject) => {
      ajaxFetcher.get(url, searchParams, {
        successCallback: resolve,
        errorCallback: reject,
      });
    });
  },

  /**
   * Get reply form HTML
   */
  getReplyForm: (slug, commentId) => {
    const url = `/api/blog/${slug}/reply/${commentId}`;
    return new Promise((resolve, reject) => {
      ajaxFetcher.get(
        url,
        {},
        {
          successCallback: resolve,
          errorCallback: reject,
        }
      );
    });
  },
};

// Export both original fetcher and blog API
export { ajaxFetcher, blogAPI };
