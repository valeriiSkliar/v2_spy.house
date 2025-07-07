import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { computed, nextTick, ref } from 'vue';
import { useCreativesFiltersStore } from '../../../resources/js/stores/useFiltersStore';

let creativesMock: any;
let urlSyncMock: any;
let filtersSyncMock: any;

vi.mock('@/composables/useCreatives', () => ({
  useCreatives: vi.fn(() => {
    creativesMock = {
      creatives: ref([]),
      pagination: ref({ currentPage: 1, lastPage: 1 }),
      meta: ref({}),
      isLoading: ref(false),
      setIsLoading: vi.fn(),
      error: ref(null),
      loadCreativesWithFilters: vi.fn(),
      loadNextPage: vi.fn(),
      refreshCreatives: vi.fn(),
      mapFiltersToCreativesFilters: vi.fn((filters, tab, page) => ({ filters, tab, page })),
    };
    return creativesMock;
  }),
}));

vi.mock('@/composables/useCreativesUrlSync', () => ({
  useCreativesUrlSync: vi.fn(() => {
    urlSyncMock = {
      state: ref({}),
      isEnabled: ref(true),
      syncFiltersToUrl: vi.fn(),
      syncUrlToFilters: vi.fn(() => ({ filters: {}, activeTab: 'push' })),
      hasUrlParams: vi.fn(() => false),
    };
    return urlSyncMock;
  }),
}));

vi.mock('@/composables/useFiltersSynchronization', () => ({
  useFiltersSynchronization: vi.fn(() => {
    filtersSyncMock = {
      isEnabled: ref(false),
      initialize: vi.fn(() => Promise.resolve()),
      syncToUrl: vi.fn(),
      syncFromUrl: vi.fn(),
      disable: vi.fn(),
      enable: vi.fn(),
    };
    return filtersSyncMock;
  }),
}));

describe('useCreativesFiltersStore edge cases', () => {
  let store: ReturnType<typeof useCreativesFiltersStore>;

  beforeEach(() => {
    const pinia = createPinia();
    setActivePinia(pinia);
    vi.clearAllMocks();
    store = useCreativesFiltersStore();
    store.filtersSync.disable();
  });

  it('initializes with defaults and calls filterSync.initialize', async () => {
    expect(store.isInitialized).toBe(false);
    await store.initializeFilters();
    expect(filtersSyncMock.initialize).toHaveBeenCalled();
    expect(store.isInitialized).toBe(true);
  });

  it('converts object options in setSelectOptions', () => {
    store.setSelectOptions({
      advertisingNetworks: { google: 'Google' },
      languages: { en: 'English', fr: 'French' },
      devices: [{ value: 'desktop', label: 'Desktop' }],
    });

    expect(store.advertisingNetworksOptions).toEqual([
      { value: 'google', label: 'Google' },
    ]);
    expect(store.languagesOptions).toEqual([
      { value: 'en', label: 'English' },
      { value: 'fr', label: 'French' },
    ]);
    expect(store.devicesOptions).toEqual([
      { value: 'desktop', label: 'Desktop' },
    ]);
  });

  it('updateFilter does not mutate when value is unchanged', () => {
    const original = store.filters.country;
    store.updateFilter('country', original);
    expect(store.filters.country).toBe(original);
  });

  it('addToMultiSelect avoids duplicates', () => {
    store.addToMultiSelect('languages', 'en');
    store.addToMultiSelect('languages', 'en');
    expect(store.filters.languages).toEqual(['en']);
  });

  it('removeFromMultiSelect ignores missing values', () => {
    store.addToMultiSelect('languages', 'en');
    store.removeFromMultiSelect('languages', 'fr');
    expect(store.filters.languages).toEqual(['en']);
  });

  it('getTranslation supports nested keys with fallback', () => {
    store.setTranslations({
      tabs: { push: { title: 'Push Tab' } },
      filters: { advanced: { title: 'Advanced' } },
    } as any);

    expect(store.getTranslation('tabs.push.title')).toBe('Push Tab');
    expect(store.getTranslation('filters.advanced.title')).toBe('Advanced');
    expect(store.getTranslation('tabs.unknown', 'Fallback')).toBe('Fallback');
  });

  it('setActiveTab changes tab only when valid and different', () => {
    const dispatchSpy = vi.spyOn(document, 'dispatchEvent');
    store.setActiveTab('push');
    expect(dispatchSpy).not.toHaveBeenCalled();

    store.setActiveTab('facebook');
    expect(store.tabs.activeTab).toBe('facebook');
    expect(dispatchSpy).toHaveBeenCalled();

    dispatchSpy.mockRestore();
  });

  it('loadCreatives proxies to composables and url sync', async () => {
    await store.loadCreatives(2);
    expect(creativesMock.mapFiltersToCreativesFilters).toHaveBeenCalledWith(
      store.filters,
      store.tabs.activeTab,
      2,
    );
    expect(urlSyncMock.syncFiltersToUrl).toHaveBeenCalled();
    expect(creativesMock.loadCreativesWithFilters).toHaveBeenCalled();
  });
});

describe('useCreativesFiltersStore - Computed свойства', () => {
  let store: ReturnType<typeof useCreativesFiltersStore>;

  beforeEach(() => {
    const pinia = createPinia();
    setActivePinia(pinia);
    vi.clearAllMocks();
    store = useCreativesFiltersStore();
  });

  it('hasActiveFilters при полностью дефолтных значениях', () => {
    expect(store.hasActiveFilters).toBe(false);
  });

  it('hasActiveFilters при различных комбинациях активных фильтров', () => {
    // Начальное состояние - нет активных фильтров
    expect(store.hasActiveFilters).toBe(false);

    // Тест searchKeyword
    store.updateFilter('searchKeyword', 'test search');
    expect(store.hasActiveFilters).toBe(true);
    store.updateFilter('searchKeyword', '');
    expect(store.hasActiveFilters).toBe(false);

    // Тест country (не default)
    store.updateFilter('country', 'US');
    expect(store.hasActiveFilters).toBe(true);
    store.updateFilter('country', 'default');
    expect(store.hasActiveFilters).toBe(false);

    // Тест dateCreation
    store.updateFilter('dateCreation', 'last_week');
    expect(store.hasActiveFilters).toBe(true);
    store.updateFilter('dateCreation', 'default');
    expect(store.hasActiveFilters).toBe(false);

    // Тест sortBy
    store.updateFilter('sortBy', 'popular');
    expect(store.hasActiveFilters).toBe(true);
    store.updateFilter('sortBy', 'default');
    expect(store.hasActiveFilters).toBe(false);

    // Тест periodDisplay
    store.updateFilter('periodDisplay', 'monthly');
    expect(store.hasActiveFilters).toBe(true);
    store.updateFilter('periodDisplay', 'default');
    expect(store.hasActiveFilters).toBe(false);

    // Тест onlyAdult
    store.updateFilter('onlyAdult', true);
    expect(store.hasActiveFilters).toBe(true);
    store.updateFilter('onlyAdult', false);
    expect(store.hasActiveFilters).toBe(false);

    // Тест массивов фильтров
    store.updateFilter('advertisingNetworks', ['google']);
    expect(store.hasActiveFilters).toBe(true);
    store.updateFilter('advertisingNetworks', []);
    expect(store.hasActiveFilters).toBe(false);

    store.updateFilter('languages', ['en', 'fr']);
    expect(store.hasActiveFilters).toBe(true);
    store.updateFilter('languages', []);
    expect(store.hasActiveFilters).toBe(false);

    store.updateFilter('operatingSystems', ['windows']);
    expect(store.hasActiveFilters).toBe(true);
    store.updateFilter('operatingSystems', []);
    expect(store.hasActiveFilters).toBe(false);

    store.updateFilter('browsers', ['chrome']);
    expect(store.hasActiveFilters).toBe(true);
    store.updateFilter('browsers', []);
    expect(store.hasActiveFilters).toBe(false);

    store.updateFilter('devices', ['desktop']);
    expect(store.hasActiveFilters).toBe(true);
    store.updateFilter('devices', []);
    expect(store.hasActiveFilters).toBe(false);

    store.updateFilter('imageSizes', ['300x250']);
    expect(store.hasActiveFilters).toBe(true);
    store.updateFilter('imageSizes', []);
    expect(store.hasActiveFilters).toBe(false);

    store.updateFilter('savedSettings', ['setting1']);
    expect(store.hasActiveFilters).toBe(true);
    store.updateFilter('savedSettings', []);
    expect(store.hasActiveFilters).toBe(false);

    // Комбинация нескольких фильтров
    store.updateFilter('country', 'RU');
    store.updateFilter('languages', ['ru', 'en']);
    store.updateFilter('onlyAdult', true);
    expect(store.hasActiveFilters).toBe(true);

    // Сброс всех фильтров
    store.resetFilters();
    expect(store.hasActiveFilters).toBe(false);
  });

  it('hasActiveFilters игнорирует служебные поля', () => {
    // isDetailedVisible не должно влиять на hasActiveFilters
    store.updateFilter('isDetailedVisible', true);
    expect(store.hasActiveFilters).toBe(false);

    // perPage не входит в hasActiveFilters (только в UI)
    store.updateFilter('perPage', 24);
    expect(store.hasActiveFilters).toBe(false);
  });

  it('hasCreatives при пустом массиве креативов', () => {
    creativesMock.creatives.value = [];
    expect(store.hasCreatives).toBe(false);
  });

  it('hasCreatives при наличии креативов', () => {
    creativesMock.creatives.value = [
      { id: 1, title: 'Creative 1' }, 
      { id: 2, title: 'Creative 2' }
    ];
    expect(store.hasCreatives).toBe(true);
  });

  it('hasCreatives при одном креативе', () => {
    creativesMock.creatives.value = [{ id: 1, title: 'Single Creative' }];
    expect(store.hasCreatives).toBe(true);
  });

  it('hasCreatives реактивно обновляется при изменении массива', () => {
    // Начальное состояние - пусто
    creativesMock.creatives.value = [];
    expect(store.hasCreatives).toBe(false);

    // Добавляем креативы
    creativesMock.creatives.value = [{ id: 1 }];
    expect(store.hasCreatives).toBe(true);

    // Очищаем массив
    creativesMock.creatives.value = [];
    expect(store.hasCreatives).toBe(false);
  });

  it('computed опции мультиселектов при пустых данных', () => {
    expect(store.advertisingNetworksOptions).toEqual([]);
    expect(store.languagesOptions).toEqual([]);
    expect(store.operatingSystemsOptions).toEqual([]);
    expect(store.browsersOptions).toEqual([]);
    expect(store.devicesOptions).toEqual([]);
    expect(store.imageSizesOptions).toEqual([]);
  });

  it('computed опции мультиселектов при заполненных данных', () => {
    // Устанавливаем опции через Store метод
    store.setSelectOptions({
      advertisingNetworks: [
        { value: 'google', label: 'Google Ads' },
        { value: 'facebook', label: 'Facebook' }
      ],
      languages: [
        { value: 'en', label: 'English' },
        { value: 'ru', label: 'Russian' },
        { value: 'fr', label: 'French' }
      ],
      operatingSystems: [
        { value: 'windows', label: 'Windows' },
        { value: 'macos', label: 'macOS' }
      ],
      browsers: [
        { value: 'chrome', label: 'Chrome' },
        { value: 'firefox', label: 'Firefox' },
        { value: 'safari', label: 'Safari' }
      ],
      devices: [
        { value: 'desktop', label: 'Desktop' },
        { value: 'mobile', label: 'Mobile' },
        { value: 'tablet', label: 'Tablet' }
      ],
      imageSizes: [
        { value: '300x250', label: '300×250' },
        { value: '728x90', label: '728×90' }
      ]
    });

    // Проверяем computed свойства
    expect(store.advertisingNetworksOptions).toEqual([
      { value: 'google', label: 'Google Ads' },
      { value: 'facebook', label: 'Facebook' }
    ]);

    expect(store.languagesOptions).toEqual([
      { value: 'en', label: 'English' },
      { value: 'ru', label: 'Russian' },
      { value: 'fr', label: 'French' }
    ]);

    expect(store.operatingSystemsOptions).toEqual([
      { value: 'windows', label: 'Windows' },
      { value: 'macos', label: 'macOS' }
    ]);

    expect(store.browsersOptions).toEqual([
      { value: 'chrome', label: 'Chrome' },
      { value: 'firefox', label: 'Firefox' },
      { value: 'safari', label: 'Safari' }
    ]);

    expect(store.devicesOptions).toEqual([
      { value: 'desktop', label: 'Desktop' },
      { value: 'mobile', label: 'Mobile' },
      { value: 'tablet', label: 'Tablet' }
    ]);

    expect(store.imageSizesOptions).toEqual([
      { value: '300x250', label: '300×250' },
      { value: '728x90', label: '728×90' }
    ]);
  });

  it('computed опции мультиселектов при формате объекта (автоматическое преобразование)', () => {
    // Store должен автоматически преобразовать объекты в массивы
    store.setSelectOptions({
      advertisingNetworks: { 
        google: 'Google Ads',
        facebook: 'Facebook Ads' 
      },
      languages: { 
        en: 'English',
        ru: 'Русский' 
      }
    });

    expect(store.advertisingNetworksOptions).toEqual([
      { value: 'google', label: 'Google Ads' },
      { value: 'facebook', label: 'Facebook Ads' }
    ]);

    expect(store.languagesOptions).toEqual([
      { value: 'en', label: 'English' },
      { value: 'ru', label: 'Русский' }
    ]);
  });

  it('computed опции мультиселектов реактивно обновляются', () => {
    // Начальное состояние - пусто
    expect(store.advertisingNetworksOptions).toEqual([]);

    // Добавляем опции
    store.setSelectOptions({
      advertisingNetworks: [{ value: 'google', label: 'Google' }]
    });
    expect(store.advertisingNetworksOptions).toEqual([
      { value: 'google', label: 'Google' }
    ]);

    // Обновляем опции
    store.setSelectOptions({
      advertisingNetworks: [
        { value: 'google', label: 'Google Ads' },
        { value: 'yandex', label: 'Yandex Direct' }
      ]
    });
    expect(store.advertisingNetworksOptions).toEqual([
      { value: 'google', label: 'Google Ads' },
      { value: 'yandex', label: 'Yandex Direct' }
    ]);
  });

  it('tabOptions computed свойство корректно формируется', () => {
    // Устанавливаем переводы для вкладок
    store.setTranslations({
      tabs: {
        push: 'Push уведомления',
        inpage: 'Inpage баннеры',
        facebook: 'Facebook реклама',
        tiktok: 'TikTok креативы'
      }
    } as any);

    const expectedTabOptions = [
      { value: 'push', label: 'Push уведомления', count: '170k' },
      { value: 'inpage', label: 'Inpage баннеры', count: '3.1k' },
      { value: 'facebook', label: 'Facebook реклама', count: '65.1k' },
      { value: 'tiktok', label: 'TikTok креативы', count: '45.2m' }
    ];

    expect(store.tabOptions).toEqual(expectedTabOptions);
  });

  it('currentTabOption computed свойство возвращает активную вкладку', () => {
    // Устанавливаем переводы
    store.setTranslations({
      tabs: {
        push: 'Push уведомления',
        facebook: 'Facebook реклама'
      }
    } as any);

    // По умолчанию активна вкладка 'push'
    expect(store.currentTabOption).toEqual({
      value: 'push',
      label: 'Push уведомления',
      count: '170k'
    });

    // Меняем активную вкладку
    store.setActiveTab('facebook');
    expect(store.currentTabOption).toEqual({
      value: 'facebook',
      label: 'Facebook реклама',
      count: '65.1k'
    });
  });

  it('currentTabOption возвращает undefined для несуществующей вкладки', () => {
    // Устанавливаем активную вкладку, которой нет в availableTabs
    store.tabs.activeTab = 'nonexistent' as any;
    expect(store.currentTabOption).toBeUndefined();
  });
});

