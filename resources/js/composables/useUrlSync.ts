import { useDebounceFn, useUrlSearchParams } from '@vueuse/core';
import { computed, onUnmounted, ref, watch, type Ref } from 'vue';

// Опциональный тип для Zod схемы (может быть не установлен)
type ZodSchema<T> = {
  parse: (data: unknown) => T;
};

/**
 * Опции для настройки URL синхронизации
 */
export interface UseUrlSyncOptions<T> {
  /** Задержка для debounce в миллисекундах (по умолчанию 300ms) */
  debounce?: number;
  /** Префикс для namespace параметров в URL */
  prefix?: string;
  /** Функции трансформации для сериализации/десериализации */
  transform?: {
    serialize?: (value: any) => string;
    deserialize?: (value: string) => any;
  };
  /** Zod схема для валидации параметров */
  validation?: ZodSchema<T>;
  /** Включить управление историей браузера */
  history?: boolean;
}

/**
 * Результат композабла useUrlSync
 */
export interface UseUrlSyncReturn<T> {
  /** Реактивное состояние (только чтение) */
  state: Readonly<Ref<T>>;
  /** Метод для обновления состояния */
  updateState: (updates: Partial<T>) => void;
  /** Метод для сброса состояния к начальному */
  resetState: () => void;
  /** Индикатор загрузки */
  isLoading: Ref<boolean>;
  /** Ошибки валидации */
  errors: Ref<Record<string, string>>;
  /** Прямой доступ к URL параметрам */
  urlParams: ReturnType<typeof useUrlSearchParams>;
}

/**
 * Базовый композабл для синхронизации состояния с URL параметрами
 * 
 * @param initialState - Начальное состояние
 * @param options - Опции настройки
 */
