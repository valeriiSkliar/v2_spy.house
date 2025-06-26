// types/creatives.ts
// Единая система типов для модуля креативов

/**
 * Базовые типы
 */
export type CreativeId = number;
export type CountryCode = string;
export type LanguageCode = string;
export type TabValue = 'push' | 'inpage' | 'facebook' | 'tiktok';
export type SortValue = 'creation' | 'activity' | 'popularity' | 'byCreationDate' | 'byActivity' | 'byPopularity' | 'default';
/**
 * Тип для значений диапазона дат
 * Поддерживает предустановленные диапазоны и custom диапазоны в формате custom_YYYY-MM-DD_to_YYYY-MM-DD
 * Одиночные даты НЕ поддерживаются
 */
export type DateRangeValue = 'today' | 'yesterday' | 'last7' | 'last30' | 'last90' | 'thisMonth' | 'lastMonth' | 'thisYear' | 'lastYear' | 'default' | string;

/**
 * Основная модель креатива
 */
export interface Creative {
  id: CreativeId;
  name: string;
  category: string;
  country: CountryCode;
  file_url: string;
  preview_url?: string;
  created_at: string;
  activity_date?: string;
  advertising_networks?: string[];
  languages?: LanguageCode[];
  operating_systems?: string[];
  browsers?: string[];
  devices?: string[];
  image_sizes?: string[];
  is_adult?: boolean;
  // Computed свойства (добавляются на frontend)
  displayName?: string;
  isRecent?: boolean;
  isFavorite?: boolean;
}

/**
 * Фильтры для API запросов
 */
export interface CreativesFilters {
  searchKeyword?: string;
  country?: CountryCode;
  dateCreation?: DateRangeValue;
  sortBy?: SortValue;
  periodDisplay?: DateRangeValue;
  advertisingNetworks?: string[];
  languages?: LanguageCode[];
  operatingSystems?: string[];
  browsers?: string[];
  devices?: string[];
  imageSizes?: string[];
  onlyAdult?: boolean;
  page?: number;
  perPage?: number;
  activeTab?: TabValue;
}

/**
 * Состояние фильтров в UI
 */
export interface FilterState {
  isDetailedVisible: boolean;
  searchKeyword: string;
  country: string;
  dateCreation: string;
  sortBy: string;
  periodDisplay: string;
  advertisingNetworks: string[];
  languages: string[];
  operatingSystems: string[];
  browsers: string[];
  devices: string[];
  imageSizes: string[];
  onlyAdult: boolean;
  savedSettings: string[];
}

/**
 * Состояние вкладок
 */
export interface TabsState {
  activeTab: TabValue;
  availableTabs: TabValue[];
  tabCounts: Record<string, string | number>;
}

/**
 * Опция для селектов
 */
export interface FilterOption {
  value: string;
  label: string;
  count?: number;
}

/**
 * Опция для вкладок
 */
export interface TabOption {
  value: TabValue;
  label: string;
  count: string | number;
}

/**
 * Пагинация
 */
export interface Pagination {
  total: number;
  perPage: number;
  currentPage: number;
  lastPage: number;
  from: number;
  to: number;
}

/**
 * Метаданные запроса
 */
export interface RequestMeta {
  hasSearch: boolean;
  activeFiltersCount: number;
  cacheKey: string;
  appliedFilters?: any;
}

/**
 * Ответ от API креативов
 */
export interface CreativesResponse {
  status: string;
  data: {
    items: Creative[];
    pagination: Pagination;
    meta: RequestMeta;
  };
}

/**
 * Обработанные данные креативов
 */
export interface ProcessedCreativesData {
  items: Creative[];
  pagination: Pagination;
  meta: Omit<RequestMeta, 'appliedFilters'>;
}

/**
 * Конфигурация сервиса
 */
export interface CreativesServiceConfig {
  defaultCacheTtl: number;
  searchCacheTtl: number;
  debounceDelay: number;
  maxCacheKeyLength: number;
  retryAttempts?: number;
  retryDelay?: number;
}

/**
 * Конфигурация кэша
 */
export interface CacheConfig {
  id: string;
  cache: {
    ttl: number;
    methods: readonly ['get'];
  };
}

/**
 * Результат валидации
 */
export interface ValidationResult {
  isValid: boolean;
  errors: Record<string, string>;
  rejectedValues: string[];
  sanitizedCount: number;
  originalCount: number;
  validatedCount: number;
}

/**
 * Опции для селектов от сервера
 */
export interface SelectOptions {
  countries: FilterOption[];
  sortOptions: FilterOption[];
  dateRanges: FilterOption[];
  advertisingNetworks: FilterOption[];
  languages: FilterOption[];
  operatingSystems: FilterOption[];
  browsers: FilterOption[];
  devices: FilterOption[];
  imageSizes: FilterOption[];
}

/**
 * Опции для вкладок от сервера
 */
