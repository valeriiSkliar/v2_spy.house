import { FilterOption, FilterState } from '@/types/creatives';
import { defineStore } from 'pinia';
import { computed, reactive, ref } from 'vue';


export const useFiltersStore = defineStore('filters', () => {
  // Состояние фильтров
  const filters = reactive<FilterState>({
    isDetailedVisible: false,
    searchKeyword: '',
    country: 'All Categories',
    dateCreation: 'Date of creation',
    sortBy: 'By creation date',
    periodDisplay: 'Period of display',
    advertisingNetworks: [],
    languages: [],
    operatingSystems: [],
    browsers: [],
    devices: [],
    imageSizes: [],
    onlyAdult: false,
    savedSettings: []
  });

  // Опции для селектов
  const countryOptions = ref<FilterOption[]>([
    { value: 'all', label: 'All Categories' },
    { value: 'advertising', label: 'Advertising Networks' },
    { value: 'affiliate', label: 'Affiliate Programs' },
    { value: 'trackers', label: 'Trackers' },
    { value: 'hosting', label: 'Hosting' },
    { value: 'domain', label: 'Domain Registrars' },
    { value: 'spy', label: 'SPY Services' },
    { value: 'proxy', label: 'Proxy and VPN Services' },
    { value: 'browsers', label: 'Anti-detection Browsers' },
    { value: 'accounts', label: 'Account Purchase and Rental' },
    { value: 'apps', label: 'Purchase and Rental of Applications' },
    { value: 'notifications', label: 'Notification and Newsletter Services' },
    { value: 'payments', label: 'Payment Services' },
    { value: 'other', label: 'Other Services and Utilities' }
  ]);

  const sortOptions = ref<FilterOption[]>([
    { value: 'creation', label: 'By creation date' },
    { value: 'activity', label: 'By days of activity' }
  ]);

  const dateRanges = ref<FilterOption[]>([
    { value: 'today', label: 'Today' },
    { value: 'yesterday', label: 'Yesterday' },
    { value: 'last7', label: 'Last 7 days' },
    { value: 'last30', label: 'Last 30 days' },
    { value: 'thisMonth', label: 'This month' },
    { value: 'lastMonth', label: 'Last month' },
    { value: 'custom', label: 'Custom Range' }
  ]);

  // Методы
  function toggleDetailedFilters(): void {
    filters.isDetailedVisible = !filters.isDetailedVisible;
  }

  function setSearchKeyword(keyword: string): void {
    filters.searchKeyword = keyword;
  }

  function setCountry(country: string): void {
    filters.country = country;
  }

  function setSortBy(sortBy: string): void {
    filters.sortBy = sortBy;
  }

  function setDateCreation(date: string): void {
    filters.dateCreation = date;
  }

  function setPeriodDisplay(period: string): void {
    filters.periodDisplay = period;
  }

  function toggleAdultFilter(): void {
    filters.onlyAdult = !filters.onlyAdult;
  }

  function addToMultiSelect(field: keyof FilterState, value: string): void {
    const currentValues = filters[field] as string[];
    if (!currentValues.includes(value)) {
      currentValues.push(value);
    }
  }

  function removeFromMultiSelect(field: keyof FilterState, value: string): void {
    const currentValues = filters[field] as string[];
    const index = currentValues.indexOf(value);
    if (index > -1) {
      currentValues.splice(index, 1);
    }
  }

  function resetFilters(): void {
    filters.searchKeyword = '';
    filters.country = 'All Categories';
    filters.dateCreation = 'Date of creation';
    filters.sortBy = 'By creation date';
    filters.periodDisplay = 'Period of display';
    filters.advertisingNetworks = [];
    filters.languages = [];
    filters.operatingSystems = [];
    filters.browsers = [];
    filters.devices = [];
    filters.imageSizes = [];
    filters.onlyAdult = false;
    filters.savedSettings = [];
  }

  function saveSettings(): void {
    // Логика сохранения настроек
    console.log('Saving filters:', filters);
    // Здесь можно отправить данные на сервер
  }

  // Computed свойства
  const hasActiveFilters = computed(() => {
    return filters.searchKeyword !== '' ||
           filters.country !== 'All Categories' ||
           filters.advertisingNetworks.length > 0 ||
           filters.languages.length > 0 ||
           filters.operatingSystems.length > 0 ||
           filters.browsers.length > 0 ||
           filters.devices.length > 0 ||
           filters.imageSizes.length > 0 ||
           filters.onlyAdult ||
           filters.savedSettings.length > 0;
  });

  return {
    filters,
    countryOptions,
    sortOptions,
    dateRanges,
    hasActiveFilters,
    toggleDetailedFilters,
    setSearchKeyword,
    setCountry,
    setSortBy,
    setDateCreation,
    setPeriodDisplay,
    toggleAdultFilter,
    addToMultiSelect,
    removeFromMultiSelect,
    resetFilters,
    saveSettings
  };
});