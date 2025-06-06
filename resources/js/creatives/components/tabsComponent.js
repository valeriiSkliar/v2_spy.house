export const tabsComponent = () => ({
  activeTab: 'push', // изменено с 'facebook' на 'push' согласно creativesStore

  // обновленные табы согласно availableTabs из creativesStore и Blade разметке
  tabs: [
    {
      id: 'push',
      name: 'Push',
      routeName: 'push',
    },
    {
      id: 'inpage',
      name: 'Inpage',
      routeName: 'inpage',
    },
    {
      id: 'facebook',
      name: 'Facebook',
      routeName: 'facebook',
    },
    {
      id: 'tiktok',
      name: 'TikTok',
      routeName: 'tiktok',
    },
  ],

  init() {
    // синхронизируем с creativesStore при инициализации
    if (this.$store.creatives) {
      this.activeTab = this.$store.creatives.currentTab;

      // слушаем изменения в store
      this.$watch('$store.creatives.currentTab', newTab => {
        this.activeTab = newTab;
      });
    }

    // слушаем изменения локального activeTab и обновляем store
    this.$watch('activeTab', newTab => {
      if (this.$store.creatives && this.$store.creatives.currentTab !== newTab) {
        this.$store.creatives.setTab(newTab);
      }
    });
  },

  setActiveTab(tabId) {
    // проверяем что таб существует в availableTabs
    const availableTabs = this.$store.creatives?.availableTabs || [
      'push',
      'inpage',
      'facebook',
      'tiktok',
    ];

    if (availableTabs.includes(tabId) && this.activeTab !== tabId) {
      this.activeTab = tabId;

      // обновляем URL программно для SPA поведения
      const currentUrl = new URL(window.location);
      currentUrl.searchParams.set('tab', tabId);
      window.history.pushState({}, '', currentUrl);

      // инициируем загрузку данных для новой вкладки
      if (this.$store.creatives) {
        this.$store.creatives.setTab(tabId);
      }
    }
  },

  isActiveTab(tabId) {
    return this.activeTab === tabId;
  },

  getTabCount(tabId) {
    // основной источник: получаем счетчики из store
    const counts = this.$store.creatives?.tabCounts || {};
    const count = counts[tabId];

    if (count !== undefined && count !== null) {
      return ` ${count}`;
    }

    // fallback: проверяем window.creativesTabCounts
    if (window.creativesTabCounts && window.creativesTabCounts[tabId]) {
      console.log('window.creativesTabCounts', window.creativesTabCounts);
      return ` ${window.creativesTabCounts[tabId]}`;
    }

    // возвращаем пустую строку если нет данных о количестве
    return '';
  },

  // вспомогательный метод для получения информации о табе
  getTabInfo(tabId) {
    return this.tabs.find(tab => tab.id === tabId) || null;
  },

  // метод для получения CSS классов таба (совместимость с существующим кодом)
  getTabClasses(tabId) {
    const baseClasses = 'filter-push__item';
    const activeClasses = this.isActiveTab(tabId) ? 'active' : '';

    return `${baseClasses} ${activeClasses}`.trim();
  },
});
