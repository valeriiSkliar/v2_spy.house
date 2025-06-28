<template>
  <div class="creative-item">
    <div class="creative-item__head">
      <div class="creative-item__txt">
        <div class="creative-item__active" :class="{ 'icon-dot': isActive }">
          {{ getActiveText() }}
        </div>
        <div class="text-with-copy">
          <div class="text-with-copy__btn">
            <!-- Заглушка для copy-button компонента -->
            <button class="btn-icon _copy" type="button">
              <span class="icon-copy"></span>
            </button>
          </div>
          <div class="creative-item__title">
            {{ creative.name || '⚡ What are the pensions the increase? 💰' }}
          </div>
        </div>
        <div class="text-with-copy">
          <div class="text-with-copy__btn">
            <!-- Заглушка для copy-button компонента -->
            <button class="btn-icon _copy" type="button">
              <span class="icon-copy"></span>
            </button>
          </div>
          <div class="creative-item__desc">
            {{ creative.category || 'How much did Kazakhstanis begin to receive' }}
          </div>
        </div>
      </div>
      <div class="creative-item__icon thumb thumb-with-controls-small">
        <img :src="getIconUrl()" alt="" />
        <div class="thumb-controls">
          <a href="#" class="btn-icon _black">
            <span class="icon-download2 remore_margin"></span>
          </a>
        </div>
      </div>
    </div>
    <div class="creative-item__image thumb thumb-with-controls">
      <img :src="getImageUrl()" alt="" />
      <div class="thumb-controls">
        <a href="#" class="btn-icon _black">
          <span class="icon-download2 remore_margin"></span>
        </a>
        <a href="#" class="btn-icon _black">
          <span class="icon-new-tab remore_margin"></span>
        </a>
      </div>
    </div>
    <div class="creative-item__footer">
      <div class="creative-item__info">
        <div class="creative-item-info">
          <span class="creative-item-info__txt">{{ getNetworkText() }}</span>
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
        <button class="btn-icon btn-favorite" :class="{ active: isFavorite }">
          <span :class="getFavoriteIconClass() + ' remore_margin'"></span>
        </button>
        <button class="btn-icon _dark js-show-details">
          <span class="icon-info remore_margin"></span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Creative } from '@/types/creatives.d';
import { computed } from 'vue';

const props = defineProps<{
  creative: Creative;
}>();

// Computed для определения активности (заглушка)
const isActive = computed((): boolean => {
  // Логика будет добавлена позже
  return true;
});

// Computed для избранного (заглушка)
const isFavorite = computed((): boolean => {
  // Логика будет добавлена позже
  return false;
});

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

// Функция для получения URL иконки
const getIconUrl = (): string => {
  return props.creative.preview_url || props.creative.file_url || '/img/th-2.jpg';
};

// Функция для получения URL изображения
const getImageUrl = (): string => {
  return props.creative.file_url || '/img/th-3.jpg';
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

// Функция для получения CSS класса иконки избранного
const getFavoriteIconClass = (): string => {
  return isFavorite.value ? 'icon-favorite' : 'icon-favorite-empty';
};
</script>
