<template>
  <div
    v-if="!isCreativesLoading"
    class="creative-item"
    :class="{
      'creative-item--loading': isCreativesLoading,
      'creative-item--disabled': isAnyLoading,
    }"
  >
    <div class="creative-item__head">
      <div class="creative-item__txt">
        <div class="creative-item__active" :class="{ 'icon-dot': isActive }">
          {{ getActiveText() }}
        </div>
        <div class="text-with-copy">
          <div class="text-with-copy__btn">
            <!-- Заглушка для copy-button компонента -->
            <button
              class="btn copy-btn _flex _dark"
              type="button"
              @click="handleCopyTitle"
              :disabled="isCreativesLoading"
            >
              <span class="icon-copy">{{ translations.copyButton.value }}</span>
            </button>
          </div>
          <div class="creative-item__title">
            {{ creative.title }}
          </div>
        </div>
        <div class="text-with-copy">
          <div class="text-with-copy__btn">
            <!-- Заглушка для copy-button компонента -->
            <button
              class="btn copy-btn _flex _dark"
              type="button"
              @click="handleCopyDescription"
              :disabled="isCreativesLoading"
            >
              <span class="icon-copy">{{ translations.copyButton.value }}</span>
            </button>
          </div>
          <div class="creative-item__desc">
            {{ creative.description }}
          </div>
        </div>
      </div>
      <div class="creative-item__icon thumb thumb-with-controls-small">
        <img :src="getIconUrl()" alt="" />
        <div class="thumb-controls">
          <a
            href="#"
            class="btn-icon _black"
            @click.prevent="handleDownload"
            :class="{ disabled: isCreativesLoading }"
          >
            <span class="icon-download2 remore_margin"></span>
          </a>
        </div>
      </div>
    </div>
    <div class="creative-item__image thumb thumb-with-controls">
      <img :src="getImageUrl()" alt="" />
      <div class="thumb-controls">
        <a
          href="#"
          class="btn-icon _black"
          @click.prevent="handleDownload"
          :class="{ disabled: isCreativesLoading }"
        >
          <span class="icon-download2 remore_margin"></span>
        </a>
        <a
          href="#"
          class="btn-icon _black"
          @click.prevent="handleOpenInNewTab"
          :class="{ disabled: isCreativesLoading }"
        >
          <span class="icon-new-tab remore_margin"></span>
        </a>
      </div>
    </div>
    <div class="creative-item__footer">
      <div class="creative-item__info">
        <div v-if="getNetworkName" class="creative-item-info">
          <span class="creative-item-info__txt">{{ getNetworkName }}</span>
        </div>
        <div class="creative-item-info">
          <img :src="getFlagIcon()" alt="" />{{ creative.country || 'KZ' }}
        </div>
        <div class="creative-item-info">
          <div :class="getDeviceIconClass()"></div>
          {{ getDeviceText() }}
        </div>
      </div>
      <div class="creative-item__btns">
        <button
          class="btn-icon btn-favorite"
          :class="{
            active: isFavorite,
            loading: props.isFavoriteLoading,
          }"
          @click="handleFavoriteClick"
          :disabled="isAnyLoading"
        >
          <span :class="getFavoriteIconClass() + ' remore_margin'"></span>
        </button>
        <button
          class="btn-icon _dark js-show-details"
          @click="handleShowDetails"
          :disabled="isCreativesLoading"
        >
          <span class="icon-info remore_margin"></span>
        </button>
      </div>
    </div>
  </div>
  <div v-else class="similar-creatives">
    <div class="similar-creative-empty _push">
      <img :src="empty" alt="empty" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { useCreativesFiltersStore } from '@/stores/useFiltersStore';
import type { Creative } from '@/types/creatives.d';
import empty from '@img/empty.svg';
import { computed, onMounted } from 'vue';

// Импорты новой системы переводов
import {
  createReactiveTranslations,
  mergePropsTranslations,
  useTranslations,
} from '@/composables/useTranslations';

const store = useCreativesFiltersStore();

