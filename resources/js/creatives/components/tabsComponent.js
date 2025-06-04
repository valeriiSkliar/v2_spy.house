export const tabsComponent = () => ({
    activeTab: 'facebook',
    
    tabs: [
        { 
            id: 'facebook', 
            name: 'Facebook', 
            icon: 'fab fa-facebook-f',
            color: '#1877f2'
        },
        { 
            id: 'tiktok', 
            name: 'TikTok', 
            icon: 'fab fa-tiktok',
            color: '#000000'
        },
        { 
            id: 'telegram', 
            name: 'Telegram', 
            icon: 'fab fa-telegram-plane',
            color: '#0088cc'
        },
        { 
            id: 'other', 
            name: 'Другие', 
            icon: 'fas fa-globe',
            color: '#6c757d'
        }
    ],
    
    init() {
        this.activeTab = this.$store.creatives.currentTab;
        
        this.$watch('activeTab', (newTab) => {
            this.$store.creatives.setTab(newTab);
        });
    },
    
    setActiveTab(tabId) {
        if (this.activeTab !== tabId) {
            this.activeTab = tabId;
        }
    },
    
    isActiveTab(tabId) {
        return this.activeTab === tabId;
    },
    
    getTabCount(tabId) {
        return this.$store.creatives.totalCount || 0;
    },
    
    getTabClasses(tabId) {
        const baseClasses = 'nav-link d-flex align-items-center gap-2 position-relative';
        const activeClasses = this.isActiveTab(tabId) 
            ? 'active border-bottom border-3' 
            : 'text-muted';
        
        return `${baseClasses} ${activeClasses}`;
    },
    
    getTabStyle(tab) {
        if (this.isActiveTab(tab.id)) {
            return `border-color: ${tab.color} !important; color: ${tab.color} !important;`;
        }
        return '';
    }
});