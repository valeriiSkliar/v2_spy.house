import baseSelect from '@/alpine/components/baseSelect.js';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

describe('BaseSelect Component', () => {
  let component;
  let mockStore;
  let mockDispatch;
  let mockWatch;

  beforeEach(() => {
    // Создаем мок store
    mockStore = {
      creatives: {
        perPage: 12,
        filters: {
          category: '',
        },
      },
    };

    // Мокаем $dispatch
    mockDispatch = vi.fn();

    // Мокаем $watch
    mockWatch = vi.fn();

    // Базовые опции для тестов
    const defaultConfig = {
      initialSelectedValue: null,
      optionsArray: [
        { value: '12', label: '12 на странице' },
        { value: '24', label: '24 на странице' },
        { value: '48', label: '48 на странице' },
      ],
      elementId: 'test-select',
      useFlags: false,
      iconClass: null,
      placeholderText: 'Выберите опцию',
      storePath: null,
      onChangeCallback: null,
    };

    component = baseSelect(defaultConfig);

    // Мокаем Alpine.js контекст
    component.$store = mockStore;
    component.$dispatch = mockDispatch;
    component.$watch = mockWatch;
    component.$el = {
      addEventListener: vi.fn(),
    };
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  describe('Инициализация', () => {
    it('должен инициализироваться с правильными начальными значениями', () => {
      component.init();

      expect(component.open).toBe(false);
      expect(component.selectedOption).toEqual({ value: null, label: null });
      expect(component.optionsArray).toHaveLength(3);
      expect(component.placeholderText).toBe('Выберите опцию');
    });

    it('должен установить начальное значение если оно передано', () => {
      component.initialValue = '24';
      component.init();

      expect(component.selectedOption.value).toBe('24');
      expect(component.selectedOption.label).toBe('24 на странице');
    });

    it('должен создать временную опцию для неизвестного значения', () => {
      component.initialValue = 'unknown';
      component.init();

      expect(component.selectedOption.value).toBe('unknown');
      expect(component.selectedOption.label).toBe('unknown');
    });
  });

  describe('Работа с опциями', () => {
    beforeEach(() => {
      component.init();
    });

    it('должен находить опцию по строковому значению', () => {
      component.updateSelectedOptionFromValue('12');

      expect(component.selectedOption.value).toBe('12');
      expect(component.selectedOption.label).toBe('12 на странице');
    });

    it('должен находить опцию по числовому значению', () => {
      component.updateSelectedOptionFromValue(24);

      expect(component.selectedOption.value).toBe('24');
      expect(component.selectedOption.label).toBe('24 на странице');
    });

    it('должен обрабатывать null значения', () => {
      component.updateSelectedOptionFromValue(null);

      expect(component.selectedOption.value).toBe(null);
      expect(component.selectedOption.label).toBe(null);
    });

    it('должен обрабатывать undefined значения', () => {
      component.updateSelectedOptionFromValue(undefined);

      expect(component.selectedOption.value).toBe(null);
      expect(component.selectedOption.label).toBe(null);
    });

    it('должен создавать временную опцию для отсутствующего значения', () => {
      component.updateSelectedOptionFromValue('999');

      expect(component.selectedOption.value).toBe('999');
      expect(component.selectedOption.label).toBe('999');
    });
  });

  describe('Управление dropdown', () => {
    beforeEach(() => {
      component.init();
    });

    it('должен переключать состояние dropdown', () => {
      expect(component.open).toBe(false);

      component.toggleDropdown();
      expect(component.open).toBe(true);

      component.toggleDropdown();
      expect(component.open).toBe(false);
    });
  });

  describe('Выбор опций', () => {
    beforeEach(() => {
      component.init();
    });

    it('должен выбирать опцию и закрывать dropdown', () => {
      const option = { value: '24', label: '24 на странице' };

      component.selectOption(option);

      expect(component.selectedOption).toEqual(option);
      expect(component.open).toBe(false);
      expect(mockDispatch).toHaveBeenCalledWith('base-select:change', {
        id: 'test-select',
        value: '24',
        label: '24 на странице',
        order: undefined,
        option: option,
      });
    });

    it('должен обрабатывать опции с order', () => {
      const option = { value: '12', label: '12 на странице', order: 1 };

      component.selectOption(option);

      expect(mockDispatch).toHaveBeenCalledWith(
        'base-select:change',
        expect.objectContaining({
          order: 1,
        })
      );
    });
  });

  describe('Интеграция со Store', () => {
    beforeEach(() => {
      // Мокаем store методы
      mockStore.creatives.setPerPage = vi.fn();
      mockStore.creatives.handleFieldChange = vi.fn();
    });

    it('должен синхронизироваться со store при наличии storePath', () => {
      component.storePath = 'creatives.perPage';
      component.init();

      expect(mockWatch).toHaveBeenCalledWith('$store.creatives.perPage', expect.any(Function));
    });

    it('должен получать значение из store при инициализации', () => {
      component.storePath = 'creatives.perPage';
      mockStore.creatives.perPage = 24;

      component.init();

      expect(component.selectedOption.value).toBe('24');
    });

    it('должен обновлять store при выборе опции', () => {
      component.storePath = 'creatives.perPage';
      component.onChangeCallback = 'setPerPage';
      component.init();

      const option = { value: '48', label: '48 на странице' };
      component.selectOption(option);

      expect(mockStore.creatives.perPage).toBe(48);
      expect(mockStore.creatives.setPerPage).toHaveBeenCalledWith(48);
    });

    it('должен использовать handleFieldChange если нет конкретного callback', () => {
      component.storePath = 'creatives.perPage';
      component.init();

      const option = { value: '24', label: '24 на странице' };
      component.selectOption(option);

      expect(mockStore.creatives.handleFieldChange).toHaveBeenCalledWith('perPage', 24);
    });

    it('должен конвертировать числовые значения при отправке в store', () => {
      component.storePath = 'creatives.perPage';
      component.init();

      const option = { value: '36', label: '36 на странице' };
      component.selectOption(option);

      expect(mockStore.creatives.perPage).toBe(36);
      expect(typeof mockStore.creatives.perPage).toBe('number');
    });
  });

  describe('Граничные случаи и ошибки', () => {
    beforeEach(() => {
      component.init();
    });

    it('должен обрабатывать пустой массив опций', () => {
      component.optionsArray = [];
      component.updateSelectedOptionFromValue('test');

      expect(component.selectedOption.value).toBe('test');
      expect(component.selectedOption.label).toBe('test');
    });

    it('должен обрабатывать некорректные опции', () => {
      const invalidOption = null;

      expect(() => {
        component.selectOption(invalidOption);
      }).toThrow();
    });

    it('должен обрабатывать отсутствующий store', () => {
      component.$store = null;
      component.storePath = 'creatives.perPage';

      expect(() => {
        component.init();
      }).not.toThrow();
    });

    it('должен обрабатывать некорректный storePath', () => {
      component.storePath = 'nonexistent.path';

      expect(() => {
        component.init();
      }).not.toThrow();
    });

    it('должен обрабатывать отсутствующий callback в store', () => {
      component.storePath = 'creatives.perPage';
      component.onChangeCallback = 'nonexistentMethod';
      delete mockStore.creatives.setPerPage;
      delete mockStore.creatives.handleFieldChange;

      const option = { value: '12', label: '12 на странице' };

      expect(() => {
        component.selectOption(option);
      }).not.toThrow();
    });
  });

  describe('Обновление опций', () => {
    beforeEach(() => {
      component.init();
    });

    it('должен обновлять список опций', () => {
      const newOptions = [
        { value: '6', label: '6 на странице' },
        { value: '12', label: '12 на странице' },
      ];

      component.updateOptions(newOptions);

      expect(component.optionsArray).toEqual(newOptions);
    });

    it('должен пересинхронизировать выбранную опцию после обновления', () => {
      component.updateSelectedOptionFromValue('12');
      expect(component.selectedOption.label).toBe('12 на странице');

      // Обновляем опции с новыми label
      const newOptions = [
        { value: '12', label: 'Новый label для 12' },
        { value: '24', label: '24 на странице' },
      ];

      component.updateOptions(newOptions);

      expect(component.selectedOption.label).toBe('Новый label для 12');
    });

    it('должен обрабатывать null при обновлении опций', () => {
      component.updateOptions(null);

      expect(component.optionsArray).toEqual([]);
    });
  });

  describe('События и слушатели', () => {
    beforeEach(() => {
      component.init();
    });

    it('должен регистрировать слушатели событий при инициализации', () => {
      expect(component.$el.addEventListener).toHaveBeenCalledWith(
        'update-base-select-test-select',
        expect.any(Function)
      );
      expect(component.$el.addEventListener).toHaveBeenCalledWith(
        'update-options-test-select',
        expect.any(Function)
      );
    });
  });

  describe('Работа с флагами стран', () => {
    beforeEach(() => {
      component.useFlags = true;
      component.optionsArray = [
        { value: 'ru', label: 'Россия', code: 'ru' },
        { value: 'us', label: 'США', code: 'us' },
      ];
      component.init();
    });

    it('должен правильно обрабатывать опции с кодами стран', () => {
      const option = { value: 'ru', label: 'Россия', code: 'ru' };
      component.selectOption(option);

      expect(component.selectedOption.code).toBe('ru');
      expect(mockDispatch).toHaveBeenCalledWith(
        'base-select:change',
        expect.objectContaining({
          option: expect.objectContaining({ code: 'ru' }),
        })
      );
    });
  });
});
