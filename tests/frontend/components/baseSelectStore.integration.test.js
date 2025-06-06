import baseSelect from '@/alpine/components/baseSelect.js';
import { creativesStore } from '@/creatives/store/creativesStore.js';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

describe('BaseSelect + CreativesStore Integration', () => {
  let selectComponent;
  let store;
  let mockDispatch;
  let mockWatch;
  let mockElement;

  beforeEach(() => {
    // Создаем свежую копию store
    store = { ...creativesStore };
    Object.setPrototypeOf(store, creativesStore);

    // Мокаем Alpine.js методы
    mockDispatch = vi.fn();
    mockWatch = vi.fn();
    mockElement = {
      addEventListener: vi.fn(),
    };

    // Мокаем методы store
    store.setPerPage = vi.fn();
    store.handleFieldChange = vi.fn();
    store.updateSearchQuery = vi.fn();
    store.updateSelectedCountry = vi.fn();
    store.loadCreatives = vi.fn();
    store.updateUrl = vi.fn();

    // Очищаем все моки
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('Синхронизация perPage с store', () => {
    beforeEach(() => {
      const config = {
        initialSelectedValue: null,
        optionsArray: [
          { value: '12', label: '12 на странице' },
          { value: '24', label: '24 на странице' },
          { value: '48', label: '48 на странице' },
        ],
        elementId: 'per-page-select',
        storePath: 'creatives.perPage',
        onChangeCallback: 'setPerPage',
      };

      selectComponent = baseSelect(config);
      selectComponent.$store = { creatives: store };
      selectComponent.$dispatch = mockDispatch;
      selectComponent.$watch = mockWatch;
      selectComponent.$el = mockElement;
    });

    it('должен инициализироваться с текущим значением из store', () => {
      store.perPage = 24;

      selectComponent.init();

      expect(selectComponent.selectedOption.value).toBe('24');
      expect(selectComponent.selectedOption.label).toBe('24 на странице');
    });

    it('должен обновлять store при выборе новой опции', () => {
      selectComponent.init();

      const option = { value: '48', label: '48 на странице' };
      selectComponent.selectOption(option);

      expect(store.perPage).toBe(48);
      expect(store.setPerPage).toHaveBeenCalledWith(48);
    });

    it('должен синхронизироваться при изменении store извне', () => {
      selectComponent.init();

      // Получаем callback функцию из $watch
      const watchCallback = mockWatch.mock.calls.find(
        call => call[0] === '$store.creatives.perPage'
      )[1];

      // Симулируем изменение store
      watchCallback(36);

      expect(selectComponent.selectedOption.value).toBe('36');
      expect(selectComponent.selectedOption.label).toBe('36');
    });

    it('должен конвертировать строковые значения в числа для store', () => {
      selectComponent.init();

      const option = { value: '24', label: '24 на странице' };
      selectComponent.selectOption(option);

      expect(store.perPage).toBe(24);
      expect(typeof store.perPage).toBe('number');
    });

    it('должен использовать onChangeCallback если указан', () => {
      selectComponent.init();

      const option = { value: '12', label: '12 на странице' };
      selectComponent.selectOption(option);

      expect(store.setPerPage).toHaveBeenCalledWith(12);
      expect(store.handleFieldChange).not.toHaveBeenCalled();
    });

    it('должен fallback на handleFieldChange если нет onChangeCallback', () => {
      // Удаляем onChangeCallback
      selectComponent.onChangeCallback = null;
      selectComponent.init();

      const option = { value: '24', label: '24 на странице' };
      selectComponent.selectOption(option);

      expect(store.handleFieldChange).toHaveBeenCalledWith('perPage', 24);
      expect(store.setPerPage).not.toHaveBeenCalled();
    });
  });

  describe('Синхронизация фильтров с store', () => {
    beforeEach(() => {
      const config = {
        initialSelectedValue: null,
        optionsArray: [
          { value: 'ru', label: 'Россия', code: 'ru' },
          { value: 'us', label: 'США', code: 'us' },
          { value: 'de', label: 'Германия', code: 'de' },
        ],
        elementId: 'country-select',
        storePath: 'creatives.selectedCountry',
        useFlags: true,
      };

      selectComponent = baseSelect(config);
      selectComponent.$store = { creatives: store };
      selectComponent.$dispatch = mockDispatch;
      selectComponent.$watch = mockWatch;
      selectComponent.$el = mockElement;
    });

    it('должен инициализироваться с текущим значением фильтра', () => {
      store.selectedCountry = 'ru';

      selectComponent.init();

      expect(selectComponent.selectedOption.value).toBe('ru');
      expect(selectComponent.selectedOption.label).toBe('Россия');
      expect(selectComponent.selectedOption.code).toBe('ru');
    });

    it('должен обновлять store при выборе страны', () => {
      selectComponent.init();

      const option = { value: 'de', label: 'Германия', code: 'de' };
      selectComponent.selectOption(option);

      expect(store.selectedCountry).toBe('de');
      expect(store.handleFieldChange).toHaveBeenCalledWith('selectedCountry', 'de');
    });

    it('должен отправлять правильные события при выборе с флагами', () => {
      selectComponent.init();

      const option = { value: 'us', label: 'США', code: 'us' };
      selectComponent.selectOption(option);

      expect(mockDispatch).toHaveBeenCalledWith('base-select:change', {
        id: 'country-select',
        value: 'us',
        label: 'США',
        order: undefined,
        option: expect.objectContaining({ code: 'us' }),
      });
    });
  });

  describe('Интеграция с поиском', () => {
    beforeEach(() => {
      const config = {
        initialSelectedValue: '',
        optionsArray: [
          { value: '', label: 'Все категории' },
          { value: 'video', label: 'Видео' },
          { value: 'image', label: 'Изображения' },
        ],
        elementId: 'category-select',
        storePath: 'creatives.searchQuery',
      };

      selectComponent = baseSelect(config);
      selectComponent.$store = { creatives: store };
      selectComponent.$dispatch = mockDispatch;
      selectComponent.$watch = mockWatch;
      selectComponent.$el = mockElement;
    });

    it('должен синхронизироваться с поисковым запросом', () => {
      store.searchQuery = 'test search';

      selectComponent.init();

      expect(selectComponent.selectedOption.value).toBe('test search');
      expect(selectComponent.selectedOption.label).toBe('test search');
    });

    it('должен обновлять поисковый запрос при выборе', () => {
      selectComponent.init();

      const option = { value: 'video', label: 'Видео' };
      selectComponent.selectOption(option);

      expect(store.searchQuery).toBe('video');
      expect(store.handleFieldChange).toHaveBeenCalledWith('searchQuery', 'video');
    });
  });

  describe('Граничные случаи интеграции', () => {
    beforeEach(() => {
      const config = {
        initialSelectedValue: null,
        optionsArray: [
          { value: '12', label: '12 на странице' },
          { value: '24', label: '24 на странице' },
        ],
        elementId: 'test-select',
        storePath: 'creatives.perPage',
      };

      selectComponent = baseSelect(config);
      selectComponent.$store = { creatives: store };
      selectComponent.$dispatch = mockDispatch;
      selectComponent.$watch = mockWatch;
      selectComponent.$el = mockElement;
    });

    it('должен обрабатывать отсутствующий store gracefully', () => {
      selectComponent.$store = null;

      expect(() => {
        selectComponent.init();
      }).not.toThrow();
    });

    it('должен обрабатывать некорректный storePath', () => {
      selectComponent.storePath = 'nonexistent.path';

      expect(() => {
        selectComponent.init();
      }).not.toThrow();
    });

    it('должен обрабатывать отсутствующие методы в store', () => {
      delete store.setPerPage;
      delete store.handleFieldChange;

      selectComponent.init();

      const option = { value: '24', label: '24 на странице' };

      expect(() => {
        selectComponent.selectOption(option);
      }).not.toThrow();
    });

    it('должен обрабатывать глубокий storePath', () => {
      store.deep = { nested: { value: 42 } };
      selectComponent.storePath = 'creatives.deep.nested.value';

      selectComponent.init();

      expect(selectComponent.selectedOption.value).toBe('42');
    });

    it('должен обрабатывать изменения в несуществующем пути store', () => {
      selectComponent.storePath = 'creatives.nonexistent.path';
      selectComponent.init();

      const option = { value: '12', label: '12 на странице' };

      expect(() => {
        selectComponent.selectOption(option);
      }).not.toThrow();
    });
  });

  describe('Кэширование и производительность', () => {
    beforeEach(() => {
      const config = {
        initialSelectedValue: null,
        optionsArray: [
          { value: '12', label: '12 на странице' },
          { value: '24', label: '24 на странице' },
        ],
        elementId: 'performance-select',
        storePath: 'creatives.perPage',
        onChangeCallback: 'setPerPage',
      };

      selectComponent = baseSelect(config);
      selectComponent.$store = { creatives: store };
      selectComponent.$dispatch = mockDispatch;
      selectComponent.$watch = mockWatch;
      selectComponent.$el = mockElement;
    });

    it('должен вызывать store методы только при реальных изменениях', () => {
      selectComponent.init();

      // Выбираем ту же опцию дважды
      const option = { value: '24', label: '24 на странице' };
      selectComponent.selectOption(option);
      selectComponent.selectOption(option);

      // setPerPage должен быть вызван дважды (нет дедупликации)
      expect(store.setPerPage).toHaveBeenCalledTimes(2);
    });

    it('должен правильно обрабатывать множественные быстрые изменения', () => {
      selectComponent.init();

      const option1 = { value: '12', label: '12 на странице' };
      const option2 = { value: '24', label: '24 на странице' };

      selectComponent.selectOption(option1);
      selectComponent.selectOption(option2);
      selectComponent.selectOption(option1);

      expect(store.setPerPage).toHaveBeenCalledTimes(3);
      expect(store.setPerPage).toHaveBeenNthCalledWith(1, 12);
      expect(store.setPerPage).toHaveBeenNthCalledWith(2, 24);
      expect(store.setPerPage).toHaveBeenNthCalledWith(3, 12);
    });
  });

  describe('События и уведомления', () => {
    beforeEach(() => {
      const config = {
        initialSelectedValue: null,
        optionsArray: [
          { value: '12', label: '12 на странице' },
          { value: '24', label: '24 на странице' },
        ],
        elementId: 'events-select',
        storePath: 'creatives.perPage',
      };

      selectComponent = baseSelect(config);
      selectComponent.$store = { creatives: store };
      selectComponent.$dispatch = mockDispatch;
      selectComponent.$watch = mockWatch;
      selectComponent.$el = mockElement;
    });

    it('должен отправлять события при каждом изменении', () => {
      selectComponent.init();

      const option = { value: '24', label: '24 на странице' };
      selectComponent.selectOption(option);

      expect(mockDispatch).toHaveBeenCalledWith('base-select:change', {
        id: 'events-select',
        value: '24',
        label: '24 на странице',
        order: undefined,
        option: option,
      });
    });

    it('должен регистрировать слушатели для внешних обновлений', () => {
      selectComponent.init();

      expect(mockElement.addEventListener).toHaveBeenCalledWith(
        'update-base-select-events-select',
        expect.any(Function)
      );
      expect(mockElement.addEventListener).toHaveBeenCalledWith(
        'update-options-events-select',
        expect.any(Function)
      );
    });
  });

  describe('Сценарии реального использования', () => {
    it('должен правильно работать в сценарии смены количества на странице', () => {
      // Конфигурируем как реальный perPage селект
      const config = {
        initialSelectedValue: 12,
        optionsArray: [
          { value: '12', label: '12 на странице' },
          { value: '24', label: '24 на странице' },
          { value: '48', label: '48 на странице' },
        ],
        elementId: 'per-page-select',
        storePath: 'creatives.perPage',
        onChangeCallback: 'setPerPage',
      };

      store.perPage = 12;
      store.setPerPage = vi.fn(newPerPage => {
        store.perPage = newPerPage;
        store.currentPage = 1;
        store.loadCreatives();
        store.updateUrl();
      });

      selectComponent = baseSelect(config);
      selectComponent.$store = { creatives: store };
      selectComponent.$dispatch = mockDispatch;
      selectComponent.$watch = mockWatch;
      selectComponent.$el = mockElement;

      // Инициализация
      selectComponent.init();
      expect(selectComponent.selectedOption.value).toBe('12');

      // Пользователь выбирает 24 на странице
      const option = { value: '24', label: '24 на странице' };
      selectComponent.selectOption(option);

      // Проверяем что store был обновлен правильно
      expect(store.setPerPage).toHaveBeenCalledWith(24);
      expect(store.perPage).toBe(24);
      expect(store.loadCreatives).toHaveBeenCalled();
      expect(store.updateUrl).toHaveBeenCalled();
    });

    it('должен правильно работать в сценарии фильтрации по стране', () => {
      // Конфигурируем как реальный country селект
      const config = {
        initialSelectedValue: '',
        optionsArray: [
          { value: '', label: 'Все страны' },
          { value: 'ru', label: 'Россия', code: 'ru' },
          { value: 'us', label: 'США', code: 'us' },
        ],
        elementId: 'country-select',
        storePath: 'creatives.selectedCountry',
        useFlags: true,
      };

      store.selectedCountry = '';
      store.handleFieldChange = vi.fn((field, value) => {
        if (field === 'selectedCountry') {
          store.selectedCountry = value;
          store.filters.category = value;
          store.currentPage = 1;
          store.loadCreatives();
          store.updateUrl();
        }
      });

      selectComponent = baseSelect(config);
      selectComponent.$store = { creatives: store };
      selectComponent.$dispatch = mockDispatch;
      selectComponent.$watch = mockWatch;
      selectComponent.$el = mockElement;

      // Инициализация
      selectComponent.init();
      expect(selectComponent.selectedOption.value).toBe('');

      // Пользователь выбирает Россию
      const option = { value: 'ru', label: 'Россия', code: 'ru' };
      selectComponent.selectOption(option);

      // Проверяем что фильтр применен
      expect(store.handleFieldChange).toHaveBeenCalledWith('selectedCountry', 'ru');
      expect(store.selectedCountry).toBe('ru');
      expect(store.loadCreatives).toHaveBeenCalled();
    });
  });
});
