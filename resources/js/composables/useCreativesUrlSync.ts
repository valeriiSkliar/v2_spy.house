import type { FilterState } from '@/types/creatives';
import { useUrlSync } from './useUrlSync';

/**
 * Интерфейс состояния URL для креативов
 * Соответствует FilterState но с опциональными полями для URL
 */
export interface CreativesUrlState {
    searchKeyword?: string;
    country?: string;
    dateCreation?: string;
    sortBy?: string;
    periodDisplay?: string;
    advertisingNetworks?: string[];
    languages?: string[];
    operatingSystems?: string[];
    browsers?: string[];
    devices?: string[];
    imageSizes?: string[];
    onlyAdult?: boolean;
    savedSettings?: string[];
    isDetailedVisible?: boolean;
}

/**
 * Преобразует FilterState в CreativesUrlState для URL синхронизации
 */
function filterStateToUrlState(filterState: FilterState): CreativesUrlState {
    return {
        searchKeyword: filterState.searchKeyword || undefined,
        country: filterState.country !== 'All Categories' ? filterState.country : undefined,
        dateCreation: filterState.dateCreation !== 'Date of creation' ? filterState.dateCreation : undefined,
        sortBy: filterState.sortBy !== 'By creation date' ? filterState.sortBy : undefined,
        periodDisplay: filterState.periodDisplay !== 'Period of display' ? filterState.periodDisplay : undefined,
        advertisingNetworks: filterState.advertisingNetworks?.length > 0 ? [...filterState.advertisingNetworks] : undefined,
        languages: filterState.languages?.length > 0 ? [...filterState.languages] : undefined,
        operatingSystems: filterState.operatingSystems?.length > 0 ? [...filterState.operatingSystems] : undefined,
        browsers: filterState.browsers?.length > 0 ? [...filterState.browsers] : undefined,
        devices: filterState.devices?.length > 0 ? [...filterState.devices] : undefined,
        imageSizes: filterState.imageSizes?.length > 0 ? [...filterState.imageSizes] : undefined,
        onlyAdult: filterState.onlyAdult || undefined,
        savedSettings: filterState.savedSettings?.length > 0 ? [...filterState.savedSettings] : undefined,
        isDetailedVisible: filterState.isDetailedVisible || undefined,
    };
}

/**
 * Преобразует CreativesUrlState в частичный FilterState
 */
function urlStateToFilterState(urlState: CreativesUrlState): Partial<FilterState> {
    return {
        searchKeyword: urlState.searchKeyword || '',
        country: urlState.country || 'All Categories',
        dateCreation: urlState.dateCreation || 'Date of creation',
        sortBy: urlState.sortBy || 'By creation date',
        periodDisplay: urlState.periodDisplay || 'Period of display',
        advertisingNetworks: Array.isArray(urlState.advertisingNetworks) ? [...urlState.advertisingNetworks] : [],
        languages: Array.isArray(urlState.languages) ? [...urlState.languages] : [],
        operatingSystems: Array.isArray(urlState.operatingSystems) ? [...urlState.operatingSystems] : [],
        browsers: Array.isArray(urlState.browsers) ? [...urlState.browsers] : [],
        devices: Array.isArray(urlState.devices) ? [...urlState.devices] : [],
        imageSizes: Array.isArray(urlState.imageSizes) ? [...urlState.imageSizes] : [],
        onlyAdult: urlState.onlyAdult || false,
        savedSettings: Array.isArray(urlState.savedSettings) ? [...urlState.savedSettings] : [],
        isDetailedVisible: urlState.isDetailedVisible || false,
    };
}

/**
 * Композабл для синхронизации фильтров креативов с URL
 * 
 * @param initialState - Начальное состояние фильтров
 */
