/**
 * Blog State Store
 * Centralized state management for blog functionality
 * Replaces global variables and provides reactive state
 */
export const blogStore = {
    // Loading state
    loading: false,
    currentRequest: null,
    retryCount: 0,
    
    // Content data
    articles: [],
    categories: [],
    currentCategory: null,
    
    // Pagination
    pagination: {
        currentPage: 1,
        totalPages: 1,
        hasPagination: false,
        hasNext: false,
        hasPrev: false
    },
    
    // Filters (will be made persistent after Alpine initialization)
    filters: {
        page: 1,
        category: '',
        search: '',
        sort: 'latest',
        direction: 'desc'
    },
    
    // UI state
    ui: {
        showSidebar: true,
        activeTab: 'all',
        scrollPosition: 0
    },
    
    // Statistics
    stats: {
        totalCount: 0,
        currentCount: 0,
        loadTime: 0
    },
    
    // Initialize method to set up persistence after Alpine is ready
    init() {
        // Load persisted filters from sessionStorage
        const persistedFilters = sessionStorage.getItem('blog_filters');
        if (persistedFilters) {
            try {
                this.filters = { ...this.filters, ...JSON.parse(persistedFilters) };
            } catch (e) {
                console.warn('Could not parse persisted blog filters:', e);
            }
        }
    },
    
    // Save filters to sessionStorage
    persistFilters() {
        try {
            sessionStorage.setItem('blog_filters', JSON.stringify(this.filters));
        } catch (e) {
            console.warn('Could not persist blog filters:', e);
        }
    },
    
    // Actions
    setLoading(state) {
        this.loading = state;
    },
    
    setCurrentRequest(request) {
        this.currentRequest = request;
    },
    
    setRetryCount(count) {
        this.retryCount = count;
    },
    
    setArticles(articles) {
        this.articles = articles;
    },
    
    setCategories(categories) {
        this.categories = categories;
    },
    
    setCurrentCategory(category) {
        this.currentCategory = category;
    },
    
    setPagination(paginationData) {
        this.pagination = {
            ...this.pagination,
            ...paginationData
        };
    },
    
    setFilters(newFilters) {
        this.filters = { 
            ...this.filters, 
            ...newFilters 
        };
        // Persist filters whenever they change
        this.persistFilters();
    },
    
    resetFilters() {
        this.filters = {
            page: 1,
            category: '',
            search: '',
            sort: 'latest',
            direction: 'desc'
        };
        // Persist reset filters
        this.persistFilters();
    },
    
    setUIState(newUIState) {
        this.ui = { 
            ...this.ui, 
            ...newUIState 
        };
    },
    
    setStats(newStats) {
        this.stats = { 
            ...this.stats, 
            ...newStats 
        };
    },
    
    // Computed properties
    get isFirstPage() {
        return this.pagination.currentPage === 1;
    },
    
    get isLastPage() {
        return this.pagination.currentPage === this.pagination.totalPages;
    },
    
    get hasResults() {
        return this.stats.currentCount > 0;
    },
    
    get isFiltered() {
        return this.filters.category || this.filters.search || this.filters.sort !== 'latest';
    },
    
    get filterParams() {
        const params = new URLSearchParams();
        
        if (this.filters.page > 1) params.set('page', this.filters.page);
        if (this.filters.category) params.set('category', this.filters.category);
        if (this.filters.search) params.set('search', this.filters.search);
        if (this.filters.sort !== 'latest') params.set('sort', this.filters.sort);
        if (this.filters.direction !== 'desc') params.set('direction', this.filters.direction);
        
        return params;
    },
    
    // Utility methods
    updateFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        
        this.setFilters({
            page: parseInt(urlParams.get('page')) || 1,
            category: urlParams.get('category') || '',
            search: urlParams.get('search') || '',
            sort: urlParams.get('sort') || 'latest',
            direction: urlParams.get('direction') || 'desc'
        });
    },
    
    buildRequestURL(baseUrl) {
        const requestUrl = new URL(baseUrl, window.location.origin);
        
        // Add filter parameters
        this.filterParams.forEach((value, key) => {
            requestUrl.searchParams.set(key, value);
        });
        
        return requestUrl.toString();
    },
    
    resetToPage(page = 1) {
        this.setFilters({ 
            ...this.filters, 
            page 
        });
    },
    
    clearSearch() {
        this.setFilters({ 
            ...this.filters, 
            search: '', 
            page: 1 
        });
    },
    
    clearCategory() {
        this.setFilters({ 
            ...this.filters, 
            category: '', 
            page: 1 
        });
    },
    
    // Validation methods
    validateFilters() {
        const { page, search, category } = this.filters;
        
        // Validate page number
        if (page < 1 || page > 1000) return false;
        
        // Validate search query
        if (search && (search.length > 255 || search.length < 1)) return false;
        
        // Validate category slug
        if (category && !/^[a-zA-Z0-9\-_]*$/.test(category)) return false;
        
        return true;
    },
    
    // State reset methods
    resetState() {
        this.loading = false;
        this.currentRequest = null;
        this.retryCount = 0;
        this.articles = [];
        this.currentCategory = null;
        this.pagination = {
            currentPage: 1,
            totalPages: 1,
            hasPagination: false,
            hasNext: false,
            hasPrev: false
        };
        this.stats = {
            totalCount: 0,
            currentCount: 0,
            loadTime: 0
        };
    }
};

// Export function to register store with Alpine after initialization
export function initBlogStore(Alpine) {
    Alpine.store('blog', blogStore);
    // Initialize store after registration
    blogStore.init();
}