// Новая система переводов
const { waitForReady } = useTranslations();

// Создание reactive переводов для карточки
const translations = createReactiveTranslations(
  {
    copyButton: 'copyButton',
  },
  {
    copyButton: 'Copy',
  }
);

const props = defineProps<{
  creative: Creative;
  isFavorite?: boolean;
  isFavoriteLoading?: boolean;
  translations?: Record<string, string>;
}>();

const emit = defineEmits<{
  'toggle-favorite': [creativeId: number, isFavorite: boolean];
  download: [creative: Creative];
  'show-details': [creative: Creative];
  'open-in-new-tab': [creative: Creative];
}>();

// Защита от race condition при инициализации
onMounted(async () => {
  // Мержим переводы из props с Store для обратной совместимости
  mergePropsTranslations(props.translations, store.setTranslations);

  // Ждем готовности переводов
  await waitForReady();
});

// Computed для определения активности (заглушка)
const isActive = computed((): boolean => {
  // Логика будет добавлена позже
  return true;
});

// Computed для избранного
const isFavorite = computed((): boolean => {
  return props.isFavorite ?? props.creative.isFavorite ?? false;
});

// Computed для глобального состояния загрузки креативов
const isCreativesLoading = computed((): boolean => {
  return store.isLoading;
});

// Computed для объединенного состояния загрузки (блокирует все операции)
const isAnyLoading = computed((): boolean => {
  return isCreativesLoading.value || props.isFavoriteLoading || false;
});

// Обработчики событий
const handleFavoriteClick = (): void => {
  // Блокируем повторные клики если уже идет обработка для этого креатива или загружается список
  if (isAnyLoading.value) {
    console.warn(
      `Операция с избранным для креатива ${props.creative.id} заблокирована: идет загрузка`
    );
    return;
  }

  emit('toggle-favorite', props.creative.id, isFavorite.value);

  // Эмитируем DOM событие для Store
  document.dispatchEvent(
    new CustomEvent('creatives:toggle-favorite', {
      detail: {
        creativeId: props.creative.id,
        isFavorite: isFavorite.value,
      },
    })
  );
};

const handleDownload = (): void => {
  // Блокируем скачивание во время загрузки списка
  if (isCreativesLoading.value) {
    console.warn(`Скачивание креатива ${props.creative.id} заблокировано: идет загрузка списка`);
    return;
  }

  emit('download', props.creative);

  // Эмитируем DOM событие для Store
  document.dispatchEvent(
    new CustomEvent('creatives:download', {
      detail: {
        creative: props.creative,
      },
    })
  );
};

const handleShowDetails = (): void => {
  // Блокируем просмотр деталей во время загрузки списка
  if (isCreativesLoading.value) {
    console.warn(
      `Просмотр деталей креатива ${props.creative.id} заблокирован: идет загрузка списка`
    );
    return;
  }

  emit('show-details', props.creative);

  // Эмитируем DOM событие для Store
  document.dispatchEvent(
    new CustomEvent('creatives:show-details', {
      detail: {
        creative: props.creative,
      },
    })
  );
};

const handleOpenInNewTab = (): void => {
  // Блокируем открытие в новой вкладке во время загрузки списка
  if (isCreativesLoading.value) {
    console.warn(
      `Открытие креатива ${props.creative.id} в новой вкладке заблокировано: идет загрузка списка`
    );
    return;
  }

  emit('open-in-new-tab', props.creative);

  // Эмитируем DOM событие для возможной обработки в Store
  document.dispatchEvent(
    new CustomEvent('creatives:open-in-new-tab', {
      detail: {
        creative: props.creative,
      },
    })
  );

  // Базовая реализация - открытие файла/превью в новой вкладке
  const url = props.creative.main_image_url;
  if (url) {
    window.open(url, '_blank');
  }
};