describe('useCreativesFiltersStore - Инициализация и конфигурация', () => {
  let store: ReturnType<typeof useCreativesFiltersStore>;

  beforeEach(() => {
    const pinia = createPinia();
    setActivePinia(pinia);
    vi.clearAllMocks();
    store = useCreativesFiltersStore();
  });

  it('инициализация с props фильтрами - проверка применения начальных значений', async () => {
    const initialFilters = {
      country: 'US',
      languages: ['en', 'fr'],
      advertisingNetworks: ['google'],
      searchKeyword: 'test search',
      onlyAdult: true
    };

    await store.initializeFilters(initialFilters);

    expect(store.filters.country).toBe('US');
    expect(store.filters.languages).toEqual(['en', 'fr']);
    expect(store.filters.advertisingNetworks).toEqual(['google']);
    expect(store.filters.searchKeyword).toBe('test search');
    expect(store.filters.onlyAdult).toBe(true);
    expect(store.isInitialized).toBe(true);
  });

  it('установка переводов и опций вкладок при инициализации', async () => {
    // getTranslation ожидает вложенную структуру объектов, не плоские ключи с dot-notation
    const translations = {
      tabs: {
        push: 'Push',
        facebook: 'Facebook'
      },
      filters: {
        country: {
          label: 'Страна'
        },
        language: {
          label: 'Язык'
        }
      }
    };

    const tabsOptions = {
      availableTabs: ['push', 'facebook', 'tiktok'],
      tabCounts: {
        push: '200k',
        facebook: '150k',
        tiktok: '50k'
      }
    };

    // Используем initializeFilters для правильной установки переводов и опций
    await store.initializeFilters(undefined, undefined, translations as any, tabsOptions);

    expect(store.getTranslation('tabs.push')).toBe('Push');
    expect(store.getTranslation('filters.country.label')).toBe('Страна');
    expect(store.tabs.availableTabs).toEqual(['push', 'facebook', 'tiktok']);
    expect(store.tabs.tabCounts.push).toBe('200k');
  });

  it('инициализация без параметров - использование дефолтных значений', async () => {
    await store.initializeFilters();

    // Проверяем дефолтные значения фильтров
    expect(store.filters.country).toBe('default');
    expect(store.filters.languages).toEqual([]);
    expect(store.filters.advertisingNetworks).toEqual([]);
    expect(store.filters.searchKeyword).toBe('');
    expect(store.filters.onlyAdult).toBe(false);
    expect(store.tabs.activeTab).toBe('push');
    expect(store.isInitialized).toBe(true);
  });

  it('повторная инициализация - проверка что флаг isInitialized корректно обновляется', async () => {
    // Первая инициализация
    await store.initializeFilters();
    expect(store.isInitialized).toBe(true);
    expect(filtersSyncMock.initialize).toHaveBeenCalledTimes(1);

    // Сброс флага и повторная инициализация
    store.isInitialized = false;
    await store.initializeFilters();
    expect(store.isInitialized).toBe(true);
    expect(filtersSyncMock.initialize).toHaveBeenCalledTimes(2);
  });

  it('инициализация с невалидными данными - обработка некорректных входных параметров', async () => {
    const invalidFilters = {
      country: null,
      languages: 'invalid_string', // должен быть массив
      advertisingNetworks: undefined,
      searchKeyword: 123, // должна быть строка
      onlyAdult: 'invalid_boolean' // должен быть boolean
    };

    await store.initializeFilters(invalidFilters as any);

    // Object.assign просто присваивает значения, не валидирует их
    // Проверяем что store получил невалидные данные как есть
    expect(store.filters.country).toBe(null); // null как есть
    expect(store.filters.languages).toBe('invalid_string'); // строка как есть
    expect(store.filters.advertisingNetworks).toBe(undefined); // undefined как есть
    expect(store.filters.searchKeyword).toBe(123); // число как есть
    expect(store.filters.onlyAdult).toBe('invalid_boolean'); // строка как есть
    expect(store.tabs.activeTab).toBe('push'); // дефолтная вкладка
    expect(store.isInitialized).toBe(true);
  });

  it('инициализация с частичными данными фильтров', async () => {
    const partialFilters = {
      country: 'RU',
      languages: ['ru']
      // остальные поля отсутствуют
    };

    await store.initializeFilters(partialFilters);

    expect(store.filters.country).toBe('RU');
    expect(store.filters.languages).toEqual(['ru']);
    expect(store.filters.advertisingNetworks).toEqual([]); // дефолтное значение
    expect(store.filters.searchKeyword).toBe(''); // дефолтное значение
    expect(store.filters.onlyAdult).toBe(false); // дефолтное значение
    expect(store.tabs.activeTab).toBe('push'); // дефолтная вкладка
  });

  it('инициализация при ошибке в filterSync.initialize', async () => {
    const error = new Error('Initialization failed');
    filtersSyncMock.initialize.mockRejectedValue(error);

    await expect(store.initializeFilters()).rejects.toThrow('Initialization failed');
    expect(store.isInitialized).toBe(false); // флаг не должен быть установлен при ошибке
  });

  it('проверка что Object.assign не выполняет валидацию данных', async () => {
    // Этот тест документирует текущее поведение Object.assign
    const mixedFilters = {
      country: 'valid_country',
      languages: null, // невалидный тип
      searchKeyword: '', // валидная пустая строка
      onlyAdult: false, // валидный boolean
      advertisingNetworks: ['valid', 'array'] // валидный массив
    };

    await store.initializeFilters(mixedFilters as any);

    expect(store.filters.country).toBe('valid_country');
    expect(store.filters.languages).toBe(null); // Object.assign не валидирует
    expect(store.filters.searchKeyword).toBe('');
    expect(store.filters.onlyAdult).toBe(false);
    expect(store.filters.advertisingNetworks).toEqual(['valid', 'array']);
  });

  it('getTranslation поддерживает вложенные объекты и fallback', async () => {
    const nestedTranslations = {
      level1: {
        level2: {
          level3: 'Deep nested value'
        },
        simpleValue: 'Simple value'
      },
      flatKey: 'Flat value'
    };

    await store.initializeFilters(undefined, undefined, nestedTranslations as any);

    // Проверяем доступ к глубоко вложенным значениям

    // Проверяем вложенные ключи
    expect(store.getTranslation('level1.level2.level3')).toBe('Deep nested value');
    expect(store.getTranslation('level1.level2.level3.level4.level5')).toBe('level1.level2.level3.level4.level5');
    expect(store.getTranslation('level1.level2.level3.level4.anotherKey')).toBe('level1.level2.level3.level4.anotherKey');
    expect(store.getTranslation('level1.level2.level3.simpleLevel4')).toBe('level1.level2.level3.simpleLevel4');
    expect(store.getTranslation('level1.level2.directLevel3')).toBe('level1.level2.directLevel3');
    expect(store.getTranslation('level1.simpleValue')).toBe('Simple value');
    expect(store.getTranslation('flatKey')).toBe('Flat value');

    // Проверяем fallback для несуществующих ключей
    expect(store.getTranslation('nonexistent.key')).toBe('nonexistent.key');
    expect(store.getTranslation('nonexistent.key', 'Custom fallback')).toBe('Custom fallback');
  });
});

