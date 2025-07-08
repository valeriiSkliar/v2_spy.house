/**
 * Композабл для унифицированной работы с переводами
 * 
 * Обеспечивает:
 * - Защиту от race condition при загрузке переводов
 * - Единообразный API для всех компонентов
 * - Автоматическую реактивность при обновлении переводов
 * - Fallback к базовым переводам
 * 
 * Использование:
 * ```typescript
 * const { t, isReady, waitForReady } = useTranslations();
 * 
 * // Обычное использование
 * const title = t('details.title', 'Details');
 * 
 * // Reactive версия
 * const titleReactive = tReactive('details.title', 'Details');
 * 
 * // Ожидание готовности
 * await waitForReady();
 * ```
 */

import { useCreativesFiltersStore } from '@/stores/useFiltersStore';
import { computed, type ComputedRef } from 'vue';

export interface TranslationsComposable {
  /** Получить перевод с fallback */
  t: (key: string, fallback?: string) => string;
  
  /** Reactive перевод (обновляется автоматически) */
  tReactive: (key: string, fallback?: string) => ComputedRef<string>;
  
  /** Готовы ли переводы */
  isReady: ComputedRef<boolean>;
  
  /** Ожидать готовности переводов */
  waitForReady: () => Promise<void>;
  
  /** Базовые переводы (fallback) */
  defaults: Record<string, string>;
}

/**
 * Композабл для работы с переводами
 */
export function useTranslations(): TranslationsComposable {
  const store = useCreativesFiltersStore();
  
  /**
   * Получает перевод с fallback
   */
  function t(key: string, fallback?: string): string {
    return store.getTranslation(key, fallback);
  }
  
  /**
   * Reactive версия перевода
   * Автоматически обновляется при изменении переводов
   */
  function tReactive(key: string, fallback?: string): ComputedRef<string> {
    return store.useTranslation(key, fallback);
  }
  
  /**
   * Computed свойство готовности переводов
   */
  const isReady = computed(() => store.isTranslationsReady);
  
  /**
   * Ожидает готовности переводов
   */
  async function waitForReady(): Promise<void> {
    return store.waitForTranslations();
  }
  
  return {
    t,
    tReactive,
    isReady,
    waitForReady,
    defaults: store.defaultTranslations
  };
}

/**
 * Хелпер для создания reactive переводов в setup()
 * 
 * @example
 * ```typescript
 * const translations = createReactiveTranslations({
 *   title: 'details.title',
 *   addToFavorites: 'details.add-to-favorites',
 *   removeFromFavorites: 'details.remove-from-favorites'
 * });
 * 
 * // В template:
 * {{ translations.title.value }}
 * ```
 */
export function createReactiveTranslations<T extends Record<string, string>>(
  keys: T,
  fallbacks?: Partial<Record<keyof T, string>>
): Record<keyof T, ComputedRef<string>> {
  const { tReactive } = useTranslations();
  
  const result = {} as Record<keyof T, ComputedRef<string>>;
  
  Object.entries(keys).forEach(([localKey, translationKey]) => {
    const fallback = fallbacks?.[localKey as keyof T];
    result[localKey as keyof T] = tReactive(translationKey, fallback);
  });
  
  return result;
}

/**
 * Хелпер для массовой установки переводов из props
 * Используется в компонентах которые получают переводы через props
 * 
 * @param propsTranslations - переводы из props
 * @param setStoreTranslations - функция установки переводов в store
 */
export function mergePropsTranslations(
  propsTranslations: Record<string, string> = {},
  setStoreTranslations: (translations: Record<string, string>) => void
): void {
  if (Object.keys(propsTranslations).length > 0) {
    setStoreTranslations(propsTranslations);
  }
} 