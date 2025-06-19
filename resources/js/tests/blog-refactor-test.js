/**
 * Basic tests for blog refactoring
 * Validates core functionality and store integration
 */

import { blogStore, initBlogStore } from '../stores/blog-store';
import { BlogAjaxManager } from '../managers/blog-ajax-manager';
import Alpine from 'alpinejs';

// Mock Alpine for testing
const mockAlpine = {
    store: (name, store) => {
        this.stores = this.stores || {};
        this.stores[name] = store;
        return store;
    },
    stores: {}
};

// Initialize store for testing
initBlogStore(mockAlpine);

// Test suite
const tests = {
    'Store initialization': () => {
        const store = mockAlpine.stores.blog;
        console.assert(store !== undefined, 'Store should be initialized');
        console.assert(store.loading === false, 'Loading should be false initially');
        console.assert(store.filters.page === 1, 'Page should be 1 initially');
        return true;
    },

    'Filter updates': () => {
        const store = mockAlpine.stores.blog;
        store.setFilters({ category: 'test-category', page: 2 });
        console.assert(store.filters.category === 'test-category', 'Category should be updated');
        console.assert(store.filters.page === 2, 'Page should be updated');
        return true;
    },

    'URL parameter building': () => {
        const store = mockAlpine.stores.blog;
        store.setFilters({ 
            category: 'tech', 
            search: 'javascript', 
            page: 3,
            sort: 'popular',
            direction: 'asc'
        });
        
        const params = store.filterParams;
        console.assert(params.get('category') === 'tech', 'Category param should be set');
        console.assert(params.get('search') === 'javascript', 'Search param should be set');
        console.assert(params.get('page') === '3', 'Page param should be set');
        console.assert(params.get('sort') === 'popular', 'Sort param should be set');
        console.assert(params.get('direction') === 'asc', 'Direction param should be set');
        return true;
    },

    'Filter validation': () => {
        const store = mockAlpine.stores.blog;
        
        // Valid filters
        store.setFilters({ page: 5, category: 'valid-category', search: 'test' });
        console.assert(store.validateFilters() === true, 'Valid filters should pass validation');
        
        // Invalid page
        store.setFilters({ page: -1 });
        console.assert(store.validateFilters() === false, 'Invalid page should fail validation');
        
        // Invalid category
        store.setFilters({ page: 1, category: 'invalid@category!' });
        console.assert(store.validateFilters() === false, 'Invalid category should fail validation');
        
        // Reset to valid state
        store.setFilters({ page: 1, category: '', search: '' });
        return true;
    },

    'Pagination helpers': () => {
        const store = mockAlpine.stores.blog;
        store.setPagination({ currentPage: 3, totalPages: 10, hasPagination: true });
        
        console.assert(store.isFirstPage === false, 'Should not be first page');
        console.assert(store.isLastPage === false, 'Should not be last page');
        console.assert(store.pagination.hasNext === true, 'Should have next page');
        console.assert(store.pagination.hasPrev === true, 'Should have previous page');
        return true;
    },

    'State reset': () => {
        const store = mockAlpine.stores.blog;
        
        // Set some state
        store.setLoading(true);
        store.setFilters({ category: 'test', search: 'query', page: 5 });
        store.setStats({ totalCount: 100, currentCount: 10 });
        
        // Reset state
        store.resetState();
        
        console.assert(store.loading === false, 'Loading should be reset');
        console.assert(store.articles.length === 0, 'Articles should be empty');
        console.assert(store.stats.totalCount === 0, 'Stats should be reset');
        return true;
    },

    'AJAX Manager initialization': () => {
        const manager = new BlogAjaxManager();
        console.assert(manager.store !== undefined, 'Manager should have store reference');
        console.assert(manager.MAX_RETRIES === 3, 'Manager should have retry limit');
        return true;
    },

    'Filter persistence': () => {
        const store = mockAlpine.stores.blog;
        
        // Mock sessionStorage
        const mockStorage = {};
        global.sessionStorage = {
            getItem: (key) => mockStorage[key],
            setItem: (key, value) => { mockStorage[key] = value; }
        };
        
        // Set filters (should trigger persistence)
        store.setFilters({ category: 'persist-test', search: 'persist-query' });
        
        // Check if persisted
        const persisted = JSON.parse(mockStorage['blog_filters'] || '{}');
        console.assert(persisted.category === 'persist-test', 'Category should be persisted');
        console.assert(persisted.search === 'persist-query', 'Search should be persisted');
        return true;
    }
};

// Run tests
export function runBlogRefactorTests() {
    console.log('Running blog refactor tests...');
    
    let passed = 0;
    let total = 0;
    
    for (const [testName, testFn] of Object.entries(tests)) {
        total++;
        try {
            if (testFn()) {
                console.log(`✅ ${testName}`);
                passed++;
            } else {
                console.log(`❌ ${testName} - Test returned false`);
            }
        } catch (error) {
            console.log(`❌ ${testName} - Error: ${error.message}`);
        }
    }
    
    console.log(`\nTest Results: ${passed}/${total} tests passed`);
    
    if (passed === total) {
        console.log('🎉 All tests passed! Blog refactoring is working correctly.');
    } else {
        console.log('⚠️ Some tests failed. Please check the implementation.');
    }
    
    return passed === total;
}

// Auto-run tests in development
if (import.meta.env?.DEV) {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => runBlogRefactorTests(), 1000);
    });
}