// Функция для обработки клика по кнопке копирования названия
const handleCopyTitle = async (): Promise<void> => {
  // Блокируем копирование во время загрузки списка
  if (isCreativesLoading.value) {
    console.warn(
      `Копирование названия креатива ${props.creative.id} заблокировано: идет загрузка списка`
    );
    return;
  }

  const title = props.creative.title;

  try {
    await navigator.clipboard.writeText(title);

    // Эмитируем событие успешного копирования
    document.dispatchEvent(
      new CustomEvent('creatives:copy-success', {
        detail: {
          text: title,
          type: 'title',
          creativeId: props.creative.id,
        },
      })
    );
  } catch (error) {
    console.error('Ошибка копирования названия:', error);

    // Fallback для старых браузеров
    const textarea = document.createElement('textarea');
    textarea.value = title;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);

    document.dispatchEvent(
      new CustomEvent('creatives:copy-success', {
        detail: {
          text: title,
          type: 'title',
          creativeId: props.creative.id,
          fallback: true,
        },
      })
    );
  }
};

// Функция для обработки клика по кнопке копирования описания
const handleCopyDescription = async (): Promise<void> => {
  // Блокируем копирование во время загрузки списка
  if (isCreativesLoading.value) {
    console.warn(
      `Копирование описания креатива ${props.creative.id} заблокировано: идет загрузка списка`
    );
    return;
  }

  const description = props.creative.description;

  try {
    await navigator.clipboard.writeText(description);

    // Эмитируем событие успешного копирования
    document.dispatchEvent(
      new CustomEvent('creatives:copy-success', {
        detail: {
          text: description,
          type: 'description',
          creativeId: props.creative.id,
        },
      })
    );
  } catch (error) {
    console.error('Ошибка копирования описания:', error);

    // Fallback для старых браузеров
    const textarea = document.createElement('textarea');
    textarea.value = description;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);

    document.dispatchEvent(
      new CustomEvent('creatives:copy-success', {
        detail: {
          text: description,
          type: 'description',
          creativeId: props.creative.id,
          fallback: true,
        },
      })
    );
  }
};

// Функция для формирования текста активности
import { getActiveDaysText } from '@/utils/getActiveDaysText';

const getActiveText = (): string => {
  return getActiveDaysText(props.creative.activity_date, 'Active 3 days');
};

// Функция для получения URL иконки
const getIconUrl = (): string => {
  return props.creative.icon_url;
};

// Функция для получения URL изображения
const getImageUrl = (): string => {
  return props.creative.main_image_url || 'https://picsum.photos/300/200';
};

// Функция для получения текста сети
const getNetworkText = (): string => {
  // console.log('PROPS.CREATIVE.ADVERTISING_NETWORKS', props.creative.advertising_networks);
  if (props.creative.advertising_networks && props.creative.advertising_networks.length > 0) {
    return props.creative.advertising_networks[0];
  }
  return '';
};

const getNetworkName: string = getNetworkText();

// Функция для получения иконки флага
const getFlagIcon = (): string => {
  return `/img/flags/${props.creative.country || 'KZ'}.svg`;
};

// Функция для получения CSS класса иконки устройства
const getDeviceIconClass = (): string => {
  if (props.creative.devices && props.creative.devices.length > 0) {
    const device = props.creative.devices[0].toLowerCase();
    if (device.includes('mobile') || device.includes('android') || device.includes('ios')) {
      return 'icon-mobile';
    }
    if (device.includes('tablet')) {
      return 'icon-tablet';
    }
  }
  return 'icon-pc';
};

// Функция для получения текста устройства
const getDeviceText = (): string => {
  if (props.creative.devices && props.creative.devices.length > 0) {
    const device = props.creative.devices[0];
    if (
      device.toLowerCase().includes('mobile') ||
      device.toLowerCase().includes('android') ||
      device.toLowerCase().includes('ios')
    ) {
      return 'Mobile';
    }
    if (device.toLowerCase().includes('tablet')) {
      return 'Tablet';
    }
    return device;
  }
  return 'PC';
};

// Функция для получения CSS класса иконки избранного
const getFavoriteIconClass = (): string => {
  return isFavorite.value ? 'icon-favorite' : 'icon-favorite-empty';
};
</script>
