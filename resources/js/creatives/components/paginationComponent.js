export const paginationComponent = () => ({
    currentPage: 1,
    totalPages: 1,
    totalCount: 0,
    perPage: 20,
    showingFrom: 0,
    showingTo: 0,
    
    init() {
        this.$watch('$store.creatives.currentPage', (page) => {
            this.currentPage = page;
            this.updateDisplayInfo();
        });
        
        this.$watch('$store.creatives.totalPages', (pages) => {
            this.totalPages = pages;
            this.updateDisplayInfo();
        });
        
        this.$watch('$store.creatives.totalCount', (count) => {
            this.totalCount = count;
            this.updateDisplayInfo();
        });
        
        this.$watch('$store.creatives.perPage', (perPage) => {
            this.perPage = perPage;
            this.updateDisplayInfo();
        });
        
        this.updateDisplayInfo();
    },
    
    updateDisplayInfo() {
        this.showingFrom = this.totalCount > 0 ? ((this.currentPage - 1) * this.perPage) + 1 : 0;
        this.showingTo = Math.min(this.currentPage * this.perPage, this.totalCount);
    },
    
    get visiblePages() {
        const delta = 2; // Количество страниц слева и справа от текущей
        const range = [];
        const rangeWithDots = [];
        
        for (let i = Math.max(2, this.currentPage - delta); 
             i <= Math.min(this.totalPages - 1, this.currentPage + delta); 
             i++) {
            range.push(i);
        }
        
        if (this.currentPage - delta > 2) {
            rangeWithDots.push(1, '...');
        } else {
            rangeWithDots.push(1);
        }
        
        rangeWithDots.push(...range);
        
        if (this.currentPage + delta < this.totalPages - 1) {
            rangeWithDots.push('...', this.totalPages);
        } else if (this.totalPages > 1) {
            rangeWithDots.push(this.totalPages);
        }
        
        return rangeWithDots.filter((item, index, arr) => {
            return arr.indexOf(item) === index;
        });
    },
    
    get hasPrevious() {
        return this.currentPage > 1;
    },
    
    get hasNext() {
        return this.currentPage < this.totalPages;
    },
    
    get showPagination() {
        return this.totalPages > 1;
    },
    
    goToPage(page) {
        if (typeof page === 'number' && page >= 1 && page <= this.totalPages && page !== this.currentPage) {
            this.$store.creatives.setPage(page);
        }
    },
    
    goToPrevious() {
        if (this.hasPrevious) {
            this.goToPage(this.currentPage - 1);
        }
    },
    
    goToNext() {
        if (this.hasNext) {
            this.goToPage(this.currentPage + 1);
        }
    },
    
    goToFirst() {
        this.goToPage(1);
    },
    
    goToLast() {
        this.goToPage(this.totalPages);
    },
    
    changePerPage(newPerPage) {
        const perPageInt = parseInt(newPerPage);
        if (perPageInt && perPageInt > 0 && perPageInt !== this.perPage) {
            this.$store.creatives.perPage = perPageInt;
            this.$store.creatives.setPage(1); // Сбрасываем на первую страницу
        }
    },
    
    getPageButtonClasses(page) {
        const baseClasses = 'page-link';
        
        if (page === '...') {
            return `${baseClasses} disabled`;
        }
        
        if (page === this.currentPage) {
            return `${baseClasses} active`;
        }
        
        return baseClasses;
    },
    
    getPageItemClasses(page) {
        const baseClasses = 'page-item';
        
        if (page === '...') {
            return `${baseClasses} disabled`;
        }
        
        if (page === this.currentPage) {
            return `${baseClasses} active`;
        }
        
        return baseClasses;
    },
    
    formatDisplayInfo() {
        if (this.totalCount === 0) {
            return 'Нет результатов';
        }
        
        return `Показано ${this.showingFrom}-${this.showingTo} из ${this.totalCount} результатов`;
    }
});