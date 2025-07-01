<template>
  <div class="creative-item">
    <div class="creative-item__head">
      <div class="creative-item__icon thumb thumb-with-controls-small mr-2">
        <img :src="creative.icon_url" alt="" />
        <div class="thumb-controls">
          <a href="#" class="btn-icon _black" @click.prevent="handleDownload">
            <span class="icon-download2 remore_margin"></span>
          </a>
        </div>
      </div>
      <div class="creative-item__txt">
        <div class="creative-item__active icon-dot">
          {{ getActiveText() }}
        </div>
        <div class="text-with-copy">
          <div class="text-with-copy__btn">
            <!-- Заглушка для copy-button компонента -->
            <button class="btn copy-btn _flex _dark" type="button" @click="handleCopyTitle">
              <span class="icon-copy">{{ store.getTranslation('copyButton', 'Copy') }}</span>
            </button>
          </div>
          <div class="creative-item__title">
            {{ creative.title }}
          </div>
        </div>
        <div class="text-with-copy">
          <div class="text-with-copy__btn">
            <!-- Заглушка для copy-button компонента -->
            <button class="btn copy-btn _flex _dark" type="button" @click="handleCopyDescription">
              <span class="icon-copy">{{ store.getTranslation('copyButton', 'Copy') }}</span>
            </button>
          </div>
          <div class="creative-item__desc">
            {{ creative.description }}
          </div>
        </div>
      </div>
    </div>
    <div class="creative-item__footer">
      <div class="creative-item__info">
        <div class="creative-item-info">
          <span class="creative-item-info__txt">{{ getNetworkText() }}</span>
        </div>
        <div class="creative-item-info">
          <img :src="getFlagIcon()" alt="" />
          {{ creative.country || 'KZ' }}
        </div>
        <div class="creative-item-info">
          <div :class="getDeviceIconClass()"></div>
          {{ getDeviceText() }}
        </div>
      </div>
      <div class="creative-item__btns">
        <button
          class="btn-icon btn-favorite"
          :class="{ active: isFavorite }"
          @click="handleFavoriteClick"
          :disabled="isFavoriteLoading"
        >
          <span :class="getFavoriteIconClass() + ' remore_margin'"></span>
        </button>
        <button class="btn-icon _dark js-show-details" @click="handleShowDetails">
          <span class="icon-info remore_margin"></span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useCreativesFiltersStore } from '@/stores/useFiltersStore';
import type { Creative } from '@/types/creatives.d';
import { computed } from 'vue';

const store = useCreativesFiltersStore();

const props = defineProps<{
  creative: Creative;
  isFavorite?: boolean;
  isFavoriteLoading?: boolean;
}>();

const emit = defineEmits<{
  'toggle-favorite': [creativeId: number, isFavorite: boolean];
  download: [creative: Creative];
  'show-details': [creative: Creative];
}>();

// Computed для избранного
const isFavorite = computed((): boolean => {
  return props.isFavorite ?? props.creative.isFavorite ?? false;
});

// Функция для обработки клика по избранному
const handleFavoriteClick = (): void => {
  // Блокируем повторные клики если уже идет обработка для этого креатива
  if (props.isFavoriteLoading) {
    console.warn(`Операция с избранным для креатива ${props.creative.id} уже выполняется`);
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

// Функция для получения CSS класса иконки избранного
const getFavoriteIconClass = (): string => {
  return isFavorite.value ? 'icon-favorite' : 'icon-favorite-empty';
};

// Функция для формирования текста активности
const getActiveText = (): string => {
  if (props.creative.activity_date) {
    const activityDate = new Date(props.creative.activity_date);
    const now = new Date();
    const diffTime = Math.abs(now.getTime() - activityDate.getTime());
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 1) {
      return `Active ${diffDays} day`;
    }
    return `Active ${diffDays} days`;
  }
  return 'Active 3 days';
};

// Функция для получения текста сети
const getNetworkText = (): string => {
  if (props.creative.advertising_networks && props.creative.advertising_networks.length > 0) {
    return props.creative.advertising_networks[0];
  }
  return 'Push.house';
};

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

// Функция для обработки клика по кнопке скачивания
const handleDownload = (): void => {
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

// Функция для обработки клика по кнопке копирования названия
const handleCopyTitle = async (): Promise<void> => {
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

// Функция для обработки клика по кнопке показа деталей
const handleShowDetails = (): void => {
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
</script>