export function useCreativesUrlSync(initialState?: Partial<CreativesUrlState>) {
    // Создаем начальное состояние с безопасными значениями по умолчанию
    const safeInitialState: CreativesUrlState = {
        searchKeyword: initialState?.searchKeyword || '',
        country: initialState?.country || '',
        dateCreation: initialState?.dateCreation || '',
        sortBy: initialState?.sortBy || '',
        periodDisplay: initialState?.periodDisplay || '',
        advertisingNetworks: Array.isArray(initialState?.advertisingNetworks) ? [...initialState.advertisingNetworks] : [],
        languages: Array.isArray(initialState?.languages) ? [...initialState.languages] : [],
        operatingSystems: Array.isArray(initialState?.operatingSystems) ? [...initialState.operatingSystems] : [],
        browsers: Array.isArray(initialState?.browsers) ? [...initialState.browsers] : [],
        devices: Array.isArray(initialState?.devices) ? [...initialState.devices] : [],
        imageSizes: Array.isArray(initialState?.imageSizes) ? [...initialState.imageSizes] : [],
        onlyAdult: initialState?.onlyAdult || false,
        savedSettings: Array.isArray(initialState?.savedSettings) ? [...initialState.savedSettings] : [],
        isDetailedVisible: initialState?.isDetailedVisible || false,
    };

    const urlSync = useUrlSync(safeInitialState, {
        prefix: 'cr',
        debounce: 300,
        transform: {
            serialize: (value: any) => {
                if (Array.isArray(value)) {
                    return value.length > 0 ? value.join(',') : '';
                }
                if (typeof value === 'boolean') {
                    return value ? '1' : '';
                }
                return value ? String(value) : '';
            },
            deserialize: (value: string) => {
                if (!value || value === 'undefined') {
                    return '';
                }
                
                // Пытаемся определить тип по значению
                if (value.includes(',')) {
                    // Это массив
                    return value.split(',').filter(v => v.trim() !== '');
                }
                
                if (value === '1' || value === 'true') {
                    return true;
                }
                
                if (value === '0' || value === 'false') {
                    return false;
                }
                
                return value;
            }
        }
    });

    // Методы для обновления конкретных фильтров
    const updateSearch = (search: string) => {
        urlSync.updateState({ searchKeyword: search });
    };

    const updateCountry = (country: string) => {
        urlSync.updateState({ country: country !== 'All Categories' ? country : '' });
    };

    const updateSort = (sort: string) => {
        urlSync.updateState({ sortBy: sort !== 'By creation date' ? sort : '' });
    };

    const updateDateCreation = (date: string) => {
        urlSync.updateState({ dateCreation: date !== 'Date of creation' ? date : '' });
    };

    const updatePeriodDisplay = (period: string) => {
        urlSync.updateState({ periodDisplay: period !== 'Period of display' ? period : '' });
    };

    const updateOnlyAdult = (onlyAdult: boolean) => {
        urlSync.updateState({ onlyAdult });
    };

    const updateDetailedVisible = (isVisible: boolean) => {
        urlSync.updateState({ isDetailedVisible: isVisible });
    };

    const updateMultiSelectField = (field: keyof CreativesUrlState, values: string[]) => {
        urlSync.updateState({ [field]: [...values] });
    };

    // Синхронизация с FilterState
    const syncWithFilterState = (filterState: FilterState) => {
        const urlState = filterStateToUrlState(filterState);
        urlSync.updateState(urlState);
    };

    const getFilterStateUpdates = (): Partial<FilterState> => {
        return urlStateToFilterState(urlSync.state.value);
    };

    return {
        state: urlSync.state,
        urlParams: urlSync.urlParams,
        updateState: urlSync.updateState,
        resetState: urlSync.resetState,
        
        // Специализированные методы
        updateSearch,
        updateCountry,
        updateSort,
        updateDateCreation,
        updatePeriodDisplay,
        updateOnlyAdult,
        updateDetailedVisible,
        updateMultiSelectField,
        
        // Интеграция с FilterState
        syncWithFilterState,
        getFilterStateUpdates,
    };
}