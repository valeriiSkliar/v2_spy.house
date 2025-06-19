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

  // Initialize the blog application
  init() {
    console.log('Blog app initializing...');

    // Initialize ajax manager from URL
    blogAjaxManager.initFromURL();

    // Mark as initialized
    this.initialized = true;

    console.log('Blog app initialized successfully');
  },

  // Direct access to store for debugging
  get blogStore() {
    return this.$store.blog;
  },

  // Development helpers
  debug() {
    if (import.meta.env?.DEV) {
      console.log('Blog Store State:', this.blogStore);
      console.log('Blog AJAX Manager:', blogAjaxManager);
    }
  },
}));

// Start Alpine when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  console.log('Starting Alpine.js for blog application...');
  Alpine.start();

  // Dispatch custom event when Alpine is ready
  setTimeout(() => {
    document.dispatchEvent(new CustomEvent('alpine:ready'));
  }, 50);
});

// For development - expose Alpine globally
if (import.meta.env?.DEV) {
  window.Alpine = Alpine;
  window.blogAjaxManager = blogAjaxManager;
}

export default Alpine;
