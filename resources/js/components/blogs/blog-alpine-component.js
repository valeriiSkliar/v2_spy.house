import Alpine from 'alpinejs'
import { blogAjaxManager } from '../../managers/blog-ajax-manager'

/**
 * Alpine.js Blog Component
 * Provides reactive data binding and component-level state management
 * Integrates with centralized store and ajax manager
 */
export function blogComponent() {
    return {
        // Data
        get loading() {
            return this.$store.blog.loading;
        },
        
        get articles() {
            return this.$store.blog.articles;
        },
        
        get currentCategory() {
            return this.$store.blog.currentCategory;
        },
        
        get pagination() {
            return this.$store.blog.pagination;
        },
        
        get filters() {
            return this.$store.blog.filters;
        },
        
        get hasResults() {
            return this.$store.blog.hasResults;
        },
        
        get isFiltered() {
            return this.$store.blog.isFiltered;
        },
        
        get stats() {
            return this.$store.blog.stats;
        },
        
        // UI State
        searchQuery: '',
        selectedCategory: '',
        
        // Initialize component
        init() {
            console.log('Blog Alpine component initialized');
            
            // Sync component state with store
            this.syncWithStore();
            
            // Watch for store changes
            this.$watch('$store.blog.filters.search', (value) => {
                this.searchQuery = value;
            });
            
            this.$watch('$store.blog.filters.category', (value) => {
                this.selectedCategory = value;
            });
            
            // Initialize from URL
            this.$store.blog.updateFromURL();
            this.syncWithStore();
        },
        
        // Sync component state with store
        syncWithStore() {
            this.searchQuery = this.$store.blog.filters.search;
            this.selectedCategory = this.$store.blog.filters.category;
        },
        
        // Navigation methods
        goToPage(page) {
            if (this.loading) return;
            
            const container = this.getContainer();
            const url = this.getAjaxUrl();
            
            if (container && url) {
                blogAjaxManager.goToPage(page);
                this.loadContent(container, url);
            }
        },
        
        setCategory(categorySlug) {
            if (this.loading) return;
            
            const container = this.getContainer();
            const url = this.getAjaxUrl();
            
            if (container && url) {
                blogAjaxManager.setCategory(categorySlug);
                this.loadContent(container, url);
            }
        },
        
        setSearch(query) {
            if (this.loading) return;
            
            const container = this.getContainer();
            const url = this.getAjaxUrl();
            
            if (container && url) {
                this.searchQuery = query;
                blogAjaxManager.setSearch(query);
                this.loadContent(container, url);
            }
        },
        
        setSort(sortType, direction = 'desc') {
            if (this.loading) return;
            
            const container = this.getContainer();
            const url = this.getAjaxUrl();
            
            if (container && url) {
                blogAjaxManager.setSort(sortType, direction);
                this.loadContent(container, url);
            }
        },
        
        // Clear filters
        clearFilters() {
            if (this.loading) return;
            
            this.$store.blog.resetFilters();
            this.syncWithStore();
            
            const container = this.getContainer();
            const url = this.getAjaxUrl();
            
            if (container && url) {
                blogAjaxManager.syncUrlWithStore();
                this.loadContent(container, url);
            }
        },
        
        clearSearch() {
            if (this.loading) return;
            
            this.searchQuery = '';
            this.$store.blog.clearSearch();
            
            const container = this.getContainer();
            const url = this.getAjaxUrl();
            
            if (container && url) {
                blogAjaxManager.syncUrlWithStore();
                this.loadContent(container, url);
            }
        },
        
        clearCategory() {
            if (this.loading) return;
            
            this.selectedCategory = '';
            this.$store.blog.clearCategory();
            
            const container = this.getContainer();
            const url = this.getAjaxUrl();
            
            if (container && url) {
                blogAjaxManager.syncUrlWithStore();
                this.loadContent(container, url);
            }
        },
        
        // Search handling with debounce
        handleSearchInput(event) {
            const query = event.target.value.trim();
            this.searchQuery = query;
            
            // Debounce search
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.setSearch(query);
            }, 300);
        },
        
        // Load content using ajax manager
        loadContent(container, url, options = {}) {
            return blogAjaxManager.loadContent(container, url, options);
        },
        
        // Helper methods
        getContainer() {
            return document.getElementById('blog-articles-container');
        },
        
        getAjaxUrl() {
            const container = this.getContainer();
            return container?.getAttribute('data-blog-ajax-url');
        },
        
        // Format helpers for templates
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('ru-RU', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        },
        
        formatViews(views) {
            if (!views) return '0';
            if (views >= 1000) {
                return (views / 1000).toFixed(1) + 'k';
            }
            return views.toString();
        },
        
        // Category helpers
        getCategoryName(slug) {
            const category = this.$store.blog.categories.find(cat => cat.slug === slug);
            return category ? category.name : '';
        },
        
        isCategoryActive(slug) {
            return this.selectedCategory === slug;
        },
        
        // Pagination helpers
        get paginationRange() {
            const current = this.pagination.currentPage;
            const total = this.pagination.totalPages;
            const range = [];
            
            const start = Math.max(1, current - 2);
            const end = Math.min(total, current + 2);
            
            for (let i = start; i <= end; i++) {
                range.push(i);
            }
            
            return range;
        },
        
        // Validation
        isValidPage(page) {
            return page >= 1 && page <= this.pagination.totalPages;
        },
        
        // State checks
        hasNextPage() {
            return this.pagination.hasNext;
        },
        
        hasPrevPage() {
            return this.pagination.hasPrev;
        },
        
        isFirstPage() {
            return this.$store.blog.isFirstPage;
        },
        
        isLastPage() {
            return this.$store.blog.isLastPage;
        }
    };
}

// Register component with Alpine
document.addEventListener('alpine:init', () => {
    Alpine.data('blogComponent', blogComponent);
});

export default blogComponent;