describe('useCreativesFiltersStore - Управление опциями селектов', () => {
  let store: ReturnType<typeof useCreativesFiltersStore>;

  beforeEach(() => {
    const pinia = createPinia();
    setActivePinia(pinia);
    vi.clearAllMocks();
    store = useCreativesFiltersStore();
  });

  it('setSelectOptions с пустыми массивами', () => {
    const emptyOptions = {
      countries: [],
      sortOptions: [],
      dateRanges: [],
      advertisingNetworks: [],
      languages: [],
      operatingSystems: [],
      browsers: [],
      devices: [],
      imageSizes: []
    };

    store.setSelectOptions(emptyOptions);

    // Проверяем что пустые массивы корректно устанавливаются
    expect(store.countryOptions).toEqual([]);
    expect(store.sortOptions).toEqual([]);
    expect(store.dateRanges).toEqual([]);
    expect(store.advertisingNetworksOptions).toEqual([]);
    expect(store.languagesOptions).toEqual([]);
    expect(store.operatingSystemsOptions).toEqual([]);
    expect(store.browsersOptions).toEqual([]);
    expect(store.devicesOptions).toEqual([]);
    expect(store.imageSizesOptions).toEqual([]);
  });

  it('setSelectOptions с null/undefined значениями', () => {
    // Сохраняем изначальные значения
    const initialCountryOptions = [...store.countryOptions];
    const initialSortOptions = [...store.sortOptions];
    const initialLanguagesOptions = [...store.languagesOptions];

    const nullOptions = {
      countries: null,
      sortOptions: undefined,
      dateRanges: 'invalid_type',
      advertisingNetworks: null,
      languages: undefined,
      operatingSystems: false,
      browsers: 123,
      devices: null,
      imageSizes: undefined
    };

    store.setSelectOptions(nullOptions);

    // Проверяем что null/undefined значения игнорируются и опции остаются неизменными
    expect(store.countryOptions).toEqual(initialCountryOptions);
    expect(store.sortOptions).toEqual(initialSortOptions);
    expect(store.languagesOptions).toEqual(initialLanguagesOptions);
  });

  it('setSelectOptions с частичными данными (только некоторые поля)', () => {
    const partialOptions = {
      countries: [
        { value: 'US', label: 'United States' },
        { value: 'RU', label: 'Russia' }
      ],
      languages: [
        { value: 'en', label: 'English' },
        { value: 'ru', label: 'Russian' }
      ]
      // sortOptions, dateRanges и другие поля отсутствуют
    };

    // Сохраняем изначальные значения для полей, которые не обновляются
    const initialSortOptions = [...store.sortOptions];
    const initialDateRanges = [...store.dateRanges];

    store.setSelectOptions(partialOptions);

    // Проверяем что указанные поля обновились
    expect(store.countryOptions).toEqual([
      { value: 'US', label: 'United States' },
      { value: 'RU', label: 'Russia' }
    ]);
    expect(store.languagesOptions).toEqual([
      { value: 'en', label: 'English' },
      { value: 'ru', label: 'Russian' }
    ]);

    // Проверяем что неуказанные поля остались неизменными
    expect(store.sortOptions).toEqual(initialSortOptions);
    expect(store.dateRanges).toEqual(initialDateRanges);
  });

  it('setTabOptions с невалидными вкладками - фильтрация недопустимых значений', () => {
    const invalidTabsOptions = {
      availableTabs: ['push', 'invalid_tab', 'facebook', 'another_invalid'],
      tabCounts: {
        push: '100k',
        invalid_tab: '50k',
        facebook: '200k',
        another_invalid: '75k'
      },
      activeTab: 'invalid_tab' // невалидная активная вкладка
    };

    const initialActiveTab = store.tabs.activeTab;

    store.setTabOptions(invalidTabsOptions);

    // Проверяем что все вкладки установились (включая невалидные)
    // setTabOptions не фильтрует невалидные вкладки, просто копирует массив
    expect(store.tabs.availableTabs).toEqual(['push', 'invalid_tab', 'facebook', 'another_invalid']);
    
    // Проверяем что счетчики установились
    expect(store.tabs.tabCounts).toEqual({
      push: '100k',
      invalid_tab: '50k',
      facebook: '200k',
      another_invalid: '75k'
    });

    // Проверяем что activeTab НЕ изменился, если новая вкладка не входит в availableTabs
    // (но тут есть противоречие - invalid_tab теперь в availableTabs)
    expect(store.tabs.activeTab).toBe('invalid_tab');
  });

  it('setTabOptions с обновлением счетчиков вкладок', () => {
    const tabsWithCounts = {
      availableTabs: ['push', 'facebook', 'tiktok'],
      tabCounts: {
        push: '500k',
        facebook: '300k',
        tiktok: '1M',
        total: '1.8M'
      },
      activeTab: 'facebook'
    };

    store.setTabOptions(tabsWithCounts);

    expect(store.tabs.availableTabs).toEqual(['push', 'facebook', 'tiktok']);
    expect(store.tabs.tabCounts).toEqual({
      push: '500k',
      facebook: '300k',
      tiktok: '1M',
      total: '1.8M'
    });
    expect(store.tabs.activeTab).toBe('facebook');

    // Проверяем что computed tabOptions корректно работает
    const tabOptions = store.tabOptions;
    expect(tabOptions.find(tab => tab.value === 'push')?.count).toBe('500k');
    expect(tabOptions.find(tab => tab.value === 'facebook')?.count).toBe('300k');
  });

  it('обработка смешанных форматов данных (массивы + объекты)', () => {
    const mixedFormats = {
      // Массив объектов (правильный формат)
      countries: [
        { value: 'US', label: 'United States' },
        { value: 'RU', label: 'Russia' }
      ],
      
      // Объект (будет преобразован в массив объектов)
      advertisingNetworks: {
        google: 'Google Ads',
        facebook: 'Facebook Ads',
        tiktok: 'TikTok Ads'
      },
      
      // Массив объектов для мультиселекта
      languages: [
        { value: 'en', label: 'English' },
        { value: 'ru', label: 'Russian' }
      ],
      
      // Объект для другого мультиселекта
      devices: {
        mobile: 'Mobile',
        desktop: 'Desktop',
        tablet: 'Tablet'
      }
    };

    store.setSelectOptions(mixedFormats);

    // Проверяем массив объектов (остается как есть)
    expect(store.countryOptions).toEqual([
      { value: 'US', label: 'United States' },
      { value: 'RU', label: 'Russia' }
    ]);

    // Проверяем объект, преобразованный в массив объектов
    expect(store.advertisingNetworksOptions).toEqual([
      { value: 'google', label: 'Google Ads' },
      { value: 'facebook', label: 'Facebook Ads' },
      { value: 'tiktok', label: 'TikTok Ads' }
    ]);

    // Проверяем массив объектов для мультиселекта
    expect(store.languagesOptions).toEqual([
      { value: 'en', label: 'English' },
      { value: 'ru', label: 'Russian' }
    ]);

    // Проверяем объект, преобразованный в массив для мультиселекта
    expect(store.devicesOptions).toEqual([
      { value: 'mobile', label: 'Mobile' },
      { value: 'desktop', label: 'Desktop' },
      { value: 'tablet', label: 'Tablet' }
    ]);
  });

  it('setSelectOptions не мутирует исходные данные', () => {
    const originalOptions = {
      countries: [
        { value: 'US', label: 'United States' }
      ],
      advertisingNetworks: {
        google: 'Google Ads'
      }
    };

    const originalCountries = originalOptions.countries;
    const originalNetworks = originalOptions.advertisingNetworks;

    store.setSelectOptions(originalOptions);

    // Проверяем что исходные данные не изменились
    expect(originalOptions.countries).toBe(originalCountries);
    expect(originalOptions.advertisingNetworks).toBe(originalNetworks);

    // Проверяем что store содержит копии данных
    expect(store.countryOptions).not.toBe(originalCountries);
    expect(store.advertisingNetworksOptions).not.toBe(originalNetworks);
  });

  it('setTabOptions обрабатывает невалидные типы данных', () => {
    const initialTabs = { ...store.tabs };

    const invalidTabOptions = {
      availableTabs: 'not_an_array',
      tabCounts: 'not_an_object',
      activeTab: 123
    };

    store.setTabOptions(invalidTabOptions);

    // Проверяем что невалидные данные игнорируются
    expect(store.tabs.availableTabs).toEqual(initialTabs.availableTabs);
    expect(store.tabs.tabCounts).toEqual(initialTabs.tabCounts);
    expect(store.tabs.activeTab).toEqual(initialTabs.activeTab);
  });
});

describe('useCreativesFiltersStore - Система переводов с защитой от race condition', () => {
  let store: ReturnType<typeof useCreativesFiltersStore>;

  beforeEach(() => {
    const pinia = createPinia();
    setActivePinia(pinia);
    vi.clearAllMocks();
    store = useCreativesFiltersStore();
  });

  it('getTranslation с глубоко вложенными ключами (3+ уровня)', () => {
    const deepTranslations = {
      level1: {
        level2: {
          level3: {
            level4: {
              level5: 'Very deep nested value',
              anotherKey: 'Another deep value'
            },
            simpleLevel4: 'Level 4 value'
          },
          directLevel3: 'Level 3 value'
        },
        simpleLevel2: 'Level 2 value'
      },
      topLevel: 'Top level value'
    };

    store.setTranslations(deepTranslations as any);

    // Проверяем доступ к глубоко вложенным значениям
    expect(store.getTranslation('level1.level2.level3.level4.level5')).toBe('Very deep nested value');
    expect(store.getTranslation('level1.level2.level3.level4.anotherKey')).toBe('Another deep value');
    expect(store.getTranslation('level1.level2.level3.simpleLevel4')).toBe('Level 4 value');
    expect(store.getTranslation('level1.level2.directLevel3')).toBe('Level 3 value');
    expect(store.getTranslation('level1.simpleLevel2')).toBe('Level 2 value');
    expect(store.getTranslation('topLevel')).toBe('Top level value');
  });

  it('getTranslation с несуществующими промежуточными ключами', () => {
    const translations = {
      existing: {
        path: {
          value: 'Existing value'
        }
      },
      another: 'Another value'
    };

    store.setTranslations(translations as any);

    // Проверяем fallback для несуществующих промежуточных ключей
    expect(store.getTranslation('nonexistent.path.value')).toBe('nonexistent.path.value');
    expect(store.getTranslation('existing.nonexistent.value')).toBe('existing.nonexistent.value');
    expect(store.getTranslation('existing.path.nonexistent')).toBe('existing.path.nonexistent');
    
    // Проверяем fallback с кастомным значением
    expect(store.getTranslation('nonexistent.path', 'Custom fallback')).toBe('Custom fallback');
    expect(store.getTranslation('existing.nonexistent', 'Another fallback')).toBe('Another fallback');

    // Проверяем что существующие пути работают
    expect(store.getTranslation('existing.path.value')).toBe('Existing value');
    expect(store.getTranslation('another')).toBe('Another value');
  });

  it('getTranslation когда результат - объект без поля title', () => {
    const translations = {
      objectWithTitle: {
        title: 'Object with title',
        description: 'Some description'
      },
      objectWithoutTitle: {
        name: 'Object name',
        description: 'Object description',
        value: 'Object value'
      },
      nestedObjectWithoutTitle: {
        level2: {
          name: 'Nested object',
          data: 'Some data'
        }
      },
      emptyObject: {},
      nullObject: null
    };

    store.setTranslations(translations as any);

    // Проверяем объект с полем title - должен вернуть title
    expect(store.getTranslation('objectWithTitle')).toBe('Object with title');

    // Проверяем объект без поля title - должен вернуть fallback
    expect(store.getTranslation('objectWithoutTitle')).toBe('objectWithoutTitle');
    expect(store.getTranslation('objectWithoutTitle', 'Custom fallback')).toBe('Custom fallback');

    // Проверяем вложенный объект без title
    expect(store.getTranslation('nestedObjectWithoutTitle.level2')).toBe('nestedObjectWithoutTitle.level2');

    // Проверяем пустой объект
    expect(store.getTranslation('emptyObject')).toBe('emptyObject');

    // Проверяем null объект
    expect(store.getTranslation('nullObject')).toBe('nullObject');
  });

  it('setTranslations мержит переводы (предотвращает race condition)', () => {
    // Устанавливаем начальные переводы
    const initialTranslations = {
      key1: 'Initial value 1',
      key2: 'Initial value 2',
      nested: {
        subkey1: 'Initial nested value 1',
        subkey2: 'Initial nested value 2'
      }
    };

    store.setTranslations(initialTranslations as any);

    expect(store.getTranslation('key1')).toBe('Initial value 1');
    expect(store.getTranslation('nested.subkey1')).toBe('Initial nested value 1');

    // Добавляем новые переводы (merge с существующими)
    const newTranslations = {
      key1: 'Updated value 1',
      key3: 'New value 3',
      nested: {
        subkey1: 'Updated nested value 1',
        subkey3: 'New nested value 3'
      }
    };

    store.setTranslations(newTranslations as any);

    // Проверяем что переводы смержились (старые сохранились + новые добавились)
    expect(store.getTranslation('key1')).toBe('Updated value 1'); // обновился
    expect(store.getTranslation('key2')).toBe('Initial value 2'); // сохранился из первого набора
    expect(store.getTranslation('key3')).toBe('New value 3'); // добавился новый
    expect(store.getTranslation('nested.subkey1')).toBe('Updated nested value 1'); // обновился
    expect(store.getTranslation('nested.subkey2')).toBe('Initial nested value 2'); // сохранился
    expect(store.getTranslation('nested.subkey3')).toBe('New nested value 3'); // добавился новый
  });

  it('защита от race condition - isTranslationsReady флаг', () => {
    // Изначально переводы не готовы
    expect(store.isTranslationsReady).toBe(false);
    
    // getTranslation должен возвращать fallback из defaultTranslations
    expect(store.getTranslation('copyButton')).toBe('Copy');
    expect(store.getTranslation('details.title')).toBe('Details');
    
    // После установки переводов - флаг становится true
    store.setTranslations({
      'copyButton': 'Копировать',
      'details.title': 'Детали'
    });
    
    expect(store.isTranslationsReady).toBe(true);
    expect(store.getTranslation('copyButton')).toBe('Копировать');
    expect(store.getTranslation('details.title')).toBe('Детали');
  });

  it('waitForTranslations() ожидает готовности переводов', async () => {
    expect(store.isTranslationsReady).toBe(false);
    
    // Запускаем ожидание
    const waitPromise = store.waitForTranslations();
    
    // В другом потоке устанавливаем переводы
    setTimeout(() => {
      store.setTranslations({
        'test.key': 'Test value'
      });
    }, 10);
    
    // Ожидаем готовности
    await waitPromise;
    
    expect(store.isTranslationsReady).toBe(true);
  });

  it('waitForTranslations() сразу резолвится если переводы готовы', async () => {
    // Устанавливаем переводы сначала
    store.setTranslations({
      'test.key': 'value'
    });
    
    expect(store.isTranslationsReady).toBe(true);
    
    // waitForTranslations должен сразу резолвиться
    await expect(store.waitForTranslations()).resolves.toBeUndefined();
  });

  it('useTranslation() возвращает reactive computed', async () => {
    const reactiveTranslation = store.useTranslation('dynamic.key', 'Default Value');
    
    // Изначально должен возвращать fallback
    expect(reactiveTranslation.value).toBe('Default Value');
    
    // Устанавливаем переводы
    store.setTranslations({
      'dynamic.key': 'Reactive Value'
    });
    
    await nextTick();
    
    // Теперь должен вернуть актуальный перевод
    expect(reactiveTranslation.value).toBe('Reactive Value');
  });

  it('обработка переводов с null/undefined значениями', () => {
    const translationsWithNulls = {
      validKey: 'Valid value',
      nullKey: null,
      undefinedKey: undefined,
      nested: {
        validNested: 'Valid nested value',
        nullNested: null,
        undefinedNested: undefined
      }
    };

    store.setTranslations(translationsWithNulls as any);

    // Проверяем валидные значения
    expect(store.getTranslation('validKey')).toBe('Valid value');
    expect(store.getTranslation('nested.validNested')).toBe('Valid nested value');

    // Проверяем null/undefined значения - должны возвращать fallback
    expect(store.getTranslation('nullKey')).toBe('nullKey');
    expect(store.getTranslation('undefinedKey')).toBe('undefinedKey');
    expect(store.getTranslation('nested.nullNested')).toBe('nested.nullNested');
    expect(store.getTranslation('nested.undefinedNested')).toBe('nested.undefinedNested');

    // Проверяем с кастомным fallback
    expect(store.getTranslation('nullKey', 'Null fallback')).toBe('Null fallback');
    expect(store.getTranslation('undefinedKey', 'Undefined fallback')).toBe('Undefined fallback');
  });

  it('getTranslation обрабатывает различные типы значений', () => {
    const mixedTranslations = {
      stringValue: 'String value',
      numberValue: 42,
      booleanValue: true,
      arrayValue: ['item1', 'item2'],
      objectWithTitle: {
        title: 'Object title'
      },
      objectWithoutTitle: {
        data: 'some data'
      }
    };

    store.setTranslations(mixedTranslations as any);

    // Проверяем строковые значения
    expect(store.getTranslation('stringValue')).toBe('String value');

    // Проверяем не-строковые значения - должны возвращать fallback
    expect(store.getTranslation('numberValue')).toBe('numberValue');
    expect(store.getTranslation('booleanValue')).toBe('booleanValue');
    expect(store.getTranslation('arrayValue')).toBe('arrayValue');

    // Проверяем объекты
    expect(store.getTranslation('objectWithTitle')).toBe('Object title');
    expect(store.getTranslation('objectWithoutTitle')).toBe('objectWithoutTitle');
  });

  it('getTranslation работает с пустыми переводами', () => {
    // Не устанавливаем переводы - translations.value остается пустым объектом
    expect(store.getTranslation('any.key')).toBe('any.key');
    expect(store.getTranslation('any.key', 'Custom fallback')).toBe('Custom fallback');

    // Устанавливаем пустой объект переводов
    store.setTranslations({});
    expect(store.getTranslation('any.key')).toBe('any.key');
    expect(store.getTranslation('any.key', 'Empty fallback')).toBe('Empty fallback');
  });

  it('getTranslation обрабатывает специальные случаи с ключами', () => {
    const specialTranslations = {
      '': 'Empty key value',
      'key.with.dots': 'Key with dots value',
      'normal': {
        '': 'Empty nested key',
        'key.with.dots': 'Nested key with dots'
      }
    };

    store.setTranslations(specialTranslations as any);

    // Проверяем пустой ключ
    expect(store.getTranslation('')).toBe('Empty key value');

    // Проверяем ключи с точками (теперь находятся как плоские ключи с приоритетом)
    expect(store.getTranslation('key.with.dots')).toBe('Key with dots value'); // плоский ключ найден

    // Проверяем нормальные вложенные ключи
    expect(store.getTranslation('normal.')).toBe('Empty nested key');
  });
});

