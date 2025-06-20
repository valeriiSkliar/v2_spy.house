import Alpine from 'alpinejs';
import { blogAjaxManager } from '../managers/blog-ajax-manager';

/**
 * Simplified Blog Legacy Functions
 * Most functionality has been moved to store and Alpine components
 * This file maintains only essential legacy compatibility
 */

/**
 * @deprecated Use blogAjaxManager.loadContent() directly
 */
function reloadBlogContent(container, url, options = {}) {
  return blogAjaxManager.loadContent(container, url, options);
}

// Global variable for pagination handler (legacy compatibility)
let currentPaginationHandler = null;

/**
 * Initialize pagination click handlers
 * This is kept for legacy compatibility with dynamically loaded pagination
 */
function initPaginationClickHandlers() {
  const paginationLinks = document.querySelectorAll(
    '#blog-pagination-container .pagination-list a'
  );

  console.log('Found pagination links:', paginationLinks.length);

  paginationLinks.forEach(link => {
    // Remove old handlers
    if (currentPaginationHandler) {
      link.removeEventListener('click', currentPaginationHandler);
    }

    // Add new handler
    link.addEventListener('click', handlePaginationClick);
  });

  currentPaginationHandler = handlePaginationClick;
}

/**
 * Handle pagination clicks - delegates to store
 */
function handlePaginationClick(event) {
  event.preventDefault();

  // Get store - required for new architecture
  if (typeof Alpine === 'undefined' || !Alpine.store) {
    console.error('Alpine store not available for pagination');
    return;
  }

  const store = Alpine.store('blog');
  if (!store) {
    console.error('Blog store not available');
    return;
  }

  // Check loading state through store
  if (store.loading) {
    console.log('Request already in progress, ignoring pagination click');
    return;
  }

  // Get page number from data-page attribute or href
  const linkElement = event.target.closest('a') || event.target;
  let page;

  // Priority: first data-page, then href
  if (linkElement.dataset.page) {
    page = parseInt(linkElement.dataset.page);
  } else {
    const href = linkElement.href;
    if (!href) {
      console.error('No href or data-page found on pagination link');
      return;
    }
    const url = new URL(href);
    page = parseInt(url.searchParams.get('page')) || 1;
  }

  // Validate page number
  if (page < 1 || page > 1000) {
    console.error('Invalid page number:', page);
    return;
  }

  console.log('Navigating to page:', page, 'via store');

  // Use store navigation
  store.operations().goToPage(page);
}

/**
 * Update sidebar category state (kept for legacy compatibility)
 */
function updateCategorySidebarState(categorySlug) {
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

// Legacy function exports for backward compatibility
function initBlogPagination() {
  console.warn('initBlogPagination is deprecated - pagination is handled by store');
  initPaginationClickHandlers();
}

function initCategoryFiltering() {
  console.warn('initCategoryFiltering is deprecated - categories are handled by store');
}

function initSidebarState() {
  console.warn('initSidebarState is deprecated - sidebar state is handled by store');
  const urlParams = new URLSearchParams(window.location.search);
  const categorySlug = urlParams.get('category') || '';
  updateCategorySidebarState(categorySlug);
}

function initBlogSorting() {
  console.warn('initBlogSorting is deprecated - sorting is handled by Alpine components');
}

function initFilterSearch() {
  console.warn('initFilterSearch is deprecated - search is handled by Alpine components');
}

function validateRequestParams() {
  console.warn('validateRequestParams is deprecated - validation is handled by store');
  return true;
}

// Initialize store-dependent functionality when Alpine is ready
document.addEventListener('alpine:ready', function () {
  console.log('Alpine is ready, initializing store-dependent functionality...');
  blogAjaxManager.initFromURL();
});

// Export essential functions for legacy compatibility
export {
  initBlogPagination,
  initBlogSorting,
  initCategoryFiltering,
  initFilterSearch,
  initPaginationClickHandlers,
  initSidebarState,
  reloadBlogContent,
  updateCategorySidebarState,
  validateRequestParams,
};
