export const creativesStore = {
    loading: false,
    error: null,
    
    currentTab: 'facebook',
    availableTabs: ['facebook', 'tiktok', 'telegram', 'other'],
    
    creatives: [],
    totalPages: 1,
    currentPage: 1,
    perPage: 20,
    totalCount: 0,
    
    filters: {
        search: '',
        category: '',
        dateFrom: '',
        dateTo: '',
        sortBy: 'created_at',
        sortOrder: 'desc'
    },
    
    selectedCreative: null,
    detailsPanelOpen: false,
    
    cache: new Map(),
    
    init() {
        this.loadFiltersFromUrl();
    },
    
    setLoading(loading) {
        this.loading = loading;
    },
    
    setError(error) {
        this.error = error;
    },
    
    clearError() {
        this.error = null;
    },
    
    setTab(tab) {
        if (this.availableTabs.includes(tab)) {
            this.currentTab = tab;
            this.resetPagination();
            this.loadCreatives();
        }
    },
    
    setCreatives(data) {
        this.creatives = data.data || [];
        this.totalPages = data.last_page || 1;
        this.currentPage = data.current_page || 1;
        this.totalCount = data.total || 0;
        this.perPage = data.per_page || 20;
    },
    
    updateFilters(newFilters) {
        this.filters = { ...this.filters, ...newFilters };
        this.resetPagination();
        this.updateUrl();
    },
    
    setPage(page) {
        if (page >= 1 && page <= this.totalPages) {
            this.currentPage = page;
            this.updateUrl();
            this.loadCreatives();
        }
    },
    
    resetPagination() {
        this.currentPage = 1;
    },
    
    openDetails(creative) {
        this.selectedCreative = creative;
        this.detailsPanelOpen = true;
    },
    
    closeDetails() {
        this.selectedCreative = null;
        this.detailsPanelOpen = false;
    },
    
    getCacheKey() {
        const params = {
            tab: this.currentTab,
            page: this.currentPage,
            ...this.filters
        };
        return JSON.stringify(params);
    },
    
    getFromCache(key) {
        return this.cache.get(key);
    },
    
    setCache(key, data) {
        if (this.cache.size > 50) {
            const firstKey = this.cache.keys().next().value;
            this.cache.delete(firstKey);
        }
        this.cache.set(key, data);
    },
    
    loadFiltersFromUrl() {
        const params = new URLSearchParams(window.location.search);
        const filters = {};
        
        ['search', 'category', 'dateFrom', 'dateTo', 'sortBy', 'sortOrder'].forEach(key => {
            const value = params.get(key);
            if (value) filters[key] = value;
        });
        
        const tab = params.get('tab');
        if (tab && this.availableTabs.includes(tab)) {
            this.currentTab = tab;
        }
        
        const page = parseInt(params.get('page'));
        if (page && page > 0) {
            this.currentPage = page;
        }
        
        this.filters = { ...this.filters, ...filters };
    },
    
    updateUrl() {
        const params = new URLSearchParams();
        
        params.set('tab', this.currentTab);
        params.set('page', this.currentPage.toString());
        
        Object.entries(this.filters).forEach(([key, value]) => {
            if (value && value.toString().trim() !== '') {
                params.set(key, value.toString());
            }
        });
        
        const newUrl = `${window.location.pathname}?${params.toString()}`;
        window.history.pushState({}, '', newUrl);
    },
    
    async loadCreatives() {
        const cacheKey = this.getCacheKey();
        const cached = this.getFromCache(cacheKey);
        
        if (cached) {
            this.setCreatives(cached);
            return;
        }
        
        this.setLoading(true);
        this.clearError();
        
        try {
            const response = await fetch('/api/creatives', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({
                    tab: this.currentTab,
                    page: this.currentPage,
                    per_page: this.perPage,
                    ...this.filters
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            this.setCreatives(data);
            this.setCache(cacheKey, data);
            
        } catch (error) {
            console.error('Error loading creatives:', error);
            this.setError('Ошибка загрузки креативов. Попробуйте позже.');
        } finally {
            this.setLoading(false);
        }
    }
};