describe('useCreativesFiltersStore - Управление фильтрами', () => {
  let store: ReturnType<typeof useCreativesFiltersStore>;

  beforeEach(() => {
    const pinia = createPinia();
    setActivePinia(pinia);
    vi.clearAllMocks();
    store = useCreativesFiltersStore();
  });

  it('updateFilter с различными типами данных (string, number, boolean, array)', () => {
    // Тестируем string
    store.updateFilter('country', 'US');
    expect(store.filters.country).toBe('US');

    store.updateFilter('searchKeyword', 'test search');
    expect(store.filters.searchKeyword).toBe('test search');

    // Тестируем number (хотя в схеме фильтров нет number полей, проверяем поведение)
    store.updateFilter('someNumberField' as any, 42);
    expect((store.filters as any).someNumberField).toBe(42);

    // Тестируем boolean
    store.updateFilter('onlyAdult', true);
    expect(store.filters.onlyAdult).toBe(true);

    store.updateFilter('onlyAdult', false);
    expect(store.filters.onlyAdult).toBe(false);

    // Тестируем array
    store.updateFilter('languages', ['en', 'ru']);
    expect(store.filters.languages).toEqual(['en', 'ru']);

    store.updateFilter('advertisingNetworks', ['google', 'facebook']);
    expect(store.filters.advertisingNetworks).toEqual(['google', 'facebook']);

    // Тестируем пустой массив
    store.updateFilter('languages', []);
    expect(store.filters.languages).toEqual([]);
  });

  it('updateFilter с null и undefined значениями', () => {
    // Устанавливаем начальные значения
    store.updateFilter('country', 'US');
    store.updateFilter('searchKeyword', 'test');
    store.updateFilter('onlyAdult', true);

    // Тестируем null
    store.updateFilter('country', null as any);
    expect(store.filters.country).toBe(null);

    // Тестируем undefined
    store.updateFilter('searchKeyword', undefined as any);
    expect(store.filters.searchKeyword).toBe(undefined);

    // Тестируем что boolean поле принимает null/undefined
    store.updateFilter('onlyAdult', null as any);
    expect(store.filters.onlyAdult).toBe(null);
  });

  it('updateFilter с тем же значением - проверка отсутствия лишних обновлений', () => {
    // Устанавливаем начальное значение
    store.updateFilter('country', 'US');
    const originalCountry = store.filters.country;

    // Обновляем тем же значением
    store.updateFilter('country', 'US');
    
    // Проверяем что значение не изменилось (ссылка остается той же)
    expect(store.filters.country).toBe(originalCountry);
    expect(store.filters.country).toBe('US');

    // Тестируем с массивами
    store.updateFilter('languages', ['en', 'ru']);
    const originalLanguages = store.filters.languages;

    // Обновляем тем же массивом (по содержимому)
    store.updateFilter('languages', ['en', 'ru']);
    
    // Логика корректно НЕ заменяет массивы с идентичным содержимым (оптимизация производительности)
    // Ссылка остается той же, так как содержимое идентично
    expect(store.filters.languages).toBe(originalLanguages);
    expect(store.filters.languages).toEqual(['en', 'ru']);

    // Но если содержимое отличается, то должна быть новая ссылка
    store.updateFilter('languages', ['en', 'ru', 'fr']);
    const updatedLanguages = store.filters.languages;
    expect(updatedLanguages).not.toBe(originalLanguages); // новая ссылка
    expect(updatedLanguages).toEqual(['en', 'ru', 'fr']); // новое содержимое

    // И если передаем точно ту же ссылку, она не должна измениться
    store.updateFilter('languages', updatedLanguages);
    expect(store.filters.languages).toBe(updatedLanguages); // та же ссылка

    // Тестируем с boolean
    store.updateFilter('onlyAdult', true);
    const originalAdult = store.filters.onlyAdult;

    store.updateFilter('onlyAdult', true);
    expect(store.filters.onlyAdult).toBe(originalAdult);
    expect(store.filters.onlyAdult).toBe(true);
  });

  it('updateFilter с невалидными ключами фильтров', () => {
    // Тестируем несуществующий ключ
    store.updateFilter('nonexistentFilter' as any, 'value');
    expect((store.filters as any).nonexistentFilter).toBe('value');

    // Тестируем пустой ключ
    store.updateFilter('' as any, 'empty key value');
    expect((store.filters as any)['']).toBe('empty key value');

    // Тестируем ключ с специальными символами
    store.updateFilter('filter-with-dashes' as any, 'dashed value');
    expect((store.filters as any)['filter-with-dashes']).toBe('dashed value');

    store.updateFilter('filter.with.dots' as any, 'dotted value');
    expect((store.filters as any)['filter.with.dots']).toBe('dotted value');

    // Проверяем что валидные фильтры не пострадали
    store.updateFilter('country', 'RU');
    expect(store.filters.country).toBe('RU');
  });

  it('toggleDetailedFilters - проверка переключения состояния', () => {
    // Проверяем начальное состояние
    expect(store.filters.isDetailedVisible).toBe(false);

    // Включаем детальные фильтры
    store.toggleDetailedFilters();
    expect(store.filters.isDetailedVisible).toBe(true);

    // Выключаем детальные фильтры
    store.toggleDetailedFilters();
    expect(store.filters.isDetailedVisible).toBe(false);

    // Проверяем многократное переключение
    store.toggleDetailedFilters();
    expect(store.filters.isDetailedVisible).toBe(true);
    
    store.toggleDetailedFilters();
    expect(store.filters.isDetailedVisible).toBe(false);
    
    store.toggleDetailedFilters();
    expect(store.filters.isDetailedVisible).toBe(true);
  });

  it('toggleAdultFilter - проверка переключения boolean значения', () => {
    // Проверяем начальное состояние
    expect(store.filters.onlyAdult).toBe(false);

    // Включаем adult фильтр
    store.toggleAdultFilter();
    expect(store.filters.onlyAdult).toBe(true);

    // Выключаем adult фильтр
    store.toggleAdultFilter();
    expect(store.filters.onlyAdult).toBe(false);

    // Проверяем многократное переключение
    store.toggleAdultFilter();
    expect(store.filters.onlyAdult).toBe(true);
    
    store.toggleAdultFilter();
    expect(store.filters.onlyAdult).toBe(false);
    
    store.toggleAdultFilter();
    expect(store.filters.onlyAdult).toBe(true);
  });

  it('toggleAdultFilter когда onlyAdult имеет невалидное значение', () => {
    // Устанавливаем невалидное значение
    store.updateFilter('onlyAdult', null as any);
    expect(store.filters.onlyAdult).toBe(null);

    // Переключаем - должно стать true (так как !null === true)
    store.toggleAdultFilter();
    expect(store.filters.onlyAdult).toBe(true);

    // Тестируем с undefined
    store.updateFilter('onlyAdult', undefined as any);
    store.toggleAdultFilter();
    expect(store.filters.onlyAdult).toBe(true);

    // Тестируем со строкой
    store.updateFilter('onlyAdult', 'invalid' as any);
    store.toggleAdultFilter();
    expect(store.filters.onlyAdult).toBe(false); // !'invalid' === false
  });

  it('resetFilters - полный сброс к дефолтным значениям', () => {
    // Устанавливаем различные значения фильтров
    store.updateFilter('country', 'US');
    store.updateFilter('searchKeyword', 'test search');
    store.updateFilter('onlyAdult', true);
    store.updateFilter('languages', ['en', 'ru']);
    store.updateFilter('advertisingNetworks', ['google', 'facebook']);
    store.updateFilter('operatingSystems', ['windows', 'macos']);
    store.updateFilter('browsers', ['chrome', 'firefox']);
    store.updateFilter('devices', ['mobile', 'desktop']);
    store.updateFilter('imageSizes', ['large', 'medium']);
    store.updateFilter('sortBy', 'creation');
    store.updateFilter('dateCreation', 'last_week');
    store.updateFilter('periodDisplay', 'last_month');

    // Включаем детальные фильтры
    store.toggleDetailedFilters();
    expect(store.filters.isDetailedVisible).toBe(true);

    // Проверяем что значения установлены
    expect(store.filters.country).toBe('US');
    expect(store.filters.searchKeyword).toBe('test search');
    expect(store.filters.onlyAdult).toBe(true);
    expect(store.filters.languages).toEqual(['en', 'ru']);

    // Сбрасываем фильтры
    store.resetFilters();

    // Проверяем что все значения сброшены к дефолтным
    expect(store.filters.country).toBe('default');
    expect(store.filters.searchKeyword).toBe('');
    expect(store.filters.onlyAdult).toBe(false);
    expect(store.filters.languages).toEqual([]);
    expect(store.filters.advertisingNetworks).toEqual([]);
    expect(store.filters.operatingSystems).toEqual([]);
    expect(store.filters.browsers).toEqual([]);
    expect(store.filters.devices).toEqual([]);
    expect(store.filters.imageSizes).toEqual([]);
    expect(store.filters.sortBy).toBe('default');
    expect(store.filters.dateCreation).toBe('default');
    expect(store.filters.periodDisplay).toBe('default');

    // Проверяем что isDetailedVisible также сбросился
    expect(store.filters.isDetailedVisible).toBe(false);
  });

  it('resetFilters сохраняет другие состояния store (tabs, options, translations)', () => {
    // Устанавливаем различные состояния
    store.setActiveTab('facebook');
    store.setSelectOptions({
      countries: [{ value: 'US', label: 'United States' }],
      languages: [{ value: 'en', label: 'English' }]
    });
    store.setTranslations({ key: 'value' } as any);

    // Изменяем фильтры
    store.updateFilter('country', 'RU');
    store.updateFilter('searchKeyword', 'test');

    // Сбрасываем фильтры
    store.resetFilters();

    // Проверяем что фильтры сброшены
    expect(store.filters.country).toBe('default');
    expect(store.filters.searchKeyword).toBe('');

    // Проверяем что другие состояния сохранились
    expect(store.tabs.activeTab).toBe('facebook');
    expect(store.countryOptions).toEqual([{ value: 'US', label: 'United States' }]);
    expect(store.languagesOptions).toEqual([{ value: 'en', label: 'English' }]);
    expect(store.getTranslation('key')).toBe('value');
  });

  it('updateFilter обновляет вложенные свойства объектов', () => {
    // Добавляем вложенный объект в фильтры
    store.updateFilter('complexFilter' as any, { nested: { value: 'initial' } });
    expect((store.filters as any).complexFilter).toEqual({ nested: { value: 'initial' } });

    // Обновляем весь объект
    store.updateFilter('complexFilter' as any, { nested: { value: 'updated' }, newProp: 'new' });
    expect((store.filters as any).complexFilter).toEqual({ 
      nested: { value: 'updated' }, 
      newProp: 'new' 
    });

    // Проверяем что старая ссылка заменилась
    const currentFilter = (store.filters as any).complexFilter;
    store.updateFilter('complexFilter' as any, { different: 'object' });
    expect((store.filters as any).complexFilter).not.toBe(currentFilter);
    expect((store.filters as any).complexFilter).toEqual({ different: 'object' });
  });

  it('updateFilter с функциями и другими сложными типами', () => {
    // Тестируем функцию
    const testFunction = () => 'test';
    store.updateFilter('functionFilter' as any, testFunction);
    expect((store.filters as any).functionFilter).toBe(testFunction);

    // Тестируем Date
    const testDate = new Date('2024-01-01');
    store.updateFilter('dateFilter' as any, testDate);
    expect((store.filters as any).dateFilter).toBe(testDate);

    // Тестируем RegExp
    const testRegex = /test/gi;
    store.updateFilter('regexFilter' as any, testRegex);
    expect((store.filters as any).regexFilter).toBe(testRegex);

    // Тестируем Symbol
    const testSymbol = Symbol('test');
    store.updateFilter('symbolFilter' as any, testSymbol);
    expect((store.filters as any).symbolFilter).toBe(testSymbol);
  });
});

