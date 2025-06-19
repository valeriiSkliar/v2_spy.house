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
import { initBlogPageComponent } from './components/blogs/blog-page-component';
import { initBlogStore } from './stores/blog-store';

// Blog managers and services
import { blogAjaxManager } from './managers/blog-ajax-manager';

// Legacy blog functionality for backward compatibility
import './pages/blogs';

// Alpine.js extensions
import { initBlogDirectives } from './alpine/directives';
import { initBlogMagicMethods } from './alpine/magic-methods';

// Configure Alpine plugins
Alpine.plugin(persist);
Alpine.plugin(collapse);
Alpine.plugin(focus);
Alpine.plugin(intersect);

// Initialize blog store after plugins are configured
initBlogStore(Alpine);

// Initialize blog page component
initBlogPageComponent();

// Initialize Alpine extensions
initBlogDirectives();
initBlogMagicMethods();

// Simplified global blog app component
Alpine.data('blogApp', () => ({
  // App state
  initialized: false,
  urlRestored: false,

  // Initialize the blog application
  init() {
    console.log('Blog app initializing...');

    // NEW: Restore state from URL first (highest priority)
    this.restoreFromURL();

    // Initialize ajax manager from URL
    blogAjaxManager.initFromURL();

    // Mark as initialized
    this.initialized = true;

    // NEW: Setup URL change listeners
    this.setupUrlListeners();

    // NEW: Setup page visibility listeners (for tab restoration)
    this.setupVisibilityListeners();

    console.log('Blog app initialized successfully');
  },

  // NEW: Restore state from URL on app start
  restoreFromURL() {
    console.log('Restoring blog state from URL...');

    const store = this.$store.blog;
    if (!store) {
      console.warn('Blog store not available for URL restoration');
      return;
    }

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
        store.initFromURL();
        this.urlRestored = true;

        // Restore scroll position if available
        setTimeout(() => {
          const savedPosition = sessionStorage.getItem('blog_scroll_position');
          if (savedPosition) {
            const position = parseInt(savedPosition);
            window.scrollTo({ top: position, behavior: 'smooth' });
          }
        }, 500); // Delay to allow content loading
      }
    } catch (error) {
      console.error('Error restoring from URL:', error);
    }
  },

  // NEW: Setup URL change listeners
  setupUrlListeners() {
    // Listen for browser back/forward navigation
    window.addEventListener('popstate', event => {
      console.log('Popstate event detected, updating blog state');
      const store = this.$store.blog;

      if (store && typeof store.handlePopState === 'function') {
        store.handlePopState();
      }
    });

    // Listen for hash changes (secondary navigation)
    window.addEventListener('hashchange', () => {
      console.log('Hash change detected');
      // Could handle anchor navigation here if needed
    });
  },

  // NEW: Setup page visibility listeners
  setupVisibilityListeners() {
    // When user returns to tab, check if URL state is in sync
    document.addEventListener('visibilitychange', () => {
      if (!document.hidden && this.initialized) {
        const store = this.$store.blog;
        if (store && !store.isStateInSync()) {
          console.log('Page became visible, syncing state with URL');
          store.updateFromURL();
        }
      }
    });

    // When page is about to unload, save scroll position
    window.addEventListener('beforeunload', () => {
      const store = this.$store.blog;
      if (store) {
        const position = window.pageYOffset || document.documentElement.scrollTop;
        sessionStorage.setItem('blog_scroll_position', position.toString());
      }
    });
  },

  // Direct access to store for debugging
  get blogStore() {
    return this.$store.blog;
  },

  // NEW: URL-related methods for components
  get currentUrl() {
    return this.blogStore ? this.blogStore.getCurrentURL() : window.location.href;
  },

  get isUrlSynced() {
    return this.blogStore ? this.blogStore.isStateInSync() : false;
  },

  // NEW: State restoration methods
  restoreState() {
    if (this.blogStore && typeof this.blogStore.updateFromURL === 'function') {
      this.blogStore.updateFromURL();
      return true;
    }
    return false;
  },

  syncWithUrl() {
    if (this.blogStore && typeof this.blogStore.updateURL === 'function') {
      this.blogStore.updateURL(false); // Replace state
      return true;
    }
    return false;
  },

  // NEW: Navigation methods with URL sync
  navigateTo(filters, replaceState = false) {
    if (this.blogStore && typeof this.blogStore.navigateToState === 'function') {
      this.blogStore.navigateToState(filters, replaceState);
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

  // NEW: Force state synchronization (emergency method)
  forceSyncState() {
    console.log('Force syncing blog state...');

    if (this.blogStore) {
      this.blogStore.updateFromURL();

      // Trigger content reload if needed
      if (
        window.blogAjaxManager &&
        typeof window.blogAjaxManager.loadFromCurrentState === 'function'
      ) {
        window.blogAjaxManager.loadFromCurrentState();
      }
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
