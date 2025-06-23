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
