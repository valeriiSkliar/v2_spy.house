/**
 * Тесты для Vue компонента InpageCreativeCard
 *
 * Проверяет:
 * - Блокировку операций при глобальной загрузке креативов
 * - Блокировку операций при загрузке конкретного избранного
 * - Корректное отображение состояний загрузки
 * - Обработку кликов и событий
 */

import { useCreativesFiltersStore } from '@/stores/useFiltersStore';
import InpageCreativeCard from '@/vue-components/creatives/cards/InpageCreativeCard.vue';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';

// Мокаем композабл креативов
const mockCreativesComposable = {
  isLoading: ref(false),
  creatives: ref([]),
  pagination: ref({
    total: 0,
    perPage: 20,
    currentPage: 1,
    lastPage: 1,
    from: 0,
    to: 0,
  }),
  error: ref(null),
  meta: ref({
    hasSearch: false,
    activeFiltersCount: 0,
    cacheKey: '',
  }),
};

// Мокаем import композабла
vi.mock('@/composables/useCreatives', () => ({
  useCreatives: () => mockCreativesComposable,
}));

describe('InpageCreativeCard', () => {
  let wrapper;
  let store;
  let pinia;
  let mockCreative;

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

    // Сбрасываем состояние загрузки в мокированном композабле
    mockCreativesComposable.isLoading.value = false;

    // Мокируем document.dispatchEvent
    vi.spyOn(document, 'dispatchEvent').mockImplementation(() => true);

    // Создаем тестовый креатив
    mockCreative = {
      id: 123,
      title: 'Test Creative',
      description: 'Test Description',
      icon_url: 'https://example.com/icon.jpg',
      country: 'US',
      activity_date: '2024-01-01',
      advertising_networks: ['TestNetwork'],
      devices: ['Mobile'],
      isFavorite: false,
    };
  });

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount();
    }
  });

  it('показывает заглушку при глобальной загрузке креативов (через композабл)', async () => {
    // Устанавливаем состояние загрузки через мокированный композабл
    mockCreativesComposable.isLoading.value = true;

    wrapper = mount(InpageCreativeCard, {
      global: {
        plugins: [pinia],
      },
      props: {
        creative: mockCreative,
        isFavorite: false,
        isFavoriteLoading: false,
      },
    });

    await wrapper.vm.$nextTick();

    // Проверяем что показывается заглушка empty
    expect(wrapper.find('.similar-creative-empty').exists()).toBe(true);
    expect(wrapper.find('.creative-item').exists()).toBe(false);
  });

  it('блокирует операции избранного при глобальной загрузке креативов (через событие)', async () => {
    wrapper = mount(InpageCreativeCard, {
      global: {
        plugins: [pinia],
      },
      props: {
        creative: mockCreative,
        isFavorite: false,
        isFavoriteLoading: false,
      },
    });

    // Сначала проверяем что карточка отображается в нормальном состоянии
    expect(wrapper.find('.creative-item').exists()).toBe(true);

    // Устанавливаем глобальное состояние загрузки через мокированный композабл
    mockCreativesComposable.isLoading.value = true;
    await wrapper.vm.$nextTick();

    // Теперь проверяем что показывается заглушка
    expect(wrapper.find('.similar-creative-empty').exists()).toBe(true);
    expect(wrapper.find('.creative-item').exists()).toBe(false);
  });

  it('блокирует операции через состояние isAnyLoading когда нет глобальной загрузки', async () => {
    wrapper = mount(InpageCreativeCard, {
      global: {
        plugins: [pinia],
      },
      props: {
        creative: mockCreative,
        isFavorite: false,
        isFavoriteLoading: false,
      },
    });

    // Убеждаемся что нет глобальной загрузки
    mockCreativesComposable.isLoading.value = false;
    await wrapper.vm.$nextTick();

    // Проверяем что карточка отображается и кнопки активны
    expect(wrapper.find('.creative-item').exists()).toBe(true);
    const favoriteButton = wrapper.find('.btn-favorite');
    expect(favoriteButton.exists()).toBe(true);
    expect(favoriteButton.attributes('disabled')).toBeUndefined();

    // Эмулируем клик - должен работать
    await favoriteButton.trigger('click');
    expect(wrapper.emitted('toggle-favorite')).toBeTruthy();
  });

  it('блокирует операции при загрузке конкретного избранного', async () => {
    wrapper = mount(InpageCreativeCard, {
      global: {
        plugins: [pinia],
      },
      props: {
        creative: mockCreative,
        isFavorite: false,
        isFavoriteLoading: true, // Загрузка конкретного избранного
      },
    });

    await wrapper.vm.$nextTick();

    // Проверяем что кнопка избранного заблокирована
    const favoriteButton = wrapper.find('.btn-favorite');
    expect(favoriteButton.attributes('disabled')).toBeDefined();
    expect(favoriteButton.classes()).toContain('loading');
  });

  it('разрешает операции когда загрузка завершена', async () => {
    wrapper = mount(InpageCreativeCard, {
      global: {
        plugins: [pinia],
      },
      props: {
        creative: mockCreative,
        isFavorite: false,
        isFavoriteLoading: false,
      },
    });

    // Убеждаемся что глобальная загрузка отключена
    mockCreativesComposable.isLoading.value = false;
    await wrapper.vm.$nextTick();

    // Пытаемся кликнуть по кнопке избранного
    const favoriteButton = wrapper.find('.btn-favorite');
    await favoriteButton.trigger('click');

    // Проверяем что событие было эмитировано с правильными параметрами
    expect(wrapper.emitted('toggle-favorite')).toBeTruthy();
    expect(wrapper.emitted('toggle-favorite')[0][0]).toBe(mockCreative.id); // creativeId
    expect(wrapper.emitted('toggle-favorite')[0][1]).toBe(false); // isFavorite
  });

  it('показывает состояние empty при глобальной загрузке', async () => {
    // Устанавливаем глобальное состояние загрузки через мокированный композабл ДО создания компонента
    mockCreativesComposable.isLoading.value = true;

    wrapper = mount(InpageCreativeCard, {
      global: {
        plugins: [pinia],
      },
      props: {
        creative: mockCreative,
        isFavorite: false,
        isFavoriteLoading: false,
      },
    });

    await wrapper.vm.$nextTick();

    // Проверяем что показывается заглушка empty
    expect(wrapper.find('.similar-creative-empty').exists()).toBe(true);
    expect(wrapper.find('.creative-item').exists()).toBe(false);
  });

  it('показывает нормальную карточку когда загрузка завершена', async () => {
    wrapper = mount(InpageCreativeCard, {
      global: {
        plugins: [pinia],
      },
      props: {
        creative: mockCreative,
        isFavorite: false,
        isFavoriteLoading: false,
      },
    });

    // Убеждаемся что глобальная загрузка отключена через мокированный композабл
    mockCreativesComposable.isLoading.value = false;
    await wrapper.vm.$nextTick();

    // Проверяем что показывается нормальная карточка
    expect(wrapper.find('.creative-item').exists()).toBe(true);
    expect(wrapper.find('.similar-creative-empty').exists()).toBe(false);
  });

  it('комбинированная блокировка: переход от карточки к заглушке при загрузке', async () => {
    wrapper = mount(InpageCreativeCard, {
      global: {
        plugins: [pinia],
      },
      props: {
        creative: mockCreative,
        isFavorite: false,
        isFavoriteLoading: true,
      },
    });

    // Начально нет глобальной загрузки, показывается карточка с заблокированной кнопкой избранного
    mockCreativesComposable.isLoading.value = false;
    await wrapper.vm.$nextTick();

    expect(wrapper.find('.creative-item').exists()).toBe(true);
    const favoriteButton = wrapper.find('.btn-favorite');
    expect(favoriteButton.attributes('disabled')).toBeDefined();
    expect(favoriteButton.classes()).toContain('loading');

    // Включаем глобальную загрузку - должна показаться заглушка
    mockCreativesComposable.isLoading.value = true;
    await wrapper.vm.$nextTick();

    expect(wrapper.find('.similar-creative-empty').exists()).toBe(true);
    expect(wrapper.find('.creative-item').exists()).toBe(false);

    // Убираем глобальную загрузку, но оставляем загрузку избранного
    mockCreativesComposable.isLoading.value = false;
    await wrapper.vm.$nextTick();

    // Должна вернуться карточка с заблокированной кнопкой избранного
    expect(wrapper.find('.creative-item').exists()).toBe(true);
    const newFavoriteButton = wrapper.find('.btn-favorite');
    expect(newFavoriteButton.attributes('disabled')).toBeDefined();
    expect(newFavoriteButton.classes()).toContain('loading');
  });

  it('проверяет computed isCreativesLoading из store', async () => {
    wrapper = mount(InpageCreativeCard, {
      global: {
        plugins: [pinia],
      },
      props: {
        creative: mockCreative,
        isFavorite: false,
        isFavoriteLoading: false,
      },
    });

    // Проверяем начальное состояние
    expect(wrapper.vm.isCreativesLoading).toBe(false);

    // Изменяем состояние в мокированном композабле
    mockCreativesComposable.isLoading.value = true;
    await wrapper.vm.$nextTick();

    // Проверяем что computed реагирует на изменения
    expect(wrapper.vm.isCreativesLoading).toBe(true);
  });
});