export interface TabOptions {
  availableTabs: TabValue[];
  tabCounts: Record<string, string | number>;
  activeTab?: TabValue;
}

/**
 * URL параметры синхронизации
 */
export interface UrlSyncParams {
  cr_searchKeyword?: string;
  cr_country?: string;
  cr_dateCreation?: string;
  cr_sortBy?: string;
  cr_periodDisplay?: string;
  cr_onlyAdult?: string;
  cr_advertisingNetworks?: string;
  cr_languages?: string;
  cr_operatingSystems?: string;
  cr_browsers?: string;
  cr_devices?: string;
  cr_imageSizes?: string;
  cr_activeTab?: string;
}

/**
 * Type Guards
 */
export const isValidTabValue = (value: string): value is TabValue => {
  return ['push', 'inpage', 'facebook', 'tiktok'].includes(value);
};

export const isValidSortValue = (value: string): value is SortValue => {
  return ['creation', 'activity', 'popularity', 'byCreationDate', 'byActivity', 'byPopularity', 'default'].includes(value);
};

export const isValidDateRangeValue = (value: string): value is DateRangeValue => {
  // Проверяем предустановленные значения
  if (['today', 'yesterday', 'last7', 'last30', 'last90', 'thisMonth', 'lastMonth', 'thisYear', 'lastYear', 'default'].includes(value)) {
    return true;
  }
  
  // Проверяем только custom диапазоны в формате: custom_YYYY-MM-DD_to_YYYY-MM-DD
  // Одиночные даты больше не поддерживаются
  if (/^custom_\d{4}-\d{2}-\d{2}_to_\d{4}-\d{2}-\d{2}$/.test(value)) {
    return true;
  }
  
  return false;
};

/**
 * Константы
 */
export const CREATIVES_CONSTANTS = {
  DEFAULT_PAGE_SIZE: 12,
  MIN_PAGE_SIZE: 6,
  MAX_PAGE_SIZE: 100,
  MAX_SEARCH_LENGTH: 255,
  DEBOUNCE_DELAY: 300,
  URL_PREFIX: 'cr',
  CACHE_TTL: {
    DEFAULT: 5 * 60 * 1000, // 5 минут
    SEARCH: 30 * 1000,      // 30 секунд
  },
} as const;

/**
 * Дефолтные значения
 */
export const DEFAULT_FILTERS: FilterState = {
  isDetailedVisible: false,
  searchKeyword: '',
  country: 'default',
  dateCreation: 'default',
  sortBy: 'default',
  periodDisplay: 'default',
  advertisingNetworks: [],
  languages: [],
  operatingSystems: [],
  browsers: [],
  devices: [],
  imageSizes: [],
  onlyAdult: false,
  savedSettings: [],
};

export const DEFAULT_TABS: TabsState = {
  activeTab: 'push',
  availableTabs: ['push', 'inpage', 'facebook', 'tiktok'],
  tabCounts: {},
};

/**
 * Утилитарные типы
 */
export type FilterKey = keyof FilterState;
export type MultiSelectFilterKey = 'advertisingNetworks' | 'languages' | 'operatingSystems' | 'browsers' | 'devices' | 'imageSizes';
export type SimpleFilterKey = Exclude<FilterKey, MultiSelectFilterKey | 'isDetailedVisible' | 'savedSettings'>;

/**
 * Events типы
 */
export interface CreativesEvents {
  'creatives:tab-changed': {
    previousTab: TabValue;
    currentTab: TabValue;
    tabOption: TabOption;
  };
  'creatives:filters-changed': {
    filters: Partial<FilterState>;
    source: 'user' | 'url' | 'api';
  };
  'creatives:loading-state-changed': {
    isLoading: boolean;
    source: 'service' | 'store';
  };
}

/**
 * Hook результаты
 */
export interface UseCreativesReturn {
  // State
  creatives: Readonly<Ref<Creative[]>>;
  pagination: Readonly<Ref<Pagination>>;
  meta: Readonly<Ref<RequestMeta>>;
  isLoading: Readonly<Ref<boolean>>;
  error: Readonly<Ref<string | null>>;
  
  // Actions
  loadCreatives: (page?: number) => Promise<void>;
  refreshCreatives: () => Promise<void>;
  loadNextPage: () => Promise<void>;
  clearCreatives: () => void;
  mapFiltersToCreativesFilters: (filters: FilterState, activeTab: TabValue, page: number) => CreativesFilters;
  loadCreativesWithFilters: (filters: CreativesFilters) => Promise<void>;
}

export interface UseFiltersReturn {
  // State
  filters: FilterState;
  hasActiveFilters: Readonly<Ref<boolean>>;
  
  // Actions
  updateFilter: <K extends FilterKey>(key: K, value: FilterState[K]) => void;
  resetFilters: () => void;
  addToMultiSelect: (field: MultiSelectFilterKey, value: string) => void;
  removeFromMultiSelect: (field: MultiSelectFilterKey, value: string) => void;
}