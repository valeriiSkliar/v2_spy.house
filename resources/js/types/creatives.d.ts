export interface FilterOption {
  value: string;
  label: string;
}

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

// Типы для вкладок креативов
export interface TabOption {
  value: string;
  label: string;
  count?: string | number;
}

export interface TabsState {
  activeTab: string;
  availableTabs: string[];
  tabCounts: Record<string, string | number>;
}

// Комбинированное состояние всего Store
export interface CreativesStoreState extends FilterState, TabsState {
  // Можно добавить другие состояния (пагинация, избранное и т.д.)
}
