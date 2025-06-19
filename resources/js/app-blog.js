/**
 * Blog Application Entry Point
 * Initializes Alpine.js with stores, components, and plugins
 * Provides unified blog functionality with state management
 */

import Alpine from 'alpinejs'
import persist from '@alpinejs/persist'
import collapse from '@alpinejs/collapse'
import focus from '@alpinejs/focus'
import intersect from '@alpinejs/intersect'

// Import blog store initializer and components
import { initBlogStore } from './stores/blog-store'
import './components/blogs/blog-alpine-component'
import { blogAjaxManager } from './managers/blog-ajax-manager'

// Import existing blog functionality for backward compatibility
import './pages/blogs'

// Configure Alpine plugins
Alpine.plugin(persist)
Alpine.plugin(collapse)
Alpine.plugin(focus)
Alpine.plugin(intersect)

// Initialize blog store after plugins are configured
initBlogStore(Alpine)

// Global Alpine data for blog functionality
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
    
    // Global helper methods
    get blogStore() {
        return this.$store.blog;
    },
    
    // Navigation helpers for templates
    navigateToPage(page) {
        if (this.blogStore.loading) return;
        
        const container = document.getElementById('blog-articles-container');
        const url = container?.getAttribute('data-blog-ajax-url');
        
        if (container && url) {
            blogAjaxManager.goToPage(page);
            blogAjaxManager.loadContent(container, url);
        }
    },
    
    navigateToCategory(categorySlug) {
        if (this.blogStore.loading) return;
        
        const container = document.getElementById('blog-articles-container');
        const url = container?.getAttribute('data-blog-ajax-url');
        
        if (container && url) {
            blogAjaxManager.setCategory(categorySlug);
            blogAjaxManager.loadContent(container, url);
        }
    },
    
    performSearch(query) {
        if (this.blogStore.loading) return;
        
        const container = document.getElementById('blog-articles-container');
        const url = container?.getAttribute('data-blog-ajax-url');
        
        if (container && url) {
            blogAjaxManager.setSearch(query);
            blogAjaxManager.loadContent(container, url);
        }
    },
    
    applySorting(sortType, direction = 'desc') {
        if (this.blogStore.loading) return;
        
        const container = document.getElementById('blog-articles-container');
        const url = container?.getAttribute('data-blog-ajax-url');
        
        if (container && url) {
            blogAjaxManager.setSort(sortType, direction);
            blogAjaxManager.loadContent(container, url);
        }
    }
}));

// Global magic methods for blog
Alpine.magic('blog', () => {
    return {
        store: Alpine.store('blog'),
        manager: blogAjaxManager,
        
        // Quick access methods
        loading: () => Alpine.store('blog').loading,
        filters: () => Alpine.store('blog').filters,
        stats: () => Alpine.store('blog').stats,
        pagination: () => Alpine.store('blog').pagination,
        
        // Navigation methods
        goToPage: (page) => blogAjaxManager.goToPage(page),
        setCategory: (slug) => blogAjaxManager.setCategory(slug),
        setSearch: (query) => blogAjaxManager.setSearch(query),
        setSort: (type, direction) => blogAjaxManager.setSort(type, direction),
        
        // Helper methods
        clearFilters: () => Alpine.store('blog').resetFilters(),
        clearSearch: () => Alpine.store('blog').clearSearch(),
        clearCategory: () => Alpine.store('blog').clearCategory(),
        
        // Load content
        loadContent: (container, url, options) => blogAjaxManager.loadContent(container, url, options)
    };
});

// Alpine directive for blog loading states
Alpine.directive('blog-loading', (el, { expression }, { evaluateLater, effect }) => {
    const evaluate = evaluateLater(expression || 'loading');
    
    effect(() => {
        evaluate(loading => {
            if (loading) {
                el.style.opacity = '0.5';
                el.style.pointerEvents = 'none';
                el.setAttribute('aria-busy', 'true');
            } else {
                el.style.opacity = '1';
                el.style.pointerEvents = 'auto';
                el.removeAttribute('aria-busy');
            }
        });
    });
});

// Alpine directive for blog filters
Alpine.directive('blog-filter', (el, { modifiers, expression }, { evaluateLater, effect }) => {
    const evaluate = evaluateLater(expression);
    
    effect(() => {
        evaluate(filterValue => {
            const store = Alpine.store('blog');
            
            if (modifiers.includes('category')) {
                store.setFilters({ ...store.filters, category: filterValue, page: 1 });
            } else if (modifiers.includes('search')) {
                store.setFilters({ ...store.filters, search: filterValue, page: 1 });
            } else if (modifiers.includes('sort')) {
                store.setFilters({ ...store.filters, sort: filterValue, page: 1 });
            }
        });
    });
});

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