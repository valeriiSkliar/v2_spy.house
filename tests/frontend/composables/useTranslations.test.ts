/**
 * Тесты для композабла useTranslations
 * Проверяет унифицированную систему переводов с защитой от race condition
 */

import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import { createReactiveTranslations, mergePropsTranslations, useTranslations } from '../../../resources/js/composables/useTranslations';
import { useCreativesFiltersStore } from '../../../resources/js/stores/useFiltersStore';

// Мокируем композаблы
vi.mock('@/composables/useCreatives');
vi.mock('@/composables/useCreativesUrlSync');
vi.mock('@/composables/useFiltersSynchronization');

describe('useTranslations composable', () => {
  let store: ReturnType<typeof useCreativesFiltersStore>;
  
  beforeEach(() => {
    const pinia = createPinia();
    setActivePinia(pinia);
    store = useCreativesFiltersStore();
    vi.clearAllMocks();
  });

  describe('Базовая функциональность', () => {
    it('возвращает корректный интерфейс', () => {
      const { t, tReactive, isReady, waitForReady, defaults } = useTranslations();
      
      expect(typeof t).toBe('function');
      expect(typeof tReactive).toBe('function');
      expect(isReady).toBeDefined();
      expect(typeof waitForReady).toBe('function');
      expect(typeof defaults).toBe('object');
    });

    it('t() возвращает перевод из store', () => {
      store.setTranslations({
        'test.key': 'Тестовое значение',
        'another.key': 'Другое значение'
      });
      
      const { t } = useTranslations();
      
      expect(t('test.key')).toBe('Тестовое значение');
      expect(t('another.key')).toBe('Другое значение');
    });

    it('t() возвращает fallback для несуществующих ключей', () => {
      const { t } = useTranslations();
      
      expect(t('nonexistent.key', 'Fallback')).toBe('Fallback');
      expect(t('nonexistent.key')).toBe('nonexistent.key');
    });

    it('t() использует базовые переводы как fallback', () => {
      const { t } = useTranslations();
      
      // Используем ключ из defaultTranslations
      expect(t('copyButton')).toBe('Copy');
      expect(t('details.title')).toBe('Details');
    });
  });

  describe('Reactive переводы', () => {
    it('tReactive() возвращает computed ref', async () => {
      const { tReactive } = useTranslations();
      
      const titleRef = tReactive('test.title', 'Default Title');
      
      // Изначально должен возвращать fallback
      expect(titleRef.value).toBe('Default Title');
      
      // Устанавливаем переводы
      store.setTranslations({
        'test.title': 'Новый заголовок'
      });
      
      await nextTick();
      
      // Теперь должен вернуть актуальный перевод
      expect(titleRef.value).toBe('Новый заголовок');
    });

    it('tReactive() обновляется при изменении переводов', async () => {
      const { tReactive } = useTranslations();
      
      // Устанавливаем начальные переводы
      store.setTranslations({
        'dynamic.key': 'Начальное значение'
      });
      
      const dynamicRef = tReactive('dynamic.key');
      expect(dynamicRef.value).toBe('Начальное значение');
      
      // Обновляем переводы
      store.setTranslations({
        'dynamic.key': 'Обновленное значение'
      });
      
      await nextTick();
      
      expect(dynamicRef.value).toBe('Обновленное значение');
    });
  });

  describe('Защита от race condition', () => {
    it('isReady отслеживает готовность переводов', () => {
      const { isReady } = useTranslations();
      
      // Изначально переводы не готовы
      expect(isReady.value).toBe(false);
      
      // После установки переводов - готовы
      store.setTranslations({
        'test.key': 'value'
      });
      
      expect(isReady.value).toBe(true);
    });

    it('waitForReady() ожидает готовности переводов', async () => {
      const { waitForReady, isReady } = useTranslations();
      
      expect(isReady.value).toBe(false);
      
      // Запускаем ожидание
      const waitPromise = waitForReady();
      
      // В другом потоке устанавливаем переводы
      setTimeout(() => {
        store.setTranslations({
          'test.key': 'value'
        });
      }, 10);
      
      // Ожидаем готовности
      await waitPromise;
      
      expect(isReady.value).toBe(true);
    });

    it('waitForReady() сразу резолвится если переводы уже готовы', async () => {
      // Устанавливаем переводы сначала
      store.setTranslations({
        'test.key': 'value'
      });
      
      const { waitForReady, isReady } = useTranslations();
      
      expect(isReady.value).toBe(true);
      
      // waitForReady должен сразу резолвиться
      await expect(waitForReady()).resolves.toBeUndefined();
    });

    it('getTranslation возвращает fallback до готовности переводов', () => {
      const { t, isReady } = useTranslations();
      
      expect(isReady.value).toBe(false);
      
      // Должен вернуть fallback из defaultTranslations
      expect(t('details.title')).toBe('Details');
      expect(t('copyButton')).toBe('Copy');
      expect(t('unknown.key', 'Custom fallback')).toBe('Custom fallback');
    });
  });

  describe('createReactiveTranslations helper', () => {
    it('создает объект с reactive переводами', async () => {
      const translations = createReactiveTranslations({
        title: 'details.title',
        copy: 'details.copy',
        custom: 'custom.key'
      }, {
        custom: 'Custom Fallback'
      });
      
      // Проверяем структуру
      expect(translations.title).toBeDefined();
      expect(translations.copy).toBeDefined();
      expect(translations.custom).toBeDefined();
      
      // Проверяем fallback значения
      expect(translations.title.value).toBe('Details'); // из defaultTranslations
      expect(translations.copy.value).toBe('Copy'); // из defaultTranslations
      expect(translations.custom.value).toBe('Custom Fallback'); // из переданного fallback
      
      // Устанавливаем переводы
      store.setTranslations({
        'details.title': 'Детали',
        'details.copy': 'Копировать',
        'custom.key': 'Пользовательское значение'
      });
      
      await nextTick();
      
      // Проверяем обновленные значения
      expect(translations.title.value).toBe('Детали');
      expect(translations.copy.value).toBe('Копировать');
      expect(translations.custom.value).toBe('Пользовательское значение');
    });
  });

  describe('mergePropsTranslations helper', () => {
    it('вызывает setStoreTranslations с переводами из props', () => {
      const mockSetTranslations = vi.fn();
      const propsTranslations = {
        'prop.key1': 'Prop Value 1',
        'prop.key2': 'Prop Value 2'
      };
      
      mergePropsTranslations(propsTranslations, mockSetTranslations);
      
      expect(mockSetTranslations).toHaveBeenCalledWith(propsTranslations);
    });

    it('не вызывает setStoreTranslations для пустых props', () => {
      const mockSetTranslations = vi.fn();
      
      mergePropsTranslations({}, mockSetTranslations);
      mergePropsTranslations(undefined, mockSetTranslations);
      
      expect(mockSetTranslations).not.toHaveBeenCalled();
    });
  });

  describe('Интеграция с Store', () => {
    it('использует методы store для переводов', () => {
      const storeSpy = vi.spyOn(store, 'getTranslation');
      
      const { t } = useTranslations();
      
      t('test.key', 'fallback');
      
      expect(storeSpy).toHaveBeenCalledWith('test.key', 'fallback');
    });

    it('отслеживает isTranslationsReady из store', () => {
      const { isReady } = useTranslations();
      
      expect(isReady.value).toBe(store.isTranslationsReady);
      
      store.setTranslations({ 'test': 'value' });
      
      expect(isReady.value).toBe(store.isTranslationsReady);
    });

    it('использует waitForTranslations из store', async () => {
      // Очищаем состояние Store перед тестом
      store.isTranslationsReady = false;
      store.translations = {};
      
      const storeSpy = vi.spyOn(store, 'waitForTranslations');
      
      const { waitForReady } = useTranslations();
      
      // Запускаем ожидание в фоне
      const waitPromise = waitForReady();
      
      // Устанавливаем переводы для разблокировки ожидания
      setTimeout(() => {
        store.setTranslations({ 'test': 'value' });
      }, 10);
      
      await waitPromise;
      
      expect(storeSpy).toHaveBeenCalled();
    });
  });

  describe('Edge cases', () => {
    it('обрабатывает множественные вызовы waitForReady', async () => {
      const { waitForReady } = useTranslations();
      
      // Запускаем несколько ожиданий одновременно
      const promises = [
        waitForReady(),
        waitForReady(),
        waitForReady()
      ];
      
      // Устанавливаем переводы
      setTimeout(() => {
        store.setTranslations({ 'test': 'value' });
      }, 10);
      
      // Все промисы должны резолвиться
      await Promise.all(promises);
      
      expect(store.isTranslationsReady).toBe(true);
    });

    it('возвращает defaultTranslations через defaults', () => {
      const { defaults } = useTranslations();
      
      expect(defaults).toBe(store.defaultTranslations);
      expect(defaults['copyButton']).toBe('Copy');
      expect(defaults['details.title']).toBe('Details');
    });

    it('обрабатывает вложенные ключи в createReactiveTranslations', async () => {
      const translations = createReactiveTranslations({
        nestedTitle: 'level1.level2.title',
        deepNested: 'level1.level2.level3.value'
      });
      
      store.setTranslations({
        level1: {
          level2: {
            title: 'Вложенный заголовок',
            level3: {
              value: 'Глубоко вложенное значение'
            }
          }
        }
      } as any);
      
      await nextTick();
      
      expect(translations.nestedTitle.value).toBe('Вложенный заголовок');
      expect(translations.deepNested.value).toBe('Глубоко вложенное значение');
    });
  });
}); 