export function useUrlSync<T extends Record<string, any>>(
  initialState: T,
  options: UseUrlSyncOptions<T> = {}
): UseUrlSyncReturn<T> {
  const {
    debounce = 300,
    prefix = '',
    transform = {},
    validation,
    history = true
  } = options;

  // Реактивные данные
  const state = ref({ ...initialState }) as Ref<T>;
  const isLoading = ref(false);
  const errors = ref<Record<string, string>>({});

  // URL параметры с @vueuse/core - используем 'history' mode
  const urlParams = useUrlSearchParams('history', {
    removeFalsyValues: true,
    removeNullishValues: true,
  });

  /**
   * Создает ключ с префиксом
   */
  const createKey = (key: string): string => {
    return prefix ? `${prefix}_${key}` : key;
  };

  /**
   * Парсит ключ, убирая префикс
   */
  const parseKey = (key: string): string => {
    return prefix && key.startsWith(`${prefix}_`) 
      ? key.substring(prefix.length + 1) 
      : key;
  };

  /**
   * Сериализует значение для URL
   */
  const serializeValue = (value: any): string => {
    if (transform.serialize) {
      return transform.serialize(value);
    }

    if (Array.isArray(value)) {
      return value.join(',');
    }

    if (typeof value === 'boolean') {
      return value ? '1' : '0';
    }

    if (value === null || value === undefined) {
      return '';
    }

    return String(value);
  };

  /**
   * Десериализует значение из URL
   */
  const deserializeValue = (value: string, originalValue: any): any => {
    if (transform.deserialize) {
      return transform.deserialize(value);
    }

    if (Array.isArray(originalValue)) {
      return value ? value.split(',').filter(Boolean) : [];
    }

    if (typeof originalValue === 'boolean') {
      return value === '1' || value === 'true';
    }

    if (typeof originalValue === 'number') {
      const parsed = Number(value);
      return isNaN(parsed) ? originalValue : parsed;
    }

    return value || originalValue;
  };

  /**
   * Валидирует состояние с помощью Zod схемы
   */
  const validateState = (stateToValidate: T): boolean => {
    if (!validation) return true;

    try {
      validation.parse(stateToValidate);
      errors.value = {};
      return true;
    } catch (error: any) {
      const validationErrors: Record<string, string> = {};
      
      if (error.errors) {
        error.errors.forEach((err: any) => {
          const path = err.path.join('.');
          validationErrors[path] = err.message;
        });
      }
      
      errors.value = validationErrors;
      return false;
    }
  };

  /**
   * Загружает состояние из URL параметров
   */
  const loadFromUrl = (): void => {
    isLoading.value = true;
    
    try {
      const newState = { ...state.value };
      let hasChanges = false;

      // Проходим по всем ключам начального состояния
      Object.keys(initialState).forEach((key) => {
        const urlKey = createKey(key);
        const urlValue = urlParams[urlKey];
        
        if (urlValue !== undefined && urlValue !== null) {
          // Приводим к строке если это массив
          const stringValue = Array.isArray(urlValue) ? urlValue[0] : String(urlValue);
          const deserializedValue = deserializeValue(stringValue, initialState[key]);
          
          if (JSON.stringify(newState[key]) !== JSON.stringify(deserializedValue)) {
            (newState as any)[key] = deserializedValue;
            hasChanges = true;
          }
        }
      });

      if (hasChanges && validateState(newState)) {
        state.value = newState;
      }
    } catch (error) {
      console.error('Ошибка загрузки состояния из URL:', error);
      errors.value.general = 'Ошибка загрузки параметров из URL';
    } finally {
      isLoading.value = false;
    }
  };

  /**
   * Сохраняет состояние в URL параметры (с debounce)
   */
  const saveToUrl = useDebounceFn((): void => {
    if (!validateState(state.value)) {
      return; // Не сохраняем невалидное состояние
    }

    try {
      // Очищаем старые параметры с нашим префиксом
      Object.keys(urlParams).forEach((key) => {
        if (!prefix || key.startsWith(`${prefix}_`)) {
          const originalKey = parseKey(key);
          if (originalKey in initialState) {
            delete urlParams[key];
          }
        }
      });

      // Добавляем новые параметры
      Object.entries(state.value).forEach(([key, value]) => {
        const urlKey = createKey(key);
        const serializedValue = serializeValue(value);
        
        // Добавляем в URL только значимые параметры
        if (serializedValue && serializedValue !== serializeValue(initialState[key])) {
          urlParams[urlKey] = serializedValue;
        }
      });
    } catch (error) {
      console.error('Ошибка сохранения состояния в URL:', error);
      errors.value.general = 'Ошибка обновления URL';
    }
  }, debounce);

  /**
   * Обновляет состояние
   */
  const updateState = (updates: Partial<T>): void => {
    const newState = { ...state.value, ...updates };
    
    if (validateState(newState)) {
      state.value = newState;
    }
  };

  /**
   * Сбрасывает состояние к начальному
   */
  const resetState = (): void => {
    state.value = { ...initialState };
    errors.value = {};
  };

  // Наблюдатель за изменениями состояния
  const stateWatcher = watch(
    state,
    () => {
      if (history) {
        saveToUrl();
      }
    },
    { deep: true }
  );

  // Наблюдатель за изменениями URL (для внешних изменений)
  const urlWatcher = watch(
    urlParams,
    () => {
      loadFromUrl();
    },
    { deep: true }
  );

  // Инициализация: загружаем состояние из URL при создании
  loadFromUrl();

  // Очистка при размонтировании
  onUnmounted(() => {
    stateWatcher();
    urlWatcher();
  });

  return {
    state: computed(() => state.value),
    updateState,
    resetState,
    isLoading,
    errors,
    urlParams,
  };
}

/**
 * Утилитарные функции для работы с URL
 */
export const urlSyncUtils = {
  /**
   * Создает transform функции для массивов
   */
  arrayTransform: (separator = ',') => ({
    serialize: (value: string[]) => value.join(separator),
    deserialize: (value: string) => value ? value.split(separator).filter(Boolean) : [],
  }),

  /**
   * Создает transform функции для объектов (JSON)
   */
  objectTransform: () => ({
    serialize: (value: any) => JSON.stringify(value),
    deserialize: (value: string) => {
      try {
        return JSON.parse(value);
      } catch {
        return {};
      }
    },
  }),

  /**
   * Создает transform функции для чисел
   */
  numberTransform: (defaultValue = 0) => ({
    serialize: (value: number) => String(value),
    deserialize: (value: string) => {
      const parsed = Number(value);
      return isNaN(parsed) ? defaultValue : parsed;
    },
  }),

  /**
   * Создает transform функции для булевых значений
   */
  booleanTransform: () => ({
    serialize: (value: boolean) => value ? '1' : '0',
    deserialize: (value: string) => value === '1' || value === 'true',
  }),
};

/**
 * Конфигурация по умолчанию для URL синхронизации
 */
export const DEFAULT_URL_SYNC_CONFIG = {
  debounce: 300,
  prefixes: {
    creatives: 'cr',
    filter: 'f',
    pagination: 'p',
    search: 's',
  },
} as const; 