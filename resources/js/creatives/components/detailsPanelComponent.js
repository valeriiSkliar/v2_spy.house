export const detailsPanelComponent = () => ({
    isOpen: false,
    creative: null,
    activeTab: 'info',
    editMode: false,
    editData: {},
    
    tabs: [
        { id: 'info', name: 'Информация', icon: 'fas fa-info-circle' },
        { id: 'preview', name: 'Предпросмотр', icon: 'fas fa-eye' },
        { id: 'history', name: 'История', icon: 'fas fa-history' },
        { id: 'analytics', name: 'Аналитика', icon: 'fas fa-chart-bar' }
    ],
    
    init() {
        this.$watch('$store.creatives.detailsPanelOpen', (isOpen) => {
            this.isOpen = isOpen;
            if (isOpen) {
                this.creative = this.$store.creatives.selectedCreative;
                this.loadCreativeData();
            }
        });
        
        this.$watch('$store.creatives.selectedCreative', (creative) => {
            this.creative = creative;
            if (creative) {
                this.loadCreativeData();
            }
        });
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
        return 'Не определены';
    },
    
    loadCreativeData() {
        if (!this.creative) return;
        
        this.editData = {
            name: this.creative.name || '',
            description: this.creative.description || '',
            category: this.creative.category || '',
            tags: this.creative.tags || []
        };
    },
    
    close() {
        this.$store.creatives.closeDetails();
        this.activeTab = 'info';
        this.editMode = false;
    },
    
    setActiveTab(tabId) {
        this.activeTab = tabId;
    },
    
    toggleEditMode() {
        this.editMode = !this.editMode;
        if (this.editMode) {
            this.loadCreativeData();
        }
    },
    
    async saveChanges() {
        if (!this.creative) return;
        
        try {
            const response = await fetch(`/api/creatives/${this.creative.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                body: JSON.stringify(this.editData)
            });
            
            if (response.ok) {
                const updatedCreative = await response.json();
                this.creative = updatedCreative;
                this.$store.creatives.selectedCreative = updatedCreative;
                this.editMode = false;
                
                this.$dispatch('show-toast', {
                    type: 'success',
                    message: 'Изменения сохранены'
                });
                
                // Обновляем список креативов
                this.$store.creatives.loadCreatives();
            }
        } catch (error) {
            console.error('Save error:', error);
            this.$dispatch('show-toast', {
                type: 'error',
                message: 'Ошибка при сохранении изменений'
            });
        }
    },
    
    cancelEdit() {
        this.editMode = false;
        this.loadCreativeData();
    },
    
    async downloadFile() {
        if (!this.creative?.file_url) return;
        
        try {
            const response = await fetch(this.creative.file_url);
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
            this.$dispatch('show-toast', {
                type: 'error',
                message: 'Ошибка при скачивании файла'
            });
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
    
    async deleteCreative() {
        if (!this.creative) return;
        
        if (!confirm('Вы уверены, что хотите удалить этот креатив?')) {
            return;
        }
        
        try {
            const response = await fetch(`/api/creatives/${this.creative.id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            
            if (response.ok) {
                this.close();
                this.$store.creatives.loadCreatives();
                
                this.$dispatch('show-toast', {
                    type: 'success',
                    message: 'Креатив удален'
                });
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.$dispatch('show-toast', {
                type: 'error',
                message: 'Ошибка при удалении креатива'
            });
        }
    },
    
    formatDate(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        return date.toLocaleString('ru-RU', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    addTag(tag) {
        if (tag && !this.editData.tags.includes(tag)) {
            this.editData.tags.push(tag);
        }
    },
    
    removeTag(index) {
        this.editData.tags.splice(index, 1);
    }
});