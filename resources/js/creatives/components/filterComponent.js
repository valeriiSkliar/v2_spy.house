export const filterComponent = () => ({
    searchQuery: '',
    selectedCategory: '',
    dateFrom: '',
    dateTo: '',
    sortBy: 'created_at',
    sortOrder: 'desc',
    
    categories: [
        { value: '', label: 'Все категории' },
        { value: 'ecommerce', label: 'E-commerce' },
        { value: 'crypto', label: 'Криптовалюты' },
        { value: 'gambling', label: 'Азартные игры' },
        { value: 'finance', label: 'Финансы' },
        { value: 'health', label: 'Здоровье' },
        { value: 'dating', label: 'Знакомства' },
        { value: 'travel', label: 'Путешествия' },
        { value: 'education', label: 'Образование' },
        { value: 'other', label: 'Другое' }
    ],
    
    sortOptions: [
        { value: 'created_at', label: 'По дате создания' },
        { value: 'updated_at', label: 'По дате обновления' },
        { value: 'name', label: 'По названию' },
        { value: 'size', label: 'По размеру' }
    ],
    
    init() {
        this.loadFromStore();
        this.$watch('searchQuery', () => this.handleFilterChange());
        this.$watch('selectedCategory', () => this.handleFilterChange());
        this.$watch('dateFrom', () => this.handleFilterChange());
        this.$watch('dateTo', () => this.handleFilterChange());
        this.$watch('sortBy', () => this.handleFilterChange());
        this.$watch('sortOrder', () => this.handleFilterChange());
    },
    
    loadFromStore() {
        const store = this.$store.creatives;
        this.searchQuery = store.filters.search;
        this.selectedCategory = store.filters.category;
        this.dateFrom = store.filters.dateFrom;
        this.dateTo = store.filters.dateTo;
        this.sortBy = store.filters.sortBy;
        this.sortOrder = store.filters.sortOrder;
    },
    
    handleFilterChange() {
        const filters = {
            search: this.searchQuery,
            category: this.selectedCategory,
            dateFrom: this.dateFrom,
            dateTo: this.dateTo,
            sortBy: this.sortBy,
            sortOrder: this.sortOrder
        };
        
        this.$store.creatives.updateFilters(filters);
        this.$store.creatives.loadCreatives();
    },
    
    clearFilters() {
        this.searchQuery = '';
        this.selectedCategory = '';
        this.dateFrom = '';
        this.dateTo = '';
        this.sortBy = 'created_at';
        this.sortOrder = 'desc';
    },
    
    toggleSortOrder() {
        this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
    },
    
    applyQuickFilter(type, value) {
        switch (type) {
            case 'today':
                this.dateFrom = new Date().toISOString().split('T')[0];
                this.dateTo = '';
                break;
            case 'week':
                const weekAgo = new Date();
                weekAgo.setDate(weekAgo.getDate() - 7);
                this.dateFrom = weekAgo.toISOString().split('T')[0];
                this.dateTo = '';
                break;
            case 'month':
                const monthAgo = new Date();
                monthAgo.setMonth(monthAgo.getMonth() - 1);
                this.dateFrom = monthAgo.toISOString().split('T')[0];
                this.dateTo = '';
                break;
            case 'category':
                this.selectedCategory = value;
                break;
        }
    }
});