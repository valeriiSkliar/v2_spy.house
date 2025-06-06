export const filterComponent = () => ({
  // Состояние фильтров
  searchQuery: '',
  selectedCountry: '',
  dateFrom: '',
  dateTo: '',
  sortBy: 'by-creation-date',

  // Опции для селекта "На странице" (используются baseSelect компонентом)
  perPageOptions: [
    { value: '12', order: '1', label: '12' },
    { value: '24', order: '2', label: '24' },
    { value: '48', order: '3', label: '48' },
    { value: '96', order: '4', label: '96' },
  ],

  init() {
    // Инициализация фильтров
    this.loadFromStore();
  },

  loadFromStore() {
    const store = this.$store.creatives;
    if (store) {
      // Загружаем только те фильтры, которые не синхронизируются автоматически через baseSelect
      this.searchQuery = store.searchQuery || '';
      this.selectedCountry = store.selectedCountry || '';
      this.dateFrom = store.dateFrom || '';
      this.dateTo = store.dateTo || '';
      this.sortBy = store.sortBy || 'by-creation-date';
    }
  },

  // Метод для получения текущего состояния фильтров
  getFiltersState() {
    return {
      perPage: this.$store.creatives?.perPage || 12,
      searchQuery: this.searchQuery,
      selectedCountry: this.selectedCountry,
      dateFrom: this.dateFrom,
      dateTo: this.dateTo,
      sortBy: this.sortBy,
    };
  },

  // Метод для сброса фильтров
  resetFilters() {
    this.searchQuery = '';
    this.selectedCountry = '';
    this.dateFrom = '';
    this.dateTo = '';
    this.sortBy = 'by-creation-date';

    // Обновляем store
    if (this.$store.creatives) {
      this.$store.creatives.perPage = 12;
      this.$store.creatives.searchQuery = '';
      this.$store.creatives.selectedCountry = '';
      this.$store.creatives.dateFrom = '';
      this.$store.creatives.dateTo = '';
      this.$store.creatives.sortBy = 'by-creation-date';
      this.$store.creatives.resetPagination();
    }
  },

  // Обновление фильтра поиска
  updateSearchQuery(value) {
    this.searchQuery = value;
    if (this.$store.creatives && typeof this.$store.creatives.updateSearchQuery === 'function') {
      this.$store.creatives.updateSearchQuery(value);
    }
  },

  // Обновление фильтра страны
  updateSelectedCountry(value) {
    this.selectedCountry = value;
    if (
      this.$store.creatives &&
      typeof this.$store.creatives.updateSelectedCountry === 'function'
    ) {
      this.$store.creatives.updateSelectedCountry(value);
    }
  },

  // Обновление фильтра сортировки
  updateSortBy(value) {
    this.sortBy = value;
    if (this.$store.creatives) {
      this.$store.creatives.sortBy = value;
      this.$store.creatives.resetPagination();
    }
  },
});
