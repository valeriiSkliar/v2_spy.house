export const creativesListComponent = () => ({
    viewMode: 'grid', // 'grid' or 'list'
    selectedItems: [],
    selectAll: false,
    
    init() {
        this.loadViewMode();
        this.$store.creatives.loadCreatives();
        
        this.$watch('selectAll', (value) => {
            if (value) {
                this.selectedItems = this.creatives.map(item => item.id);
            } else {
                this.selectedItems = [];
            }
        });
    },
    
    get creatives() {
        return this.$store.creatives.creatives;
    },
    
    get loading() {
        return this.$store.creatives.loading;
    },
    
    get error() {
        return this.$store.creatives.error;
    },
    
    get hasCreatives() {
        return this.creatives && this.creatives.length > 0;
    },
    
    get totalCount() {
        return this.$store.creatives.totalCount;
    },
    
    setViewMode(mode) {
        this.viewMode = mode;
        localStorage.setItem('creatives_view_mode', mode);
    },
    
    loadViewMode() {
        const saved = localStorage.getItem('creatives_view_mode');
        if (saved && ['grid', 'list'].includes(saved)) {
            this.viewMode = saved;
        }
    },
    
    toggleItemSelection(itemId) {
        const index = this.selectedItems.indexOf(itemId);
        if (index > -1) {
            this.selectedItems.splice(index, 1);
        } else {
            this.selectedItems.push(itemId);
        }
        
        this.updateSelectAllState();
    },
    
    isItemSelected(itemId) {
        return this.selectedItems.includes(itemId);
    },
    
    updateSelectAllState() {
        this.selectAll = this.selectedItems.length === this.creatives.length && this.creatives.length > 0;
    },
    
    clearSelection() {
        this.selectedItems = [];
        this.selectAll = false;
    },
    
    openCreativeDetails(creative) {
        this.$store.creatives.openDetails(creative);
    },
    
    async downloadSelected() {
        if (this.selectedItems.length === 0) return;
        
        try {
            const response = await fetch('/api/creatives/download', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({
                    ids: this.selectedItems
                })
            });
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `creatives_${Date.now()}.zip`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                this.clearSelection();
            }
        } catch (error) {
            console.error('Download error:', error);
            alert('Ошибка при скачивании файлов');
        }
    },
    
    async deleteSelected() {
        if (this.selectedItems.length === 0) return;
        
        if (!confirm(`Удалить ${this.selectedItems.length} креативов?`)) {
            return;
        }
        
        try {
            const response = await fetch('/api/creatives/delete', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify({
                    ids: this.selectedItems
                })
            });
            
            if (response.ok) {
                this.clearSelection();
                this.$store.creatives.loadCreatives();
            }
        } catch (error) {
            console.error('Delete error:', error);
            alert('Ошибка при удалении креативов');
        }
    },
    
    getGridClasses() {
        return this.viewMode === 'grid' 
            ? 'row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-3'
            : 'd-none';
    },
    
    getListClasses() {
        return this.viewMode === 'list' 
            ? 'list-group list-group-flush'
            : 'd-none';
    }
});