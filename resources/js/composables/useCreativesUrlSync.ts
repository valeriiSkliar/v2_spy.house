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
        country: filterState.country !== 'default' ? filterState.country : undefined,
        dateCreation: filterState.dateCreation !== 'default' ? filterState.dateCreation : undefined,
        sortBy: filterState.sortBy !== 'default' ? filterState.sortBy : undefined,
        periodDisplay: filterState.periodDisplay !== 'default' ? filterState.periodDisplay : undefined,
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
        searchKeyword: urlState.searchKeyword !== undefined ? urlState.searchKeyword : '',
        country: urlState.country !== undefined ? urlState.country : 'default',
        dateCreation: urlState.dateCreation !== undefined ? urlState.dateCreation : 'default',
        sortBy: urlState.sortBy !== undefined ? urlState.sortBy : 'default',
        periodDisplay: urlState.periodDisplay !== undefined ? urlState.periodDisplay : 'default',
        advertisingNetworks: Array.isArray(urlState.advertisingNetworks) ? [...urlState.advertisingNetworks] : [],
        languages: Array.isArray(urlState.languages) ? [...urlState.languages] : [],
        operatingSystems: Array.isArray(urlState.operatingSystems) ? [...urlState.operatingSystems] : [],
        browsers: Array.isArray(urlState.browsers) ? [...urlState.browsers] : [],
        devices: Array.isArray(urlState.devices) ? [...urlState.devices] : [],
        imageSizes: Array.isArray(urlState.imageSizes) ? [...urlState.imageSizes] : [],
        onlyAdult: urlState.onlyAdult !== undefined ? urlState.onlyAdult : false,
        savedSettings: Array.isArray(urlState.savedSettings) ? [...urlState.savedSettings] : [],
        isDetailedVisible: urlState.isDetailedVisible !== undefined ? urlState.isDetailedVisible : false,
    };
}

/**
 * Композабл для синхронизации фильтров креативов с URL
 * 
 * @param initialState - Начальное состояние фильтров (опционально)
 */
export function useCreativesUrlSync(initialState?: Partial<CreativesUrlState>) {
    // Создаем начальное состояние с дефолтными значениями (URL как source of truth)
    const safeInitialState: CreativesUrlState = {
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
        isDetailedVisible: false,
        // Применяем переданные значения если есть
        ...initialState
    };

    const urlSync = useUrlSync(safeInitialState, {
        prefix: 'cr',
        debounce: 200,
        transform: {
            serialize: (value: any) => {
                // Обрабатываем Vue реактивные массивы (Proxy)
                if (Array.isArray(value) || (value && typeof value === 'object' && 
                    (value.constructor === Array || value[Symbol.toStringTag] === 'Array' || 
                     (typeof value[Symbol.iterator] === 'function' && value.length !== undefined)))) {
                    
                    // Безопасное преобразование в обычный массив
                    let arrayValue;
                    try {
                        arrayValue = [...value]; // Spread оператор работает с итерируемыми объектами
                    } catch (e) {
                        arrayValue = Array.from(value); // Fallback
                    }
                    
                    // Фильтруем пустые значения и приводим к строкам
                    const cleanArray = arrayValue
                        .filter(item => item !== null && item !== undefined && item !== '')
                        .map(item => String(item));
                    
                    return cleanArray.length > 0 ? cleanArray.join(',') : '';
                }
                if (typeof value === 'boolean') {
                    return value ? '1' : '';
                }
                // Не сериализуем пустые значения и дефолтные значения
                if (value === '' || value === 'default') {
                    return '';
                }
                return value ? String(value) : '';
            }
        }
    });

    // Методы для обновления конкретных фильтров
    const updateSearch = (search: string) => {
        urlSync.updateState({ searchKeyword: search });
    };

    const updateCountry = (country: string) => {
        urlSync.updateState({ country: country !== 'default' ? country : '' });
    };

    const updateSort = (sort: string) => {
        urlSync.updateState({ sortBy: sort !== 'default' ? sort : '' });
    };

    const updateDateCreation = (date: string) => {
        urlSync.updateState({ dateCreation: date !== 'default' ? date : '' });
    };

    const updatePeriodDisplay = (period: string) => {
        urlSync.updateState({ periodDisplay: period !== 'default' ? period : '' });
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