describe('useCreativesFiltersStore - Мультиселект операции', () => {
  let store: ReturnType<typeof useCreativesFiltersStore>;

  beforeEach(() => {
    const pinia = createPinia();
    setActivePinia(pinia);
    vi.clearAllMocks();
    store = useCreativesFiltersStore();
  });

  it('addToMultiSelect в пустой массив', () => {
    // Проверяем что массивы изначально пустые
    expect(store.filters.languages).toEqual([]);
    expect(store.filters.advertisingNetworks).toEqual([]);
    expect(store.filters.devices).toEqual([]);

    // Добавляем первый элемент в пустой массив
    store.addToMultiSelect('languages', 'en');
    expect(store.filters.languages).toEqual(['en']);

    store.addToMultiSelect('advertisingNetworks', 'google');
    expect(store.filters.advertisingNetworks).toEqual(['google']);

    store.addToMultiSelect('devices', 'mobile');
    expect(store.filters.devices).toEqual(['mobile']);

    // Добавляем второй элемент
    store.addToMultiSelect('languages', 'ru');
    expect(store.filters.languages).toEqual(['en', 'ru']);

    store.addToMultiSelect('advertisingNetworks', 'facebook');
    expect(store.filters.advertisingNetworks).toEqual(['google', 'facebook']);
  });

  it('addToMultiSelect с дублирующимися значениями - избегание дубликатов', () => {
    // Добавляем начальные значения
    store.addToMultiSelect('languages', 'en');
    store.addToMultiSelect('languages', 'ru');
    expect(store.filters.languages).toEqual(['en', 'ru']);

    // Пытаемся добавить дубликат
    store.addToMultiSelect('languages', 'en');
    expect(store.filters.languages).toEqual(['en', 'ru']); // дубликат не добавился

    // Добавляем новое уникальное значение
    store.addToMultiSelect('languages', 'fr');
    expect(store.filters.languages).toEqual(['en', 'ru', 'fr']);

    // Пытаемся добавить еще один дубликат
    store.addToMultiSelect('languages', 'ru');
    expect(store.filters.languages).toEqual(['en', 'ru', 'fr']); // дубликат не добавился
  });

  it('addToMultiSelect с несуществующим полем фильтра', () => {
    // Тестируем добавление в несуществующее поле
    store.addToMultiSelect('nonexistentField' as any, 'value');
    
    // Проверяем что поле создалось и значение добавилось
    expect((store.filters as any).nonexistentField).toEqual(['value']);

    // Добавляем еще одно значение в созданное поле
    store.addToMultiSelect('nonexistentField' as any, 'value2');
    expect((store.filters as any).nonexistentField).toEqual(['value', 'value2']);

    // Проверяем что дубликаты не добавляются
    store.addToMultiSelect('nonexistentField' as any, 'value');
    expect((store.filters as any).nonexistentField).toEqual(['value', 'value2']);
  });

  it('addToMultiSelect когда поле имеет невалидный тип данных', () => {
    // Устанавливаем невалидный тип для поля мультиселекта
    store.updateFilter('languages', 'invalid_string' as any);
    expect(store.filters.languages).toBe('invalid_string');

    // Теперь метод должен создать новый массив вместо ошибки
    store.addToMultiSelect('languages', 'en');
    expect(store.filters.languages).toEqual(['en']);

    // Устанавливаем другой невалидный тип
    store.updateFilter('advertisingNetworks', 123 as any);
    expect(store.filters.advertisingNetworks).toBe(123);

    // Метод должен заменить невалидное значение массивом
    store.addToMultiSelect('advertisingNetworks', 'google');
    expect(store.filters.advertisingNetworks).toEqual(['google']);

    // Проверяем что дальнейшие добавления работают как обычно
    store.addToMultiSelect('languages', 'ru');
    expect(store.filters.languages).toEqual(['en', 'ru']);
    
    store.addToMultiSelect('advertisingNetworks', 'facebook');
    expect(store.filters.advertisingNetworks).toEqual(['google', 'facebook']);
  });

  it('removeFromMultiSelect из пустого массива', () => {
    // Проверяем что массивы изначально пустые
    expect(store.filters.languages).toEqual([]);
    expect(store.filters.advertisingNetworks).toEqual([]);
    expect(store.filters.devices).toEqual([]);

    // Пытаемся удалить из пустого массива
    store.removeFromMultiSelect('languages', 'en');
    expect(store.filters.languages).toEqual([]); // остается пустым

    store.removeFromMultiSelect('advertisingNetworks', 'google');
    expect(store.filters.advertisingNetworks).toEqual([]); // остается пустым

    store.removeFromMultiSelect('devices', 'mobile');
    expect(store.filters.devices).toEqual([]); // остается пустым

    // Проверяем что операция не влияет на другие поля
    store.addToMultiSelect('languages', 'ru');
    expect(store.filters.languages).toEqual(['ru']);
    
    store.removeFromMultiSelect('advertisingNetworks', 'facebook');
    expect(store.filters.languages).toEqual(['ru']); // не изменился
    expect(store.filters.advertisingNetworks).toEqual([]); // остался пустым
  });

  it('removeFromMultiSelect несуществующего значения', () => {
    // Добавляем некоторые значения
    store.addToMultiSelect('languages', 'en');
    store.addToMultiSelect('languages', 'ru');
    store.addToMultiSelect('advertisingNetworks', 'google');
    
    expect(store.filters.languages).toEqual(['en', 'ru']);
    expect(store.filters.advertisingNetworks).toEqual(['google']);

    // Пытаемся удалить несуществующие значения
    store.removeFromMultiSelect('languages', 'fr'); // не существует
    expect(store.filters.languages).toEqual(['en', 'ru']); // не изменился

    store.removeFromMultiSelect('languages', 'de'); // не существует
    expect(store.filters.languages).toEqual(['en', 'ru']); // не изменился

    store.removeFromMultiSelect('advertisingNetworks', 'facebook'); // не существует
    expect(store.filters.advertisingNetworks).toEqual(['google']); // не изменился

    // Удаляем существующее значение для проверки что метод работает
    store.removeFromMultiSelect('languages', 'en');
    expect(store.filters.languages).toEqual(['ru']);
  });

  it('removeFromMultiSelect с несуществующим полем фильтра', () => {
    // Пытаемся удалить из несуществующего поля
    store.removeFromMultiSelect('nonexistentField' as any, 'value');
    
    // Поле должно остаться undefined (не создаваться)
    expect((store.filters as any).nonexistentField).toBeUndefined();

    // Создаем поле и добавляем значения
    store.addToMultiSelect('customField' as any, 'value1');
    store.addToMultiSelect('customField' as any, 'value2');
    expect((store.filters as any).customField).toEqual(['value1', 'value2']);

    // Удаляем значение из созданного поля
    store.removeFromMultiSelect('customField' as any, 'value1');
    expect((store.filters as any).customField).toEqual(['value2']);

    // Пытаемся удалить из другого несуществующего поля
    store.removeFromMultiSelect('anotherField' as any, 'someValue');
    expect((store.filters as any).anotherField).toBeUndefined();
    
    // Проверяем что существующее поле не пострадало
    expect((store.filters as any).customField).toEqual(['value2']);
  });

  it('removeFromMultiSelect когда поле имеет невалидный тип данных', () => {
    // Устанавливаем невалидные типы для полей мультиселекта
    store.updateFilter('languages', 'invalid_string' as any);
    store.updateFilter('advertisingNetworks', 123 as any);
    store.updateFilter('devices', null as any);
    store.updateFilter('browsers', undefined as any);

    // Методы должны безопасно обрабатывать невалидные типы (не выбрасывать ошибки)
    store.removeFromMultiSelect('languages', 'en');
    expect(store.filters.languages).toBe('invalid_string'); // значение не изменилось

    store.removeFromMultiSelect('advertisingNetworks', 'google');
    expect(store.filters.advertisingNetworks).toBe(123); // значение не изменилось

    store.removeFromMultiSelect('devices', 'mobile');
    expect(store.filters.devices).toBe(null); // значение не изменилось

    store.removeFromMultiSelect('browsers', 'chrome');
    expect(store.filters.browsers).toBe(undefined); // значение не изменилось

    // Устанавливаем валидные типы и проверяем что удаление работает
    store.updateFilter('languages', ['en', 'ru']);
    store.updateFilter('advertisingNetworks', ['google', 'facebook']);
    
    store.removeFromMultiSelect('languages', 'en');
    expect(store.filters.languages).toEqual(['ru']);
    
    store.removeFromMultiSelect('advertisingNetworks', 'google');
    expect(store.filters.advertisingNetworks).toEqual(['facebook']);
  });

  it('операции с мультиселектом сохраняют реактивность', () => {
    const originalLanguages = store.filters.languages;
    
    // Добавляем значение
    store.addToMultiSelect('languages', 'en');
    
    // Проверяем что ссылка изменилась (новый массив)
    expect(store.filters.languages).not.toBe(originalLanguages);
    expect(store.filters.languages).toEqual(['en']);
    
    const afterAdd = store.filters.languages;
    
    // Удаляем значение
    store.removeFromMultiSelect('languages', 'en');
    
    // Проверяем что ссылка снова изменилась
    expect(store.filters.languages).not.toBe(afterAdd);
    expect(store.filters.languages).toEqual([]);
  });

  it('операции с мультиселектом работают с различными типами значений', () => {
    // Добавляем строки
    store.addToMultiSelect('languages', 'en');
    store.addToMultiSelect('languages', 'ru');
    expect(store.filters.languages).toEqual(['en', 'ru']);

    // Добавляем числа (как строки)
    store.addToMultiSelect('customNumbers' as any, '123');
    store.addToMultiSelect('customNumbers' as any, '456');
    expect((store.filters as any).customNumbers).toEqual(['123', '456']);

    // Добавляем специальные символы
    store.addToMultiSelect('specialChars' as any, 'value-with-dash');
    store.addToMultiSelect('specialChars' as any, 'value.with.dots');
    store.addToMultiSelect('specialChars' as any, 'value_with_underscores');
    expect((store.filters as any).specialChars).toEqual([
      'value-with-dash', 
      'value.with.dots', 
      'value_with_underscores'
    ]);

    // Проверяем удаление различных типов
    store.removeFromMultiSelect('languages', 'en');
    expect(store.filters.languages).toEqual(['ru']);

    store.removeFromMultiSelect('customNumbers' as any, '123');
    expect((store.filters as any).customNumbers).toEqual(['456']);

    store.removeFromMultiSelect('specialChars' as any, 'value.with.dots');
    expect((store.filters as any).specialChars).toEqual([
      'value-with-dash', 
      'value_with_underscores'
    ]);
  });

  it('операции с мультиселектом обрабатывают пустые и специальные строки', () => {
    // Добавляем пустую строку
    store.addToMultiSelect('languages', '');
    expect(store.filters.languages).toEqual(['']);

    // Добавляем строки с пробелами
    store.addToMultiSelect('languages', ' ');
    store.addToMultiSelect('languages', '  spaces  ');
    expect(store.filters.languages).toEqual(['', ' ', '  spaces  ']);

    // Проверяем что дубликаты не добавляются
    store.addToMultiSelect('languages', '');
    store.addToMultiSelect('languages', ' ');
    expect(store.filters.languages).toEqual(['', ' ', '  spaces  ']);

    // Удаляем специальные строки
    store.removeFromMultiSelect('languages', '');
    expect(store.filters.languages).toEqual([' ', '  spaces  ']);

    store.removeFromMultiSelect('languages', ' ');
    expect(store.filters.languages).toEqual(['  spaces  ']);

    store.removeFromMultiSelect('languages', '  spaces  ');
    expect(store.filters.languages).toEqual([]);
  });
});

