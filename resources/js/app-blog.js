/**
 * Blog Application Entry Point
 * Initializes Alpine.js with stores, components, and plugins
 * Provides unified blog functionality with state management
 */

// Alpine.js core and plugins
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';
import intersect from '@alpinejs/intersect';
import persist from '@alpinejs/persist';
import Alpine from 'alpinejs';

// Blog store and components
import { initSimpleBlogComponents } from './components/blogs/blog-simple-components';
import { initBlogStore } from './stores/blog-store';

// Blog managers and services
import { blogAjaxManager } from './managers/blog-ajax-manager';

// Legacy blog functionality for backward compatibility
// import './pages/blogs';

// Alpine.js extensions
import { initBlogDirectives } from './alpine/directives';

// Configure Alpine plugins
Alpine.plugin(persist);
Alpine.plugin(collapse);
Alpine.plugin(focus);
Alpine.plugin(intersect);

// Initialize blog store after plugins are configured
initBlogStore(Alpine);

// Initialize simplified blog components
initSimpleBlogComponents();

// Initialize Alpine extensions
initBlogDirectives();

// Simplified global blog app component
Alpine.data('blogApp', () => ({
  // App state
  initialized: false,
  urlRestored: false,

  // Initialize the blog application
  init() {
    console.log('Blog app initializing...');

    // Restore state from URL using centralized store API
    const store = this.$store.blog;
    if (store && store.urlAPI) {
      this.urlRestored = store.urlAPI.restoreFromUrl();
    }

    // Initialize ajax manager from URL
    blogAjaxManager.initFromURL();

    // Mark as initialized
    this.initialized = true;

    console.log('Blog app initialized successfully');
  },

  // URL-related methods now use centralized store API

  // Direct access to store for debugging
  get blogStore() {
    return this.$store.blog;
  },

  // URL-related methods - use centralized store URL API
  get currentUrl() {
    return this.blogStore && this.blogStore.urlAPI
      ? this.blogStore.urlAPI.getCurrentUrl()
      : window.location.href;
  },

  get isUrlSynced() {
    return this.blogStore && this.blogStore.urlAPI ? this.blogStore.urlAPI.isStateSynced() : false;
  },

  // State restoration methods - delegate to store URL API
  restoreState() {
    if (this.blogStore && this.blogStore.urlAPI) {
      return this.blogStore.urlAPI.restoreFromUrl();
    }
    return false;
  },

  syncWithUrl() {
    if (this.blogStore && this.blogStore.urlAPI) {
      this.blogStore.urlAPI.updateUrl(false); // Replace state
      return true;
    }
    return false;
  },

  // Navigation methods - delegate to store URL API
  navigateTo(filters, replaceState = false) {
    if (this.blogStore && this.blogStore.urlAPI) {
      this.blogStore.urlAPI.navigateToState(filters, replaceState);
    }
  },

  // Development helpers
  debug() {
    if (import.meta.env?.DEV) {
      console.log('Blog Store State:', this.blogStore);
      console.log('Blog AJAX Manager:', blogAjaxManager);
      console.log('Current URL:', this.currentUrl);
      console.log('URL Synced:', this.isUrlSynced);
      console.log('App Initialized:', this.initialized);
      console.log('URL Restored:', this.urlRestored);
    }
  },

  // Force state synchronization - delegate to store URL API
  forceSyncState() {
    console.log('Force syncing blog state...');

    if (this.blogStore && this.blogStore.urlAPI) {
      this.blogStore.urlAPI.forceSync();
    }
  },
}));

// Custom event for URL state changes
function dispatchUrlStateChange(detail = {}) {
  document.dispatchEvent(
    new CustomEvent('blog:url-state-changed', {
      detail: {
        ...detail,
        timestamp: Date.now(),
      },
    })
  );
}

// Start Alpine when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  console.log('Starting Alpine.js for blog application...');
  Alpine.start();

  // Dispatch custom event when Alpine is ready
  setTimeout(() => {
    document.dispatchEvent(new CustomEvent('alpine:ready'));

    // NEW: Dispatch blog ready event
    document.dispatchEvent(
      new CustomEvent('blog:ready', {
        detail: {
          store: Alpine.store('blog'),
          manager: blogAjaxManager,
          timestamp: Date.now(),
        },
      })
    );
  }, 50);
});

// NEW: Listen for custom blog events
document.addEventListener('blog:ready', event => {
  console.log('Blog application ready:', event.detail);

  // Global error handler for URL sync issues
  window.addEventListener('error', error => {
    if (error.message && error.message.includes('blog')) {
      console.error('Blog-related error detected:', error);
      // Could implement error recovery here
    }
  });
});

// For development - expose Alpine globally
if (import.meta.env?.DEV) {
  window.Alpine = Alpine;
  window.blogAjaxManager = blogAjaxManager;
  window.dispatchUrlStateChange = dispatchUrlStateChange;
}

export default Alpine;
