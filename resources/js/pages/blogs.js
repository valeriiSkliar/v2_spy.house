import {
  initAlsowInterestingArticlesCarousel,
  initBlogSearch,
  initCommentPagination,
  initReadOftenArticlesCarousel,
  initReplyButtons,
  initUniversalCommentForm,
} from '@/components/blogs';
import { hideInElement, showInElement } from '../components/loader';
import { updateBrowserUrl } from '../helpers/update-browser-url';

/**
 * Global function to reload blog content
 * @param {HTMLElement} container - The blog container
 * @param {string} url - The AJAX URL to fetch data
 * @param {boolean} scrollToTop - Whether to scroll to top after loading
 */
function reloadBlogContent(container, url, scrollToTop = true) {
  console.log('Reloading blog content...', { url, scrollToTop });

  // Show loading state
  const loader = showInElement(container);

  // Build URL with the current query parameters
  const requestUrl = new URL(window.location.href);

  // Make AJAX request
  fetch(`${url}?${requestUrl.searchParams.toString()}`, {
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    },
  })
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      console.log('AJAX response received:', data);

      // Handle redirect response
      if (data.redirect) {
        window.location.href = data.url;
        return;
      }

      // Update content
      container.innerHTML = data.html;

      // Update pagination container
      const paginationContainer = document.getElementById('blog-pagination-container');
      if (paginationContainer) {
        // If pagination data exists, show it
        if (data.hasPagination && data.pagination) {
          paginationContainer.innerHTML = data.pagination;
          paginationContainer.style.display = 'block';
          // Re-initialize pagination click handlers for new content
          initPaginationClickHandlers();
        } else {
          // Otherwise hide the pagination container
          paginationContainer.innerHTML = '';
          paginationContainer.style.display = 'none';
        }
      }

      // Re-initialize carousels if they exist in the new content
      initAlsowInterestingArticlesCarousel();
      initReadOftenArticlesCarousel();

      // Scroll to top of blog container for better UX
      if (scrollToTop) {
        container.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    })
    .catch(error => {
      console.error('Error fetching blog articles:', error);
    })
    .finally(() => {
      // Remove loading state
      hideInElement(loader);
    });
}

// Store pagination click handler globally
let currentPaginationHandler = null;

/**
 * Initialize pagination click handlers
 */
function initPaginationClickHandlers() {
  const paginationLinks = document.querySelectorAll(
    '#blog-pagination-container .pagination-list a'
  );
  console.log('Found pagination links:', paginationLinks.length);

  paginationLinks.forEach(link => {
    // Remove existing event listeners to prevent duplicates
    if (currentPaginationHandler) {
      link.removeEventListener('click', currentPaginationHandler);
    }
    // Add new event listener
    link.addEventListener('click', handlePaginationClick);
  });

  // Store current handler
  currentPaginationHandler = handlePaginationClick;
}

/**
 * Handle pagination link clicks
 * @param {Event} event - Click event
 */
function handlePaginationClick(event) {
  console.log('Pagination link clicked:', event.target.href);
  event.preventDefault();

  const url = new URL(event.target.href);
  const page = url.searchParams.get('page');
  const blogContainer = document.getElementById('blog-articles-container');
  const ajaxUrl = blogContainer?.getAttribute('data-blog-ajax-url');

  if (page && ajaxUrl) {
    console.log('Navigating to page:', page);
    // Update browser URL
    updateBrowserUrl({ page: page });

    // Reload blog content
    reloadBlogContent(blogContainer, ajaxUrl);
  } else {
    console.log('No page parameter found in URL:', event.target.href);
  }
}

document.addEventListener('DOMContentLoaded', function () {
  // Initialize blog pagination if on blog index page
  initBlogPagination();

  // Initialize category filtering
  initCategoryFiltering();

  // Initialize sidebar state based on current URL
  initSidebarState();

  // Initialize existing blog components
  const commentForm = $('#universal-comment-form');
  if (commentForm.length) {
    initReplyButtons(commentForm);
    initUniversalCommentForm(commentForm);
  }
  initCommentPagination();
  initAlsowInterestingArticlesCarousel();
  initReadOftenArticlesCarousel();
  initBlogSearch();
});

/**
 * Initialize category filtering functionality
 */
function initCategoryFiltering() {
  const blogContainer = document.getElementById('blog-articles-container');
  const ajaxUrl = blogContainer?.getAttribute('data-blog-ajax-url');

  if (!ajaxUrl) {
    console.log('No AJAX URL found, category filtering disabled');
    return;
  }

  // Add event listeners to category links
  document.addEventListener('click', function (event) {
    const categoryLink = event.target.closest('[data-ajax-category-link]');
    if (!categoryLink) return;

    event.preventDefault();
    const categorySlug = categoryLink.getAttribute('data-category-slug');

    console.log('Category clicked:', categorySlug || 'all');

    // Update active state in sidebar
    updateCategorySidebarState(categorySlug);

    // Update URL and reload content
    const urlParams = new URLSearchParams(window.location.search);
    if (categorySlug) {
      urlParams.set('category', categorySlug);
    } else {
      urlParams.delete('category');
    }
    urlParams.delete('page'); // Reset to page 1 when changing category

    // Update browser URL
    const newUrl =
      window.location.pathname + (urlParams.toString() ? '?' + urlParams.toString() : '');
    window.history.pushState({ category: categorySlug }, '', newUrl);

    // Reload blog content
    reloadBlogContent(blogContainer, ajaxUrl);
  });
}

/**
 * Update active state in category sidebar
 * @param {string} categorySlug - Selected category slug
 */
function updateCategorySidebarState(categorySlug) {
  const sidebar = document.querySelector('[data-blog-sidebar]');
  if (!sidebar) return;

  // Remove active class from all items
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
 * Initialize blog pagination AJAX functionality
 */
function initBlogPagination() {
  const blogContainer = document.getElementById('blog-articles-container');
  const ajaxUrl = blogContainer?.getAttribute('data-blog-ajax-url');
  const useAjax = !!ajaxUrl;

  console.log('Blog pagination init:', { blogContainer, ajaxUrl, useAjax });

  if (!useAjax) {
    console.log('No AJAX URL found, pagination disabled');
    return;
  }

  // Add popstate event listener to handle browser back/forward navigation
  window.addEventListener('popstate', function (event) {
    if (blogContainer && ajaxUrl) {
      // Update sidebar state based on current URL
      const urlParams = new URLSearchParams(window.location.search);
      const categorySlug = urlParams.get('category') || '';
      updateCategorySidebarState(categorySlug);

      // Reload content
      reloadBlogContent(blogContainer, ajaxUrl);
    }
  });

  // Initialize pagination click handlers
  initPaginationClickHandlers();
}

/**
 * Initialize sidebar state based on current URL parameters
 */
function initSidebarState() {
  const urlParams = new URLSearchParams(window.location.search);
  const categorySlug = urlParams.get('category') || '';
  updateCategorySidebarState(categorySlug);
}