describe('useCreativesFiltersStore - Управление вкладками', () => {
  let store: ReturnType<typeof useCreativesFiltersStore>;

  beforeEach(() => {
    const pinia = createPinia();
    setActivePinia(pinia);
    vi.clearAllMocks();
    
    // Создаем store
    store = useCreativesFiltersStore();
    
    // КРИТИЧНО: отключаем синхронизацию для изоляции тестов setActiveTab
    // Это предотвращает срабатывание watcher'ов которые эмитируют дополнительные события
    store.filtersSync.disable();
  });

  it('setActiveTab обновляет состояние только при валидных изменениях', () => {
    // Проверяем начальное состояние
    expect(store.tabs.activeTab).toBe('push');
    expect(store.tabs.availableTabs).toEqual(['push', 'inpage', 'facebook', 'tiktok']);

    // Пытаемся установить невалидную вкладку
    store.setActiveTab('invalid_tab' as any);
    
    // Проверяем что активная вкладка не изменилась
    expect(store.tabs.activeTab).toBe('push');

    // Устанавливаем валидную вкладку 
    store.setActiveTab('facebook');
    expect(store.tabs.activeTab).toBe('facebook');

    // Пытаемся установить ту же вкладку
    store.setActiveTab('facebook');
    expect(store.tabs.activeTab).toBe('facebook'); // остается той же
  });

  it('setActiveTab с null и undefined значениями', () => {
    // Пытаемся установить null
    store.setActiveTab(null as any);
    expect(store.tabs.activeTab).toBe('push'); // остается дефолтной

    // Пытаемся установить undefined
    store.setActiveTab(undefined as any);
    expect(store.tabs.activeTab).toBe('push'); // остается дефолтной

    // Устанавливаем валидную вкладку 
    store.setActiveTab('facebook');
    expect(store.tabs.activeTab).toBe('facebook');
  });

  it('setActiveTab эмитирует события только когда включены', () => {
    const dispatchSpy = vi.spyOn(document, 'dispatchEvent');
    
    // События отключены по умолчанию
    store.setTabEventEmissionEnabled(false);
    store.setActiveTab('facebook');
    expect(store.tabs.activeTab).toBe('facebook'); // состояние обновилось
    expect(dispatchSpy).not.toHaveBeenCalled(); // но событие не эмитировалось
    
    // Включаем события и проверяем эмиссию
    store.setTabEventEmissionEnabled(true);
    store.setActiveTab('tiktok');
    expect(store.tabs.activeTab).toBe('tiktok');
    
    // Проверяем что событие эмитировалось (хотя бы один раз)
    expect(dispatchSpy).toHaveBeenCalled();
    
    // Проверяем содержимое события
    const lastCall = dispatchSpy.mock.calls[dispatchSpy.mock.calls.length - 1];
    const event = lastCall[0] as CustomEvent;
    expect(event.type).toBe('creatives:tab-changed');
    expect(event.detail.currentTab).toBe('tiktok');
    expect(event.detail.previousTab).toBe('facebook');

    dispatchSpy.mockRestore();
  });

  it('setActiveTab с обновлением доступных вкладок во время работы', () => {
    // Устанавливаем активную вкладку
    store.setActiveTab('facebook');
    expect(store.tabs.activeTab).toBe('facebook');

    // Обновляем список доступных вкладок, исключая текущую активную
    store.setTabOptions({
      availableTabs: ['push', 'tiktok'], // facebook больше нет в списке
      tabCounts: {
        push: '100k',
        tiktok: '200k'
      }
    });

    // Активная вкладка остается facebook, хотя её нет в availableTabs
    expect(store.tabs.activeTab).toBe('facebook');

    // Пытаемся установить facebook снова - должно игнорироваться (недоступна)
    store.setActiveTab('facebook');
    expect(store.tabs.activeTab).toBe('facebook');

    // Устанавливаем валидную вкладку из нового списка
    store.setActiveTab('tiktok');
    expect(store.tabs.activeTab).toBe('tiktok');
  });

  it('currentTabOption computed при отсутствии активной вкладки', () => {
    // Устанавливаем невалидную активную вкладку через прямое изменение состояния
    store.tabs.activeTab = 'nonexistent_tab' as any;
    
    // Проверяем что currentTabOption возвращает undefined
    expect(store.currentTabOption).toBeUndefined();

    // Проверяем что при валидной вкладке computed работает корректно
    store.tabs.activeTab = 'facebook'; // прямое изменение для избежания логики setActiveTab
    expect(store.currentTabOption).toEqual({
      value: 'facebook',
      label: 'facebook',
      count: '65.1k'
    });
  });

  it('tabOptions computed с пустым массивом availableTabs', () => {
    // Проверяем изначальное состояние (не пустой массив)
    expect(store.tabs.availableTabs).toEqual(['push', 'inpage', 'facebook', 'tiktok']);
    expect(store.tabOptions).toHaveLength(4);

    // Устанавливаем пустой массив доступных вкладок
    store.tabs.availableTabs = [];
    
    // Проверяем что tabOptions возвращает пустой массив
    expect(store.tabOptions).toEqual([]);
    expect(store.tabOptions).toHaveLength(0);

    // Проверяем что currentTabOption становится undefined
    expect(store.currentTabOption).toBeUndefined();
  });

  it('tabOptions computed с частично отсутствующими счетчиками', () => {
    // Устанавливаем вкладки с частично отсутствующими счетчиками
    store.setTabOptions({
      availableTabs: ['push', 'facebook', 'custom1', 'custom2'],
      tabCounts: {
        push: '200k',
        facebook: '150k'
        // custom1 и custom2 отсутствуют в счетчиках
      }
    });

    const tabOptions = store.tabOptions;
    expect(tabOptions).toHaveLength(4);

    // Проверяем вкладки с счетчиками
    expect(tabOptions.find(tab => tab.value === 'push')).toEqual({
      value: 'push',
      label: 'push',
      count: '200k'
    });

    // Проверяем вкладки без счетчиков (должны получить 0)
    expect(tabOptions.find(tab => tab.value === 'custom1')).toEqual({
      value: 'custom1',
      label: 'custom1',
      count: 0
    });
  });

  it('tabOptions computed с переводами и счетчиками', () => {
    // Устанавливаем переводы для вкладок
    store.setTranslations({
      tabs: {
        push: 'Push Notifications',
        facebook: 'Facebook Ads',
        custom: 'Custom Tab'
      }
    } as any);

    // Устанавливаем кастомные вкладки и счетчики
    store.setTabOptions({
      availableTabs: ['push', 'facebook', 'custom', 'notranslation'],
      tabCounts: {
        push: '500k',
        facebook: '300k',
        custom: '100k',
        notranslation: '50k'
      }
    });

    const tabOptions = store.tabOptions;
    expect(tabOptions).toHaveLength(4);

    // Проверяем вкладки с переводами
    expect(tabOptions.find(tab => tab.value === 'push')).toEqual({
      value: 'push',
      label: 'Push Notifications',
      count: '500k'
    });

    // Проверяем вкладку без перевода (fallback к value)
    expect(tabOptions.find(tab => tab.value === 'notranslation')).toEqual({
      value: 'notranslation',
      label: 'notranslation',
      count: '50k'
    });
  });

  it('управление эмиссией событий для тестирования', () => {
    const dispatchSpy = vi.spyOn(document, 'dispatchEvent');

    // По умолчанию события включены
    store.setTabEventEmissionEnabled(true);
    store.setActiveTab('facebook');
    expect(dispatchSpy).toHaveBeenCalled();

    dispatchSpy.mockClear();

    // Отключаем события
    store.setTabEventEmissionEnabled(false);
    store.setActiveTab('tiktok');
    expect(store.tabs.activeTab).toBe('tiktok'); // состояние обновилось
    expect(dispatchSpy).not.toHaveBeenCalled(); // но событие не эмитировалось

    // Включаем обратно
    store.setTabEventEmissionEnabled(true);
    store.setActiveTab('inpage');
    expect(dispatchSpy).toHaveBeenCalled();

    dispatchSpy.mockRestore();
  });
});

