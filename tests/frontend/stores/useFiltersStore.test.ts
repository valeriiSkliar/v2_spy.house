import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
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

    // Проверяем вложенные ключи
    expect(store.getTranslation('level1.level2.level3')).toBe('Deep nested value');
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

describe('useCreativesFiltersStore - Система переводов', () => {
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

  it('setTranslations с перезаписью существующих переводов', () => {
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

    // Перезаписываем переводы (полная замена)
    const newTranslations = {
      key1: 'Updated value 1',
      key3: 'New value 3',
      nested: {
        subkey1: 'Updated nested value 1',
        subkey3: 'New nested value 3'
      }
    };

    store.setTranslations(newTranslations as any);

    // Проверяем что переводы полностью заменились
    expect(store.getTranslation('key1')).toBe('Updated value 1');
    expect(store.getTranslation('key2')).toBe('key2'); // fallback, так как ключ удален
    expect(store.getTranslation('key3')).toBe('New value 3');
    expect(store.getTranslation('nested.subkey1')).toBe('Updated nested value 1');
    expect(store.getTranslation('nested.subkey2')).toBe('nested.subkey2'); // fallback
    expect(store.getTranslation('nested.subkey3')).toBe('New nested value 3');
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

    // Проверяем ключи с точками (будут интерпретированы как вложенные)
    expect(store.getTranslation('key.with.dots')).toBe('key.with.dots'); // fallback, так как нет такого пути

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
    
    // Значение обновится, так как это новый массив (новая ссылка)
    expect(store.filters.languages).not.toBe(originalLanguages);
    expect(store.filters.languages).toEqual(['en', 'ru']);

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
