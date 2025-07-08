<template>
  <div
    v-if="!isCreativesLoading"
    class="creative-item"
    :class="[
      `_${activeTab}`,
      {
        'creative-item--loading': isCreativesLoading,
        'creative-item--disabled': isAnyLoading,
      },
    ]"
  >
    <div
      class="creative-video"
      :class="{ 'has-video': creative.has_video }"
      @mouseenter="onVideoHover"
      @mouseleave="onVideoLeave"
    >
      <div class="thumb">
        <img
          src="https://dev.vitaliimaksymchuk.com.ua/spy/img/facebook-2.jpg"
          alt=""
          class="thumb-blur"
        />
        <!--TODO: change to real image-->
        <img
          src="https://dev.vitaliimaksymchuk.com.ua/spy/img/facebook-2.jpg"
          alt=""
          class="thumb-contain"
        />
        <!--TODO: change to real image-->
      </div>
      <span v-if="creative.has_video" class="icon-play"></span>
      <div v-if="creative.duration" class="creative-video__time">{{ creative.duration }}</div>
      <div
        class="creative-video__content"
        :data-video="creative.video_url || 'img/video-3.mp4'"
        v-html="videoContent"
      ></div>
    </div>
    <div class="creative-item__row">
      <div class="creative-item__icon thumb"><img :src="icon" alt="" /></div>
      <div class="creative-item__title">{{ creative.title }}</div>
      <div class="creative-item__platform">
        <img :src="activeTab === 'facebook' ? facebookIcon : tiktokIcon" alt="" />
      </div>
    </div>
    <div class="creative-item__row">
      <div class="creative-item__desc font-roboto">
        {{ creative.description }}
      </div>
      <div class="creative-item__copy">
        <button
          class="btn-icon js-copy _border-gray"
          @click="handleCopyDescription"
          :disabled="isCreativesLoading"
        >
          <span class="icon-copy"></span>
          <span class="icon-check d-none"></span>
        </button>
      </div>
    </div>
    <div class="creative-item__social">
      <div class="creative-item__social-item">
        <strong>285</strong> <span>{{ translations.likes.value }}</span>
      </div>
      <div class="creative-item__social-item">
        <strong>2</strong> <span>{{ translations.comments.value }}</span>
      </div>
      <div class="creative-item__social-item">
        <strong>7</strong> <span>{{ translations.shared.value }}</span>
      </div>
    </div>
    <div class="creative-item__footer">
      <div class="creative-item__info">
        <div class="creative-status icon-dot font-roboto">
          {{ translations.active.value }} {{ getActiveText() }}
        </div>
      </div>
      <div class="creative-item__btns">
        <div class="creative-item-info">
          <img :src="getFlagIcon()" alt="" />
          {{ creative.country || 'KZ' }}
        </div>
        <button
          class="btn-icon btn-favorite"
          :class="{
            active: isFavorite,
            loading: props.isFavoriteLoading,
          }"
          @click="handleFavoriteClick"
          :disabled="isAnyLoading"
        >
          <span :class="getFavoriteIconClass()"></span>
        </button>
        <button
          class="btn-icon _dark"
          @click="handleShowDetails(props.creative.id)"
          :disabled="isCreativesLoading"
        >
          <span class="icon-info"></span>
        </button>
      </div>
    </div>
  </div>
  <div v-else class="similar-creatives">
    <div class="similar-creative-empty _social">
      <img :src="empty" alt="empty" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { useCreativesFiltersStore } from '@/stores/useFiltersStore';
import type { Creative, TabValue } from '@/types/creatives.d';
import empty from '@img/empty.svg';
import facebookIcon from '@img/facebook.svg';
import icon from '@img/icon-1.jpg';
import tiktokIcon from '@img/tiktok.svg';
// import instagramIcon from '@img/instagram.svg';
import { computed, onMounted, ref } from 'vue';

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
    likes: 'likes',
    comments: 'comments',
    shared: 'shared',
    active: 'active',
  },
  {
    likes: 'Like',
    comments: 'Comments',
    shared: 'Shared',
    active: 'Active:',
  }
);

const props = defineProps<{
  activeTab: TabValue;
  creative: Creative;
  isFavorite?: boolean;
  isFavoriteLoading?: boolean;
  translations?: Record<string, string>;
  handleOpenInNewTab: (url: string) => void;
  handleDownload: (url: string) => void;
  handleShowDetails: (id: number) => void;
}>();

const emit = defineEmits<{
  'show-details': [creative: Creative];
  'toggle-favorite': [creativeId: number, isFavorite: boolean];
  download: [creative: Creative];
}>();

// Защита от race condition при инициализации
onMounted(async () => {
  // Мержим переводы из props с Store для обратной совместимости
  mergePropsTranslations(props.translations, store.setTranslations);

  // Ждем готовности переводов
  await waitForReady();
});

const activeTab = computed(() => props.activeTab);
const creative = computed(() => props.creative);

// Реактивное состояние для отслеживания hover
const isVideoHovered = ref(false);

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

// Computed для управления контентом видео
const videoContent = computed(() => {
  // Проверяем наличие флага has_video и состояние hover
  if (!creative.value.has_video || !isVideoHovered.value) {
    return '';
  }

  const videoUrl = creative.value.video_url || 'img/video-3.mp4';
  return `<video loop="loop" autoplay muted="muted" webkit-playsinline playsinline controls>
    <source type="video/mp4" src="${videoUrl}">
  </video>`;
});

// Обработчики событий hover - работают только при наличии видео
const onVideoHover = () => {
  if (creative.value.has_video) {
    isVideoHovered.value = true;
  }
};

const onVideoLeave = () => {
  if (creative.value.has_video) {
    isVideoHovered.value = false;
  }
};

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
  return getActiveDaysText(props.creative.activity_date, '3 days');
};

// Функция для получения иконки флага
const getFlagIcon = (): string => {
  return `/img/flags/${props.creative.country?.code}.svg`;
};

// Функция для получения CSS класса иконки избранного
const getFavoriteIconClass = (): string => {
  return isFavorite.value ? 'icon-favorite' : 'icon-favorite-empty';
};
</script>

<style scoped lang="scss">
// // Стили для социальных карточек креативов
.creative-video {
  &.has-video:hover {
    .creative-video__content {
      height: calc(100% + 165px);
      opacity: 1;
      visibility: visible;
    }
  }
}
</style>