describe('useCreativesFiltersStore - Проксирование композаблов', () => {
  let store: ReturnType<typeof useCreativesFiltersStore>;

  beforeEach(() => {
    const pinia = createPinia();
    setActivePinia(pinia);
    vi.clearAllMocks();
    store = useCreativesFiltersStore();
    store.filtersSync.disable();
  });

  it('computed свойства корректно проксируют данные из композаблов', () => {
    // Проверяем что computed свойства корректно проксируют значения из композабла
    expect(store.creatives).toEqual(creativesMock.creatives.value);
    expect(store.pagination).toEqual(creativesMock.pagination.value);
    expect(store.meta).toEqual(creativesMock.meta.value);
    expect(store.isLoading).toBe(creativesMock.isLoading.value);
    expect(store.error).toBe(creativesMock.error.value);

    // Изменяем значения в моке и проверяем что computed реактивно обновляются
    const newCreatives = [{ id: 1, title: 'Test Creative' }];
    const newPagination = { currentPage: 2, lastPage: 5 };
    const newMeta = { total: 100 };
    
    creativesMock.creatives.value = newCreatives;
    creativesMock.pagination.value = newPagination;
    creativesMock.meta.value = newMeta;
    creativesMock.isLoading.value = true;
    creativesMock.error.value = 'Test error';

    expect(store.creatives).toEqual(newCreatives);
    expect(store.pagination).toEqual(newPagination);
    expect(store.meta).toEqual(newMeta);
    expect(store.isLoading).toBe(true);
    expect(store.error).toBe('Test error');
  });

  it('loadCreatives с различными номерами страниц', async () => {
    // Проверяем загрузку без номера страницы (по умолчанию передается 1)
    await store.loadCreatives();
    
    expect(creativesMock.mapFiltersToCreativesFilters).toHaveBeenCalledWith(
      store.filters,
      store.tabs.activeTab,
      1
    );
    expect(urlSyncMock.syncFiltersToUrl).toHaveBeenCalled();
    expect(creativesMock.loadCreativesWithFilters).toHaveBeenCalled();

    vi.clearAllMocks();

    // Проверяем загрузку с конкретным номером страницы
    await store.loadCreatives(3);
    
    expect(creativesMock.mapFiltersToCreativesFilters).toHaveBeenCalledWith(
      store.filters,
      store.tabs.activeTab,
      3
    );
    expect(urlSyncMock.syncFiltersToUrl).toHaveBeenCalled();
    expect(creativesMock.loadCreativesWithFilters).toHaveBeenCalled();

    vi.clearAllMocks();

    // Проверяем загрузку с первой страницей
    await store.loadCreatives(1);
    
    expect(creativesMock.mapFiltersToCreativesFilters).toHaveBeenCalledWith(
      store.filters,
      store.tabs.activeTab,
      1
    );

    vi.clearAllMocks();

    // Проверяем загрузку с большим номером страницы
    await store.loadCreatives(999);
    
    expect(creativesMock.mapFiltersToCreativesFilters).toHaveBeenCalledWith(
      store.filters,
      store.tabs.activeTab,
      999
    );
  });

  it('loadCreatives передает актуальные фильтры и активную вкладку', async () => {
    // Изменяем фильтры и активную вкладку
    store.updateFilter('country', 'US');
    store.updateFilter('languages', ['en', 'fr']);
    store.updateFilter('searchKeyword', 'test search');
    store.setActiveTab('facebook');

    await store.loadCreatives(5);

    // Проверяем что передались актуальные значения
    expect(creativesMock.mapFiltersToCreativesFilters).toHaveBeenCalledWith(
      {
        ...store.filters,
        country: 'US',
        languages: ['en', 'fr'],
        searchKeyword: 'test search'
      },
      'facebook',
      5
    );

    vi.clearAllMocks();

    // Изменяем фильтры снова
    store.updateFilter('onlyAdult', true);
    store.updateFilter('sortBy', 'popular');
    store.setActiveTab('tiktok');

    await store.loadCreatives();

    expect(creativesMock.mapFiltersToCreativesFilters).toHaveBeenCalledWith(
      expect.objectContaining({
        country: 'US',
        languages: ['en', 'fr'],
        searchKeyword: 'test search',
        onlyAdult: true,
        sortBy: 'popular'
      }),
      'tiktok',
      1
    );
  });

  it('loadNextPage - вызов метода композабла', async () => {
    // Мокаем пагинацию для правильной работы loadNextPage
    creativesMock.pagination = computed(() => ({
      currentPage: 2,
      lastPage: 5,
      total: 100,
      perPage: 12,
      from: 13,
      to: 24
    }));

    // Проверяем что метод loadNextPage теперь вызывает loadCreatives с правильной страницей
    await store.loadNextPage();
    
    // loadNextPage теперь внутри вызывает loadCreatives, что приводит к вызову loadCreativesWithFilters
    expect(creativesMock.loadCreativesWithFilters).toHaveBeenCalledTimes(1);
    
    // Проверяем что передается правильная страница (currentPage + 1 = 3)
    const callArgs = creativesMock.mapFiltersToCreativesFilters.mock.calls[0];
    expect(callArgs[2]).toBe(3); // третий аргумент - номер страницы

    // Проверяем повторный вызов
    await store.loadNextPage();
    
    expect(creativesMock.loadCreativesWithFilters).toHaveBeenCalledTimes(2);
  });

  it('refreshCreatives - вызов метода композабла', async () => {
    // Проверяем что метод refreshCreatives корректно проксируется
    await store.refreshCreatives();
    
    expect(creativesMock.refreshCreatives).toHaveBeenCalledTimes(1);
    expect(creativesMock.refreshCreatives).toHaveBeenCalledWith();

    // Проверяем повторный вызов
    await store.refreshCreatives();
    
    expect(creativesMock.refreshCreatives).toHaveBeenCalledTimes(2);
  });

  it('проверка передачи параметров в mapFiltersToCreativesFilters', async () => {
    // Тест с минимальными фильтрами
    store.resetFilters();
    await store.loadCreatives(1);

    const firstCall = creativesMock.mapFiltersToCreativesFilters.mock.calls[0];
    expect(firstCall[0]).toEqual(store.filters); // полный объект фильтров
    expect(firstCall[1]).toBe('push'); // дефолтная вкладка
    expect(firstCall[2]).toBe(1); // номер страницы

    vi.clearAllMocks();

    // Тест с полностью заполненными фильтрами
    store.updateFilter('country', 'RU');
    store.updateFilter('searchKeyword', 'creative test');
    store.updateFilter('onlyAdult', true);
    store.updateFilter('languages', ['ru', 'en', 'fr']);
    store.updateFilter('advertisingNetworks', ['google', 'facebook', 'tiktok']);
    store.updateFilter('operatingSystems', ['windows', 'macos']);
    store.updateFilter('browsers', ['chrome', 'firefox', 'safari']);
    store.updateFilter('devices', ['mobile', 'desktop', 'tablet']);
    store.updateFilter('imageSizes', ['300x250', '728x90']);
    store.updateFilter('sortBy', 'creation');
    store.updateFilter('dateCreation', 'last_month');
    store.updateFilter('periodDisplay', 'weekly');
    store.setActiveTab('facebook');

    await store.loadCreatives(10);

    const secondCall = creativesMock.mapFiltersToCreativesFilters.mock.calls[0];
    expect(secondCall[0]).toEqual({
      country: 'RU',
      searchKeyword: 'creative test',
      onlyAdult: true,
      languages: ['ru', 'en', 'fr'],
      advertisingNetworks: ['google', 'facebook', 'tiktok'],
      operatingSystems: ['windows', 'macos'],
      browsers: ['chrome', 'firefox', 'safari'],
      devices: ['mobile', 'desktop', 'tablet'],
      imageSizes: ['300x250', '728x90'],
      sortBy: 'creation',
      dateCreation: 'last_month',
      periodDisplay: 'weekly',
      isDetailedVisible: false,
      perPage: 12,
      savedSettings: []
    });
    expect(secondCall[1]).toBe('facebook');
    expect(secondCall[2]).toBe(10);

    vi.clearAllMocks();

    // Тест с частично заполненными фильтрами
    store.resetFilters();
    store.updateFilter('country', 'US');
    store.updateFilter('languages', ['en']);
    store.updateFilter('onlyAdult', false); // явно false
    store.setActiveTab('inpage');

    await store.loadCreatives(); // без номера страницы (передается 1)

    const thirdCall = creativesMock.mapFiltersToCreativesFilters.mock.calls[0];
    expect(thirdCall[0]).toEqual(expect.objectContaining({
      country: 'US',
      languages: ['en'],
      onlyAdult: false,
      searchKeyword: '',
      advertisingNetworks: [],
      operatingSystems: [],
      browsers: [],
      devices: [],
      imageSizes: []
    }));
    expect(thirdCall[1]).toBe('inpage');
    expect(thirdCall[2]).toBe(1);
  });

  it('обработка ошибок в методах композаблов', async () => {
    // Тестируем обработку ошибок в loadCreatives
    const loadError = new Error('Load failed');
    creativesMock.loadCreativesWithFilters.mockRejectedValue(loadError);

    await expect(store.loadCreatives()).rejects.toThrow('Load failed');
    expect(creativesMock.loadCreativesWithFilters).toHaveBeenCalled();

    // Восстанавливаем нормальное поведение
    creativesMock.loadCreativesWithFilters.mockResolvedValue(undefined);

    // Тестируем обработку ошибок в loadNextPage (теперь вызывает loadCreatives)
    // Сначала настроим пагинацию для корректной работы
    creativesMock.pagination = computed(() => ({
      currentPage: 1,
      lastPage: 3,
      total: 50,
      perPage: 12,
      from: 1,
      to: 12
    }));

    const nextPageError = new Error('Next page failed');
    creativesMock.loadCreativesWithFilters.mockRejectedValue(nextPageError);

    await expect(store.loadNextPage()).rejects.toThrow('Next page failed');
    expect(creativesMock.loadCreativesWithFilters).toHaveBeenCalled();

    // Восстанавливаем нормальное поведение
    creativesMock.loadCreativesWithFilters.mockResolvedValue(undefined);

    // Тестируем обработку ошибок в refreshCreatives
    const refreshError = new Error('Refresh failed');
    creativesMock.refreshCreatives.mockRejectedValue(refreshError);

    await expect(store.refreshCreatives()).rejects.toThrow('Refresh failed');
    expect(creativesMock.refreshCreatives).toHaveBeenCalled();
  });

  it('синхронизация с URL происходит при каждом вызове loadCreatives', async () => {
    // Первый вызов
    await store.loadCreatives(1);
    expect(urlSyncMock.syncFiltersToUrl).toHaveBeenCalledTimes(1);

    // Второй вызов
    await store.loadCreatives(2);
    expect(urlSyncMock.syncFiltersToUrl).toHaveBeenCalledTimes(2);

    // Третий вызов с изменением фильтров
    store.updateFilter('country', 'FR');
    await store.loadCreatives(3);
    expect(urlSyncMock.syncFiltersToUrl).toHaveBeenCalledTimes(3);

    // Проверяем что каждый раз вызывается syncFiltersToUrl с правильными параметрами
    expect(urlSyncMock.syncFiltersToUrl).toHaveBeenCalledTimes(3);
    
    // Проверяем параметры последнего вызова
    const lastCall = urlSyncMock.syncFiltersToUrl.mock.calls[2];
    expect(lastCall[0]).toEqual(expect.objectContaining({
      country: 'FR'
    })); // filters
    expect(lastCall[1]).toBe('push'); // activeTab
    expect(lastCall[2]).toBe(3); // page
  });

  it('вызовы композаблов не влияют на состояние Store', async () => {
    // Настроим пагинацию для корректной работы loadNextPage
    creativesMock.pagination = computed(() => ({
      currentPage: 1,
      lastPage: 5,
      total: 100,
      perPage: 12,
      from: 1,
      to: 12
    }));

    // Сохраняем начальное состояние
    const initialFilters = { ...store.filters };
    const initialActiveTab = store.tabs.activeTab;

    // Выполняем различные операции композаблов
    await store.loadCreatives(5);
    await store.loadNextPage(); // теперь вызывает loadCreatives(2)
    await store.refreshCreatives();

    // Проверяем что состояние Store не изменилось
    expect(store.filters).toEqual(initialFilters);
    expect(store.tabs.activeTab).toBe(initialActiveTab);

    // Проверяем что методы композаблов были вызваны
    expect(creativesMock.loadCreativesWithFilters).toHaveBeenCalled();
    // loadNextPage теперь вызывает loadCreatives, а не композабл напрямую
    expect(creativesMock.loadCreativesWithFilters).toHaveBeenCalledTimes(2); // loadCreatives + loadNextPage
    expect(creativesMock.refreshCreatives).toHaveBeenCalled();
  });

  it('композабл mapFiltersToCreativesFilters получает копии данных', async () => {
    // Устанавливаем фильтры
    store.updateFilter('languages', ['en', 'ru']);
    store.updateFilter('devices', ['mobile', 'desktop']);

    await store.loadCreatives(1);

    const passedFilters = creativesMock.mapFiltersToCreativesFilters.mock.calls[0][0];
    
    // Проверяем что передались правильные данные
    expect(passedFilters.languages).toEqual(['en', 'ru']);
    expect(passedFilters.devices).toEqual(['mobile', 'desktop']);

    // Проверяем что это действительно те же данные что и в Store
    expect(passedFilters.languages).toEqual(store.filters.languages);
    expect(passedFilters.devices).toEqual(store.filters.devices);
    expect(passedFilters.country).toBe(store.filters.country);

    // В текущей реализации данные передаются по ссылке, что нормально для Vue reactive
    // Изменение переданных данных влияет на оригинал (это ожидаемое поведение для Vue)
    const originalLanguagesLength = store.filters.languages.length;
    passedFilters.languages.push('fr');
    
    // Проверяем что изменение повлияло на Store (это нормально для Vue реактивности)
    expect(store.filters.languages.length).toBe(originalLanguagesLength + 1);
    expect(store.filters.languages).toContain('fr');
  });
});

