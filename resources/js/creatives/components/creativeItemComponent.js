export const creativeItemComponent = () => ({
    creative: null,
    viewMode: 'grid',
    selected: false,
    
    init() {
        // Получаем данные из переданных параметров
        this.creative = this.$el.dataset.creative ? JSON.parse(this.$el.dataset.creative) : null;
        this.viewMode = this.$el.dataset.viewMode || 'grid';
        this.selected = this.$el.dataset.selected === 'true';
    },
    
    get isImage() {
        if (!this.creative?.file_type) return false;
        return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(
            this.creative.file_type.toLowerCase()
        );
    },
    
    get isVideo() {
        if (!this.creative?.file_type) return false;
        return ['mp4', 'webm', 'mov', 'avi', 'mkv'].includes(
            this.creative.file_type.toLowerCase()
        );
    },
    
    get fileSize() {
        if (!this.creative?.file_size) return '';
        
        const bytes = parseInt(this.creative.file_size);
        if (bytes === 0) return '0 B';
        
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    },
    
    get dimensions() {
        if (this.creative?.width && this.creative?.height) {
            return `${this.creative.width}×${this.creative.height}`;
        }
        return '';
    },
    
    get formatDisplay() {
        return this.creative?.file_type?.toUpperCase() || '';
    },
    
    get previewUrl() {
        return this.creative?.preview_url || this.creative?.file_url || '';
    },
    
    get downloadUrl() {
        return this.creative?.download_url || this.creative?.file_url || '';
    },
    
    toggleSelection() {
        this.selected = !this.selected;
        this.$dispatch('creative-selection-changed', {
            id: this.creative.id,
            selected: this.selected
        });
    },
    
    openDetails() {
        this.$store.creatives.openDetails(this.creative);
    },
    
    async downloadFile() {
        if (!this.downloadUrl) return;
        
        try {
            const response = await fetch(this.downloadUrl);
            const blob = await response.blob();
            
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = this.creative.original_name || `creative_${this.creative.id}`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } catch (error) {
            console.error('Download error:', error);
            alert('Ошибка при скачивании файла');
        }
    },
    
    copyFileUrl() {
        if (!this.creative?.file_url) return;
        
        navigator.clipboard.writeText(this.creative.file_url).then(() => {
            this.$dispatch('show-toast', {
                type: 'success',
                message: 'Ссылка скопирована в буфер обмена'
            });
        }).catch(() => {
            // Fallback для старых браузеров
            const textArea = document.createElement('textarea');
            textArea.value = this.creative.file_url;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            
            this.$dispatch('show-toast', {
                type: 'success',
                message: 'Ссылка скопирована в буфер обмена'
            });
        });
    },
    
    openInNewTab() {
        if (this.creative?.file_url) {
            window.open(this.creative.file_url, '_blank');
        }
    },
    
    getCardClasses() {
        const baseClasses = 'card h-100 creative-item';
        const selectedClasses = this.selected ? 'border-primary shadow-sm' : '';
        return `${baseClasses} ${selectedClasses}`;
    },
    
    getListItemClasses() {
        const baseClasses = 'list-group-item d-flex align-items-center creative-item';
        const selectedClasses = this.selected ? 'list-group-item-primary' : '';
        return `${baseClasses} ${selectedClasses}`;
    },
    
    formatDate(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - date);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays === 1) return 'Вчера';
        if (diffDays <= 7) return `${diffDays} дней назад`;
        
        return date.toLocaleDateString('ru-RU', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    },
    
    truncateText(text, maxLength = 50) {
        if (!text || text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }
});