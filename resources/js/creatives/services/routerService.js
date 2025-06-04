class RouterService {
    constructor() {
        this.router = null;
        this.store = null;
        this.routes = {
            '/creatives': this.handleCreativesRoute.bind(this),
            '/creatives/:tab': this.handleCreativesTabRoute.bind(this),
            '/creatives/:tab/:id': this.handleCreativeDetailRoute.bind(this),
        };
    }
    
    init(store = null) {
        if (typeof Navigo === 'undefined') {
            console.warn('Navigo router not found, URL routing will be limited');
            return;
        }
        
        this.store = store || (window.Alpine && window.Alpine.store('creatives'));
        
        this.router = new Navigo('/', {
            hash: false,
            linksSelector: 'a[data-route]',
            noMatchWarning: false
        });
        
        // Регистрируем маршруты
        Object.entries(this.routes).forEach(([path, handler]) => {
            this.router.on(path, handler);
        });
        
        // Обработчик для неизвестных маршрутов
        this.router.notFound(() => {
            this.navigate('/creatives');
        });
        
        // Запускаем роутер
        this.router.resolve();
        
        // Слушаем изменения в store и обновляем URL
        if (this.store) {
            this.watchStoreChanges();
        }
    }
    
    watchStoreChanges() {
        if (!this.store) return;
        
        // Следим за изменениями активной вкладки
        this.store.$watch('currentTab', (newTab) => {
            this.updateUrlFromState();
        });
        
        // Следим за изменениями страницы
        this.store.$watch('currentPage', () => {
            this.updateUrlFromState();
        });
        
        // Следим за изменениями фильтров
        this.store.$watch('filters', () => {
            this.updateUrlFromState();
        }, { deep: true });
    }
    
    handleCreativesRoute() {
        if (!this.store) return;
        
        // Загружаем креативы с текущими параметрами
        this.store.loadCreatives();
    }
    
    handleCreativesTabRoute(params) {
        if (!this.store) return;
        
        const { tab } = params.data;
        
        if (this.store.availableTabs.includes(tab)) {
            this.store.setTab(tab);
        } else {
            this.navigate('/creatives');
        }
    }
    
    handleCreativeDetailRoute(params) {
        if (!this.store) return;
        
        const { tab, id } = params.data;
        
        // Устанавливаем вкладку
        if (this.store.availableTabs.includes(tab)) {
            this.store.setTab(tab);
        }
        
        // Загружаем детали креатива
        this.loadCreativeDetails(id);
    }
    
    async loadCreativeDetails(id) {
        if (!this.store) return;
        
        try {
            // Проверяем, есть ли креатив в текущем списке
            let creative = this.store.creatives.find(c => c.id == id);
            
            if (!creative) {
                // Загружаем креатив с сервера
                const response = await fetch(`/api/creatives/${id}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    creative = await response.json();
                }
            }
            
            if (creative) {
                this.store.openDetails(creative);
            } else {
                console.warn(`Creative with id ${id} not found`);
                this.navigate(`/creatives/${this.store.currentTab}`);
            }
        } catch (error) {
            console.error('Error loading creative details:', error);
            this.navigate(`/creatives/${this.store.currentTab}`);
        }
    }
    
    updateUrlFromState() {
        if (!this.store || !this.router) return;
        
        const params = new URLSearchParams();
        
        // Добавляем параметры фильтров
        Object.entries(this.store.filters).forEach(([key, value]) => {
            if (value && value.toString().trim() !== '') {
                params.set(key, value.toString());
            }
        });
        
        // Добавляем страницу если не первая
        if (this.store.currentPage > 1) {
            params.set('page', this.store.currentPage.toString());
        }
        
        // Формируем URL
        let url = `/creatives/${this.store.currentTab}`;
        const queryString = params.toString();
        
        if (queryString) {
            url += `?${queryString}`;
        }
        
        // Обновляем URL без перезагрузки страницы
        if (window.location.pathname + window.location.search !== url) {
            this.router.navigate(url, { 
                updateBrowserURL: true,
                callHandler: false 
            });
        }
    }
    
    navigate(path, options = {}) {
        if (!this.router) {
            // Fallback для случаев когда роутер не доступен
            window.location.href = path;
            return;
        }
        
        this.router.navigate(path, {
            updateBrowserURL: true,
            callHandler: true,
            ...options
        });
    }
    
    getCurrentRoute() {
        if (!this.router) {
            return window.location.pathname;
        }
        
        return this.router.getCurrentLocation();
    }
    
    getParams() {
        const urlParams = new URLSearchParams(window.location.search);
        const params = {};
        
        for (const [key, value] of urlParams.entries()) {
            params[key] = value;
        }
        
        return params;
    }
    
    // Вспомогательные методы для навигации
    goToCreatives(tab = null) {
        const targetTab = tab || (this.store ? this.store.currentTab : 'facebook');
        this.navigate(`/creatives/${targetTab}`);
    }
    
    goToCreativeDetails(creative) {
        if (!creative || !creative.id) return;
        
        const tab = this.store ? this.store.currentTab : 'facebook';
        this.navigate(`/creatives/${tab}/${creative.id}`);
    }
    
    goBack() {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            this.goToCreatives();
        }
    }
    
    // Обновление параметров URL без перезагрузки
    updateUrlParams(params) {
        const currentParams = this.getParams();
        const newParams = { ...currentParams, ...params };
        
        // Удаляем пустые параметры
        Object.keys(newParams).forEach(key => {
            if (!newParams[key] || newParams[key].toString().trim() === '') {
                delete newParams[key];
            }
        });
        
        const searchParams = new URLSearchParams(newParams);
        const queryString = searchParams.toString();
        const newUrl = window.location.pathname + (queryString ? `?${queryString}` : '');
        
        window.history.replaceState({}, '', newUrl);
    }
    
    // Загрузка состояния из URL
    loadStateFromUrl() {
        if (!this.store) return;
        
        const params = this.getParams();
        const pathParts = window.location.pathname.split('/').filter(Boolean);
        
        // Определяем активную вкладку из URL
        if (pathParts.length >= 2 && pathParts[0] === 'creatives') {
            const tab = pathParts[1];
            if (this.store.availableTabs.includes(tab)) {
                this.store.currentTab = tab;
            }
        }
        
        // Загружаем фильтры из параметров URL
        const filters = {};
        ['search', 'category', 'dateFrom', 'dateTo', 'sortBy', 'sortOrder'].forEach(key => {
            if (params[key]) {
                filters[key] = params[key];
            }
        });
        
        if (Object.keys(filters).length > 0) {
            this.store.filters = { ...this.store.filters, ...filters };
        }
        
        // Загружаем номер страницы
        if (params.page) {
            const page = parseInt(params.page);
            if (page > 0) {
                this.store.currentPage = page;
            }
        }
    }
    
    destroy() {
        if (this.router) {
            this.router.destroy();
            this.router = null;
        }
        this.store = null;
    }
}

// Экспортируем синглтон
export const routerService = new RouterService();