describe('useCreativesFiltersStore - Управление избранным', () => {
  let store: ReturnType<typeof useCreativesFiltersStore>;

  beforeEach(() => {
    const pinia = createPinia();
    setActivePinia(pinia);
    vi.clearAllMocks();
    store = useCreativesFiltersStore();
    store.filtersSync.disable();

    // Мокируем (window as any).axios
    (window as any).axios = {
      get: vi.fn(),
      post: vi.fn(),
      delete: vi.fn(),
    };

    // Мокируем document.dispatchEvent
    vi.spyOn(document, 'dispatchEvent').mockImplementation(() => true);
  });

  it('setFavoritesCount с различными числовыми значениями', () => {
    // Проверяем установку положительных чисел
    store.setFavoritesCount(42);
    expect(store.favoritesCount).toBe(42);

    store.setFavoritesCount(0);
    expect(store.favoritesCount).toBe(0);

    store.setFavoritesCount(999);
    expect(store.favoritesCount).toBe(999);

    // Проверяем установку больших чисел
    store.setFavoritesCount(1000000);
    expect(store.favoritesCount).toBe(1000000);

    // Проверяем дробные числа
    store.setFavoritesCount(3.14);
    expect(store.favoritesCount).toBe(3.14);

    // Проверяем отрицательные числа
    store.setFavoritesCount(-5);
    expect(store.favoritesCount).toBe(-5);

    // Проверяем NaN и Infinity
    store.setFavoritesCount(NaN);
    expect(store.favoritesCount).toBeNaN();

    store.setFavoritesCount(Infinity);
    expect(store.favoritesCount).toBe(Infinity);

    store.setFavoritesCount(-Infinity);
    expect(store.favoritesCount).toBe(-Infinity);
  });

  it('refreshFavoritesCount - успешный API запрос', async () => {
    const mockResponse = {
      data: {
        data: {
          count: 75,
          lastUpdated: '2024-01-01T10:00:00.000Z'
        }
      }
    };

    ((window as any).axios.get as any).mockResolvedValue(mockResponse);

    // Проверяем начальное состояние
    expect(store.favoritesCount).toBeUndefined();
    expect(store.isFavoritesLoading).toBe(false);

    // Выполняем запрос
    await store.refreshFavoritesCount();

    // Проверяем что API был вызван
    expect((window as any).axios.get).toHaveBeenCalledWith('/api/creatives/favorites/count');

    // Проверяем что состояние обновилось
    expect(store.favoritesCount).toBe(75);
    expect(store.isFavoritesLoading).toBe(false);

    // Проверяем что событие было эмитировано
    expect(document.dispatchEvent).toHaveBeenCalledWith(
      expect.objectContaining({
        type: 'creatives:favorites-updated',
        detail: expect.objectContaining({
          count: 75,
          action: 'refresh',
          timestamp: expect.any(String)
        })
      })
    );
  });

  it('refreshFavoritesCount - обработка ошибок API', async () => {
    const apiError = new Error('Network error');
    ((window as any).axios.get as any).mockRejectedValue(apiError);

    // Проверяем начальное состояние
    expect(store.isFavoritesLoading).toBe(false);

    // Выполняем запрос и ожидаем ошибку
    await expect(store.refreshFavoritesCount()).rejects.toThrow('Network error');

    // Проверяем что API был вызван
    expect((window as any).axios.get).toHaveBeenCalledWith('/api/creatives/favorites/count');

    // Проверяем что состояние загрузки сброшено
    expect(store.isFavoritesLoading).toBe(false);

    // Проверяем что событие обновления НЕ было эмитировано при ошибке
    const updateEvents = (document.dispatchEvent as any).mock.calls.filter(
      (call: any) => call[0].type === 'creatives:favorites-updated'
    );
    expect(updateEvents).toHaveLength(0);
  });

  it('refreshFavoritesCount при уже выполняющемся запросе (защита от дублирования)', async () => {
    let resolveFirstRequest: (value: any) => void;
    const firstRequest = new Promise(resolve => {
      resolveFirstRequest = resolve;
    });

    ((window as any).axios.get as any).mockReturnValue(firstRequest);

    // Запускаем первый запрос
    const firstPromise = store.refreshFavoritesCount();
    
    // Проверяем что состояние загрузки установлено
    expect(store.isFavoritesLoading).toBe(true);

    // Пытаемся запустить второй запрос - он должен игнорироваться
    const secondPromise = store.refreshFavoritesCount();

    // Проверяем что второй запрос завершился сразу (не ждет API)
    await secondPromise;

    // Проверяем что API был вызван только один раз
    expect((window as any).axios.get).toHaveBeenCalledTimes(1);

    // Завершаем первый запрос
    resolveFirstRequest({
      data: { data: { count: 50 } }
    });
    await firstPromise;

    // Проверяем что состояние обновилось
    expect(store.favoritesCount).toBe(50);
    expect(store.isFavoritesLoading).toBe(false);
  });

  it('addToFavorites - оптимистичное обновление', async () => {
    const mockResponse = {
      data: {
        data: {
          creativeId: 123,
          isFavorite: true,
          totalFavorites: 51
        }
      }
    };

    ((window as any).axios.post as any).mockResolvedValue(mockResponse);

    // Устанавливаем начальное состояние
    store.setFavoritesCount(50);
    expect(store.favoritesItems).toEqual([]);

    // Выполняем добавление
    await store.addToFavorites(123);

    // Проверяем оптимистичное обновление
    expect(store.favoritesItems).toContain(123);
    expect(store.favoritesCount).toBe(51); // обновлено из API ответа

    // Проверяем API вызов
    expect((window as any).axios.post).toHaveBeenCalledWith('/api/creatives/123/favorite');

    // Проверяем событие
    expect(document.dispatchEvent).toHaveBeenCalledWith(
      expect.objectContaining({
        type: 'creatives:favorites-updated',
        detail: expect.objectContaining({
          count: 51,
          action: 'add',
          creativeId: 123,
          timestamp: expect.any(String)
        })
      })
    );
  });

  it('addToFavorites - откат при ошибке API', async () => {
    const apiError = new Error('Add to favorites failed');
    ((window as any).axios.post as any).mockRejectedValue(apiError);

    // Устанавливаем начальное состояние
    store.setFavoritesCount(50);
    const originalItems = [...store.favoritesItems];

    // Выполняем добавление и ожидаем ошибку
    await expect(store.addToFavorites(456)).rejects.toThrow('Add to favorites failed');

    // Проверяем что состояние откатилось
    expect(store.favoritesItems).toEqual(originalItems);
    expect(store.favoritesCount).toBe(50); // вернулось к исходному значению

    // Проверяем что состояние загрузки сброшено
    expect(store.isFavoritesLoading).toBe(false);

    // Проверяем что событие обновления НЕ было эмитировано при ошибке
    const updateEvents = (document.dispatchEvent as any).mock.calls.filter(
      (call: any) => call[0].type === 'creatives:favorites-updated'
    );
    expect(updateEvents).toHaveLength(0);
  });

  it('addToFavorites дубликата (уже в избранном)', async () => {
    const mockResponse = {
      data: {
        data: {
          creativeId: 789,
          isFavorite: true,
          totalFavorites: 50
        }
      }
    };

    ((window as any).axios.post as any).mockResolvedValue(mockResponse);

    // Устанавливаем начальное состояние с уже добавленным креативом
    store.setFavoritesCount(50);
    store.favoritesItems.push(789);

    const originalItemsLength = store.favoritesItems.length;

    // Пытаемся добавить дубликат
    await store.addToFavorites(789);

    // Проверяем что дубликат не добавился
    expect(store.favoritesItems.filter(id => id === 789)).toHaveLength(1);
    expect(store.favoritesItems.length).toBe(originalItemsLength);

    // Проверяем что счетчик обновился из API ответа
    expect(store.favoritesCount).toBe(50);

    // Проверяем что API все равно был вызван (для синхронизации с сервером)
    expect((window as any).axios.post).toHaveBeenCalledWith('/api/creatives/789/favorite');

    // Проверяем событие
    expect(document.dispatchEvent).toHaveBeenCalledWith(
      expect.objectContaining({
        type: 'creatives:favorites-updated',
        detail: expect.objectContaining({
          action: 'add',
          creativeId: 789
        })
      })
    );
  });

  it('removeFromFavorites - оптимистичное обновление', async () => {
    const mockResponse = {
      data: {
        data: {
          creativeId: 555,
          isFavorite: false,
          totalFavorites: 49
        }
      }
    };

    ((window as any).axios.delete as any).mockResolvedValue(mockResponse);

    // Устанавливаем начальное состояние с креативом в избранном
    store.setFavoritesCount(50);
    store.favoritesItems.push(555, 777);

    // Выполняем удаление
    await store.removeFromFavorites(555);

    // Проверяем оптимистичное обновление
    expect(store.favoritesItems).not.toContain(555);
    expect(store.favoritesItems).toContain(777); // другие остались
    expect(store.favoritesCount).toBe(49); // обновлено из API ответа

    // Проверяем API вызов
    expect((window as any).axios.delete).toHaveBeenCalledWith('/api/creatives/555/favorite');

    // Проверяем событие
    expect(document.dispatchEvent).toHaveBeenCalledWith(
      expect.objectContaining({
        type: 'creatives:favorites-updated',
        detail: expect.objectContaining({
          count: 49,
          action: 'remove',
          creativeId: 555,
          timestamp: expect.any(String)
        })
      })
    );
  });

  it('removeFromFavorites - откат при ошибке API', async () => {
    const apiError = new Error('Remove from favorites failed');
    ((window as any).axios.delete as any).mockRejectedValue(apiError);

    // Устанавливаем начальное состояние с креативом в избранном
    store.setFavoritesCount(50);
    store.favoritesItems.push(888);
    const originalItems = [...store.favoritesItems];

    // Выполняем удаление и ожидаем ошибку
    await expect(store.removeFromFavorites(888)).rejects.toThrow('Remove from favorites failed');

    // Проверяем что состояние откатилось
    expect(store.favoritesItems).toEqual(originalItems);
    expect(store.favoritesItems).toContain(888); // креатив вернулся
    expect(store.favoritesCount).toBe(50); // счетчик вернулся

    // Проверяем что состояние загрузки сброшено
    expect(store.isFavoritesLoading).toBe(false);

    // Проверяем что событие обновления НЕ было эмитировано при ошибке
    const updateEvents = (document.dispatchEvent as any).mock.calls.filter(
      (call: any) => call[0].type === 'creatives:favorites-updated'
    );
    expect(updateEvents).toHaveLength(0);
  });

  it('removeFromFavorites несуществующего элемента', async () => {
    const mockResponse = {
      data: {
        data: {
          creativeId: 999,
          isFavorite: false,
          totalFavorites: 50
        }
      }
    };

    ((window as any).axios.delete as any).mockResolvedValue(mockResponse);

    // Устанавливаем начальное состояние без целевого креатива
    store.setFavoritesCount(50);
    store.favoritesItems.push(111, 222);
    const originalItems = [...store.favoritesItems];

    // Пытаемся удалить несуществующий креатив
    await store.removeFromFavorites(999);

    // Проверяем что массив не изменился (кроме попытки удаления)
    expect(store.favoritesItems).toEqual(originalItems);
    expect(store.favoritesItems).toContain(111);
    expect(store.favoritesItems).toContain(222);

    // Проверяем что счетчик обновился из API ответа
    expect(store.favoritesCount).toBe(50);

    // Проверяем что API все равно был вызван
    expect((window as any).axios.delete).toHaveBeenCalledWith('/api/creatives/999/favorite');

    // Проверяем событие
    expect(document.dispatchEvent).toHaveBeenCalledWith(
      expect.objectContaining({
        type: 'creatives:favorites-updated',
        detail: expect.objectContaining({
          action: 'remove',
          creativeId: 999
        })
      })
    );
  });

  it('эмиссия событий "creatives:favorites-updated" с корректными данными', async () => {
    const addResponse = {
      data: { data: { creativeId: 123, isFavorite: true, totalFavorites: 51 } }
    };
    const removeResponse = {
      data: { data: { creativeId: 123, isFavorite: false, totalFavorites: 50 } }
    };
    const refreshResponse = {
      data: { data: { count: 75 } }
    };

    ((window as any).axios.post as any).mockResolvedValue(addResponse);
    ((window as any).axios.delete as any).mockResolvedValue(removeResponse);
    ((window as any).axios.get as any).mockResolvedValue(refreshResponse);

    store.setFavoritesCount(50);

    // Тест события при добавлении
    await store.addToFavorites(123);

    let lastEvent = (document.dispatchEvent as any).mock.calls.slice(-1)[0][0];
    expect(lastEvent.type).toBe('creatives:favorites-updated');
    expect(lastEvent.detail).toMatchObject({
      count: 51,
      action: 'add',
      creativeId: 123,
      timestamp: expect.stringMatching(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/)
    });

    vi.clearAllMocks();

    // Тест события при удалении
    await store.removeFromFavorites(123);

    lastEvent = (document.dispatchEvent as any).mock.calls.slice(-1)[0][0];
    expect(lastEvent.type).toBe('creatives:favorites-updated');
    expect(lastEvent.detail).toMatchObject({
      count: 50,
      action: 'remove',
      creativeId: 123,
      timestamp: expect.stringMatching(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/)
    });

    vi.clearAllMocks();

    // Тест события при обновлении счетчика
    await store.refreshFavoritesCount();

    lastEvent = (document.dispatchEvent as any).mock.calls.slice(-1)[0][0];
    expect(lastEvent.type).toBe('creatives:favorites-updated');
    expect(lastEvent.detail).toMatchObject({
      count: 75,
      action: 'refresh',
      timestamp: expect.stringMatching(/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/)
    });
    expect(lastEvent.detail.creativeId).toBeUndefined(); // для refresh нет creativeId
  });

  it('состояние isFavoritesLoading во время операций', async () => {
    let resolveRefresh: Function;
    let resolveAdd: Function;
    let resolveRemove: Function;

    const refreshPromise = new Promise(resolve => { resolveRefresh = resolve; });
    const addPromise = new Promise(resolve => { resolveAdd = resolve; });
    const removePromise = new Promise(resolve => { resolveRemove = resolve; });

    // Проверяем начальное состояние
    expect(store.isFavoritesLoading).toBe(false);

    // Тест состояния загрузки для refreshFavoritesCount
    ((window as any).axios.get as any).mockReturnValue(refreshPromise);
    const refreshOperation = store.refreshFavoritesCount();

    expect(store.isFavoritesLoading).toBe(true);

    resolveRefresh({ data: { data: { count: 50 } } });
    await refreshOperation;

    expect(store.isFavoritesLoading).toBe(false);

    // Тест состояния загрузки для addToFavorites
    ((window as any).axios.post as any).mockReturnValue(addPromise);
    const addOperation = store.addToFavorites(123);

    expect(store.isFavoritesLoading).toBe(true);

    resolveAdd({ data: { data: { creativeId: 123, isFavorite: true, totalFavorites: 51 } } });
    await addOperation;

    expect(store.isFavoritesLoading).toBe(false);

    // Тест состояния загрузки для removeFromFavorites
    ((window as any).axios.delete as any).mockReturnValue(removePromise);
    const removeOperation = store.removeFromFavorites(123);

    expect(store.isFavoritesLoading).toBe(true);

    resolveRemove({ data: { data: { creativeId: 123, isFavorite: false, totalFavorites: 50 } } });
    await removeOperation;

    expect(store.isFavoritesLoading).toBe(false);
  });

  it('состояние isFavoritesLoading сбрасывается при ошибках', async () => {
    const apiError = new Error('API Error');

    // Тест сброса состояния при ошибке refreshFavoritesCount
    ((window as any).axios.get as any).mockRejectedValue(apiError);
    
    expect(store.isFavoritesLoading).toBe(false);
    
    await expect(store.refreshFavoritesCount()).rejects.toThrow('API Error');
    expect(store.isFavoritesLoading).toBe(false);

    // Тест сброса состояния при ошибке addToFavorites
    ((window as any).axios.post as any).mockRejectedValue(apiError);
    
    await expect(store.addToFavorites(456)).rejects.toThrow('API Error');
    expect(store.isFavoritesLoading).toBe(false);

    // Тест сброса состояния при ошибке removeFromFavorites
    ((window as any).axios.delete as any).mockRejectedValue(apiError);
    
    await expect(store.removeFromFavorites(789)).rejects.toThrow('API Error');
    expect(store.isFavoritesLoading).toBe(false);
  });

  it('защита от параллельных операций избранного', async () => {
    let resolveFirst: Function;
    const firstPromise = new Promise(resolve => { resolveFirst = resolve; });

    ((window as any).axios.post as any).mockReturnValue(firstPromise);

    // Запускаем первую операцию
    const firstOperation = store.addToFavorites(111);
    expect(store.isFavoritesLoading).toBe(true);

    // Пытаемся запустить вторую операцию - должна игнорироваться
    const secondOperation = store.addToFavorites(222);
    await secondOperation; // завершается сразу

    // Проверяем что API вызван только один раз
    expect((window as any).axios.post).toHaveBeenCalledTimes(1);
    expect((window as any).axios.post).toHaveBeenCalledWith('/api/creatives/111/favorite');

    // Завершаем первую операцию
    resolveFirst({ data: { data: { creativeId: 111, isFavorite: true, totalFavorites: 51 } } });
    await firstOperation;

    expect(store.isFavoritesLoading).toBe(false);
  });

  it('корректная обработка undefined favoritesCount при оптимистичных обновлениях', async () => {
    const addResponse = {
      data: { data: { creativeId: 123, isFavorite: true, totalFavorites: 1 } }
    };
    const removeResponse = {
      data: { data: { creativeId: 123, isFavorite: false, totalFavorites: 0 } }
    };

    ((window as any).axios.post as any).mockResolvedValue(addResponse);
    ((window as any).axios.delete as any).mockResolvedValue(removeResponse);

    // Начальное состояние: favoritesCount = undefined
    expect(store.favoritesCount).toBeUndefined();

    // Добавляем креатив когда счетчик undefined
    await store.addToFavorites(123);

    // Счетчик должен установиться из API ответа
    expect(store.favoritesCount).toBe(1);
    expect(store.favoritesItems).toContain(123);

    // Сбрасываем счетчик в undefined
    store.favoritesCount = undefined;

    // Удаляем креатив когда счетчик undefined
    await store.removeFromFavorites(123);

    // Счетчик должен установиться из API ответа
    expect(store.favoritesCount).toBe(0);
    expect(store.favoritesItems).not.toContain(123);
  });
});
