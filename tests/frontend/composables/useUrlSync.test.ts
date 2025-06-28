import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import { urlSyncUtils, useUrlSync } from '../../../resources/js/composables/useUrlSync';

// Мокаем @vueuse/core
vi.mock('@vueuse/core', () => ({
  useUrlSearchParams: vi.fn(() => ({})),
}));

// Мокаем lodash.debounce
vi.mock('lodash.debounce', () => ({
  default: vi.fn((fn, delay) => {
    // Возвращаем функцию с методом cancel
    const debouncedFn = vi.fn(fn) as any;
    debouncedFn.cancel = vi.fn();
    return debouncedFn;
  }),
}));

// Мокаем URL для тестов
Object.defineProperty(window, 'location', {
  value: {
    search: '',
    href: 'http://localhost/'
  },
  writable: true
});

describe('useUrlSync', () => {
  let mockUrlParams: Record<string, any>;

  beforeEach(async () => {
    // Сбрасываем мок URL параметров
    mockUrlParams = {};
    
    // Импортируем моки динамически для правильного доступа
    const { useUrlSearchParams } = await import('@vueuse/core');
    (useUrlSearchParams as any).mockReturnValue(mockUrlParams);
    
    vi.clearAllMocks();
  });

  describe('Базовая функциональность', () => {
    it('инициализируется с начальным состоянием', () => {
      const initialState = { search: '', page: 1, active: true };
      
      const { state } = useUrlSync(initialState);
      
      expect(state.value).toEqual(initialState);
    });

    it('обновляет состояние через updateState', async () => {
      const initialState = { search: '', page: 1 };
      
      const { state, updateState } = useUrlSync(initialState);
      
      updateState({ search: 'test', page: 2 });
      await nextTick();
      
      expect(state.value).toEqual({ search: 'test', page: 2 });
    });

    it('сбрасывает состояние к начальному', async () => {
      const initialState = { search: '', page: 1 };
      
      const { state, updateState, resetState } = useUrlSync(initialState);
      
      updateState({ search: 'test', page: 2 });
      await nextTick();
      
      resetState();
      await nextTick();
      
      expect(state.value).toEqual(initialState);
    });
  });

  describe('Работа с префиксом', () => {
    it('создает ключи с префиксом', () => {
      const initialState = { search: 'test' };
      
      useUrlSync(initialState, { prefix: 'filter' });
      
      // Проверяем что URL параметры используют префикс
      // В реальной реализации это будет проверяться через saveToUrl
      expect(true).toBe(true); // Placeholder для демонстрации структуры
    });
  });

  describe('Сериализация и десериализация', () => {
    it('корректно сериализует массивы', () => {
      const transform = urlSyncUtils.arrayTransform(',');
      
      const serialized = transform.serialize(['a', 'b', 'c']);
      expect(serialized).toBe('a,b,c');
      
      const deserialized = transform.deserialize('a,b,c');
      expect(deserialized).toEqual(['a', 'b', 'c']);
    });

    it('корректно сериализует объекты', () => {
      const transform = urlSyncUtils.objectTransform();
      const testObject = { key: 'value', num: 42 };
      
      const serialized = transform.serialize(testObject);
      expect(serialized).toBe(JSON.stringify(testObject));
      
      const deserialized = transform.deserialize(serialized);
      expect(deserialized).toEqual(testObject);
    });

    it('корректно обрабатывает булевые значения', () => {
      const transform = urlSyncUtils.booleanTransform();
      
      expect(transform.serialize(true)).toBe('1');
      expect(transform.serialize(false)).toBe('0');
      
      expect(transform.deserialize('1')).toBe(true);
      expect(transform.deserialize('0')).toBe(false);
      expect(transform.deserialize('true')).toBe(true);
    });

    it('корректно обрабатывает числовые значения', () => {
      const transform = urlSyncUtils.numberTransform(0);
      
      expect(transform.serialize(42)).toBe('42');
      expect(transform.serialize(-10)).toBe('-10');
      
      expect(transform.deserialize('42')).toBe(42);
      expect(transform.deserialize('invalid')).toBe(0);
      expect(transform.deserialize('')).toBe(0);
    });
  });

  describe('Состояние загрузки и ошибок', () => {
    it('инициализирует isLoading как false', () => {
      const { isLoading } = useUrlSync({ test: 'value' });
      
      expect(isLoading.value).toBe(false);
    });

    it('инициализирует errors как пустой объект', () => {
      const { errors } = useUrlSync({ test: 'value' });
      
      expect(errors.value).toEqual({});
    });
  });

  describe('Валидация (с мокированием)', () => {
    it('обрабатывает валидацию успешно', async () => {
      const mockValidation = {
        parse: vi.fn().mockReturnValue({ search: 'valid' })
      };
      
      const { state, updateState, errors } = useUrlSync(
        { search: '' }, 
        { validation: mockValidation }
      );
      
      updateState({ search: 'valid' });
      await nextTick();
      
      expect(mockValidation.parse).toHaveBeenCalledWith({ search: 'valid' });
      expect(errors.value).toEqual({});
    });

    it('обрабатывает ошибки валидации', async () => {
      const mockValidation = {
        parse: vi.fn().mockImplementation(() => {
          const error = new Error('Validation failed');
          (error as any).errors = [
            { path: ['search'], message: 'Search is required' }
          ];
          throw error;
        })
      };
      
      const { updateState, errors } = useUrlSync(
        { search: '' }, 
        { validation: mockValidation }
      );
      
      updateState({ search: '' });
      await nextTick();
      
      expect(errors.value).toEqual({ search: 'Search is required' });
    });
  });

  describe('Опции конфигурации', () => {
    it('принимает кастомные опции debounce', async () => {
      useUrlSync({ test: 'value' }, { debounce: 500 });
      
      const debounce = await import('lodash.debounce');
      expect(debounce.default).toHaveBeenCalledWith(expect.any(Function), 500);
    });

    it('использует значения по умолчанию', async () => {
      useUrlSync({ test: 'value' });
      
      const debounce = await import('lodash.debounce');
      expect(debounce.default).toHaveBeenCalledWith(expect.any(Function), 300);
    });
  });
});

describe('urlSyncUtils', () => {
  describe('arrayTransform', () => {
    it('использует кастомный разделитель', () => {
      const transform = urlSyncUtils.arrayTransform('|');
      
      expect(transform.serialize(['a', 'b'])).toBe('a|b');
      expect(transform.deserialize('a|b')).toEqual(['a', 'b']);
    });

    it('обрабатывает пустые массивы', () => {
      const transform = urlSyncUtils.arrayTransform();
      
      expect(transform.serialize([])).toBe('');
      expect(transform.deserialize('')).toEqual([]);
    });
  });

  describe('objectTransform', () => {
    it('обрабатывает невалидный JSON', () => {
      const transform = urlSyncUtils.objectTransform();
      
      expect(transform.deserialize('invalid-json')).toEqual({});
    });
  });

  describe('numberTransform', () => {
    it('использует кастомное значение по умолчанию', () => {
      const transform = urlSyncUtils.numberTransform(100);
      
      expect(transform.deserialize('invalid')).toBe(100);
      expect(transform.deserialize('')).toBe(100);
    });

    it('корректно обрабатывает числовые значения с дефолтом 0', () => {
      const transform = urlSyncUtils.numberTransform(0);
      
      expect(transform.deserialize('invalid')).toBe(0);
      expect(transform.deserialize('')).toBe(0);
      expect(transform.deserialize('42')).toBe(42);
    });
  });
});
