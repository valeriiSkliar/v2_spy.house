/**
 * Тесты для Vue компонента FavoritesCounter
 *
 * Проверяет:
 * - Корректное отображение начального значения
 * - Обработку кликов по счетчику
 * - Анимации и состояния загрузки
 * - Интеграцию с Store
 */

import { useCreativesFiltersStore } from '@/stores/useFiltersStore';
import FavoritesCounter from '@/vue-components/ui/FavoritesCounter.vue';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

describe('FavoritesCounter', () => {
  let wrapper;
  let store;
  let pinia;

  beforeEach(() => {
    // Создаем новый Pinia instance для каждого теста
    pinia = createPinia();
    setActivePinia(pinia);

    // Инициализируем store
    store = useCreativesFiltersStore();

    // Мокируем window.axios
    if (!window.axios) {
      window.axios = {
        get: vi.fn(),
        post: vi.fn(),
        delete: vi.fn(),
      };
    }

    // Сбрасываем моки
    vi.clearAllMocks();

    // Мокируем document.dispatchEvent
    vi.spyOn(document, 'dispatchEvent').mockImplementation(() => true);
  });

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount();
    }
  });

  it('отображает начальное значение счетчика', () => {
    // Устанавливаем значение в Store (компонент берет данные из Store)
    store.setFavoritesCount(42);

    wrapper = mount(FavoritesCounter, {
      global: {
        plugins: [pinia],
      },
      props: {
        initialCount: 42,
      },
    });

    expect(wrapper.find('.favorites-counter__value').text()).toBe('42');
  });

  it('показывает правильный tooltip', () => {
    const translations = {
      favoritesCountTooltip: 'Тестовый tooltip: 25',
    };

    // Устанавливаем значение в Store
    store.setFavoritesCount(25);

    wrapper = mount(FavoritesCounter, {
      global: {
        plugins: [pinia],
      },
      props: {
        initialCount: 25,
        translations,
      },
    });

    expect(wrapper.attributes('title')).toBe('Тестовый tooltip: 25');
  });

  it('обрабатывает клик по счетчику', async () => {
    // Мокируем API ответ
    window.axios.get.mockResolvedValue({
      data: {
        data: {
          count: 55,
        },
      },
    });

    wrapper = mount(FavoritesCounter, {
      global: {
        plugins: [pinia],
      },
      props: {
        initialCount: 30,
      },
    });

    // Кликаем по счетчику
    await wrapper.find('.favorites-counter').trigger('click');

    // Проверяем что API был вызван
    expect(window.axios.get).toHaveBeenCalledWith('/api/creatives/favorites/count');

    // Ждем обновления компонента
    await wrapper.vm.$nextTick();

    // Проверяем что событие было эмитировано
    expect(wrapper.emitted('counter-clicked')).toBeTruthy();
    expect(wrapper.emitted('counter-clicked')[0][0]).toMatchObject({
      currentCount: expect.any(Number),
      timestamp: expect.any(String),
    });
  });

  it('показывает состояние загрузки', async () => {
    // Устанавливаем начальное значение в Store
    store.setFavoritesCount(30);

    // Мокируем медленный API ответ
    let resolvePromise;
    const apiPromise = new Promise(resolve => {
      resolvePromise = resolve;
    });

    window.axios.get.mockReturnValue(apiPromise);

    wrapper = mount(FavoritesCounter, {
      global: {
        plugins: [pinia],
      },
      props: {
        initialCount: 30,
        showLoader: true,
      },
    });

    // Устанавливаем состояние загрузки в Store (новая логика)
    store.isFavoritesLoading = true;
    await wrapper.vm.$nextTick();

    // Проверяем состояние загрузки
    expect(wrapper.find('.favorites-counter--loading').exists()).toBe(true);
    expect(wrapper.vm.isLoading).toBe(true);

    // Сбрасываем состояние загрузки в Store
    store.isFavoritesLoading = false;
    await wrapper.vm.$nextTick();

    // Loader должен исчезнуть
    expect(wrapper.vm.isLoading).toBe(false);
    expect(wrapper.find('.favorites-counter--loading').exists()).toBe(false);
  });

  it('обрабатывает ошибки API', async () => {
    // Мокируем ошибку API
    const apiError = new Error('Network error');
    window.axios.get.mockRejectedValue(apiError);

    wrapper = mount(FavoritesCounter, {
      global: {
        plugins: [pinia],
      },
      props: {
        initialCount: 30,
      },
    });

    // Кликаем по счетчику и ждем обработки ошибки
    await wrapper.find('.favorites-counter').trigger('click');

    // Даем время на обработку Promise rejection
    await new Promise(resolve => setTimeout(resolve, 0));
    await wrapper.vm.$nextTick();

    // Проверяем что событие ошибки было эмитировано
    expect(wrapper.emitted('counter-error')).toBeTruthy();
    expect(wrapper.emitted('counter-error')[0][0]).toMatchObject({
      error: expect.any(String),
      timestamp: expect.any(String),
    });
  });

  it('синхронизируется с Store', async () => {
    // Устанавливаем начальное значение в Store
    store.setFavoritesCount(30);

    wrapper = mount(FavoritesCounter, {
      global: {
        plugins: [pinia],
      },
      props: {
        initialCount: 30,
      },
    });

    // Проверяем начальное значение
    expect(wrapper.find('.favorites-counter__value').text()).toBe('30');

    // Устанавливаем новое значение в Store
    store.setFavoritesCount(75);
    await wrapper.vm.$nextTick();

    // Компонент должен отобразить новое значение из Store
    expect(wrapper.find('.favorites-counter__value').text()).toBe('75');
  });

  it('поддерживает отключение анимации', () => {
    wrapper = mount(FavoritesCounter, {
      global: {
        plugins: [pinia],
      },
      props: {
        initialCount: 30,
        enableAnimation: false,
      },
    });

    // При отключенной анимации класс animated не должен появляться
    expect(wrapper.find('.favorites-counter--animated').exists()).toBe(false);
  });

  it('форматирует большие числа', async () => {
    // Устанавливаем значение в Store
    store.setFavoritesCount(1500);

    wrapper = mount(FavoritesCounter, {
      global: {
        plugins: [pinia],
      },
      props: {
        initialCount: 1500,
      },
    });

    // Проверяем что компонент корректно форматирует большие числа
    expect(wrapper.find('.favorites-counter__value').text()).toBe('1.5k');

    // Дополнительно проверяем другие форматы
    store.setFavoritesCount(999);
    await wrapper.vm.$nextTick();
    expect(wrapper.find('.favorites-counter__value').text()).toBe('999');

    store.setFavoritesCount(1000000);
    await wrapper.vm.$nextTick();
    expect(wrapper.find('.favorites-counter__value').text()).toBe('1.0m');
  });
});
