export const creativesStore = {
  loading: false,
  error: null,

  currentTab: 'push',
  availableTabs: ['push', 'inpage', 'facebook', 'tiktok'],

  creatives: [],
  totalPages: 1,
  currentPage: 1,
  perPage: 12, // Значение по умолчанию для фильтра "На странице"
  totalCount: 0,
  tabCounts: {},

  filters: {
    search: '',
    category: '',
    dateFrom: '',
    dateTo: '',
    sortBy: 'created_at',
    sortOrder: 'desc',
  },

  // Синхронизированные поля с фильтр компонентом
  searchQuery: '',
  selectedCountry: '',

  selectedCreative: null,
  detailsPanelOpen: false,

  cache: new Map(),

  init() {
    this.loadFiltersFromUrl();
    this.loadTabCounts();
    // Загружаем начальные данные креативов
    this.loadCreatives();
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
      // Включаем автозагрузку при смене вкладки
      this.loadCreatives();
    }
  },

  // Новый метод для обновления perPage
  setPerPage(newPerPage) {
    const perPageInt = parseInt(newPerPage);
    if (perPageInt && perPageInt > 0 && perPageInt !== this.perPage) {
      this.perPage = perPageInt;
      this.resetPagination(); // Сбрасываем на первую страницу
      this.updateUrl();
      this.loadCreatives();
    }
  },

  setTabCounts(counts) {
    this.tabCounts = counts || {};
  },

  getTabCountsFromWindow() {
    if (window.creativesTabCounts) {
      this.setTabCounts(window.creativesTabCounts);
      return true;
    }
    return false;
  },

  setCreatives(data) {
    this.creatives = data.data || [];
    this.totalPages = data.last_page || 1;
    this.currentPage = data.current_page || 1;
    this.totalCount = data.total || 0;
    this.perPage = data.per_page || this.perPage;

    // Обновляем счетчики вкладок если они пришли с сервера
    if (data.tab_counts) {
      this.tabCounts = data.tab_counts;
    }
  },

  updateFilters(newFilters) {
    this.filters = { ...this.filters, ...newFilters };
    this.resetPagination();
    this.updateUrl();
  },

  updateSearchQuery(value) {
    this.searchQuery = value;
    this.filters.search = value;
    this.resetPagination();
    this.updateUrl();
    this.loadCreatives();
  },

  updateSelectedCountry(value) {
    this.selectedCountry = value;
    this.filters.category = value;
    this.resetPagination();
    this.updateUrl();
    this.loadCreatives();
  },

  setPage(page) {
    if (page >= 1 && page <= this.totalPages) {
      this.currentPage = page;
      this.updateUrl();
      // Включаем автозагрузку при смене страницы
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
      perPage: this.perPage, // Добавляем perPage в ключ кэша
      ...this.filters,
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

    // Загружаем perPage из URL или query параметра per_page
    const perPage = parseInt(params.get('perPage') || params.get('per_page'));
    if (perPage && perPage > 0) {
      this.perPage = perPage;
    }

    this.filters = { ...this.filters, ...filters };

    // Синхронизируем с полями компонентов
    this.searchQuery = filters.search || '';
    this.selectedCountry = filters.category || '';
  },

  updateUrl() {
    const params = new URLSearchParams();

    params.set('tab', this.currentTab);
    params.set('page', this.currentPage.toString());
    params.set('perPage', this.perPage.toString()); // Добавляем perPage в URL

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
      const params = new URLSearchParams({
        tab: this.currentTab,
        page: this.currentPage.toString(),
        per_page: this.perPage.toString(),
        ...Object.fromEntries(
          Object.entries(this.filters).filter(
            ([key, value]) => value && value.toString().trim() !== ''
          )
        ),
      });

      const response = await fetch(`/api/creatives?${params.toString()}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content'),
        },
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
  },

  async loadTabCounts() {
    // TODO: add loading state
    // TODO: add error handling
    // TODO: add fallback to window.creativesTabCounts
    try {
      const response = await fetch('/api/creatives/tab-counts', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content'),
        },
      });

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      this.setTabCounts(data);
    } catch (error) {
      console.error('Error loading tab counts:', error);
      // Fallback: пытаемся получить данные из window
      if (!this.getTabCountsFromWindow()) {
        console.warn('No tab counts available from API or window');
      }
    }
  },
};
