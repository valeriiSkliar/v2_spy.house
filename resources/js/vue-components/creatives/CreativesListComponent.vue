<template>
  <div class="creatives-list">
    <!-- <div v-if="isLoading && !hasCreatives" class="creatives-list__loading">
      <div class="loading-spinner"></div>
      <p>{{ translations.loading || 'Загрузка креативов...' }}</p>
    </div> -->

    <div v-if="error && !hasCreatives" class="creatives-list__error">
      <p>{{ translations.error || 'Ошибка загрузки креативов' }}</p>
      <button @click="handleRetry" class="btn btn-secondary">
        {{ translations.retry || 'Повторить' }}
      </button>
    </div>

    <!-- <div v-if="!hasCreatives && !isLoading" class="creatives-list__empty">
      <p>{{ translations.noData || 'Креативы не найдены' }}</p>
    </div> -->

    <div v-else class="creatives-list__items">
      <template v-for="creative in creatives" :key="creative.id">
        <PushCreativeCard
          v-if="currentTab === 'push'"
          :creative="creative"
          :is-favorite="store.isFavoriteCreative(creative.id)"
          :is-favorite-loading="store.isFavoriteLoading(creative.id)"
          :translations="cardTranslations"
          :handle-open-in-new-tab="handleOpenInNewTab"
          @toggle-favorite="handleToggleFavorite"
          :handle-download="handleDownload"
          :handle-show-details="handleShowDetails"
        />

        <InpageCreativeCard
          v-else-if="currentTab === 'inpage'"
          :creative="creative"
          :is-favorite="store.isFavoriteCreative(creative.id)"
          :is-favorite-loading="store.isFavoriteLoading(creative.id)"
          :translations="cardTranslations"
          :handle-open-in-new-tab="handleOpenInNewTab"
          @toggle-favorite="handleToggleFavorite"
          :handle-download="handleDownload"
          :handle-show-details="handleShowDetails"
        />

        <SocialCreativeCard
          v-else-if="currentTab === 'facebook' || currentTab === 'tiktok'"
          :active-tab="currentTab"
          :creative="creative"
          :is-favorite="store.isFavoriteCreative(creative.id)"
          :is-favorite-loading="store.isFavoriteLoading(creative.id)"
          :translations="cardTranslations"
          :handle-open-in-new-tab="handleOpenInNewTab"
          @toggle-favorite="handleToggleFavorite"
          :handle-download="handleDownload"
          :handle-show-details="handleShowDetails"
        />
      </template>
    </div>
    <CreativeDetailsComponent
      :handle-open-in-new-tab="handleOpenInNewTab"
      :handle-download="handleDownload"
      :showSimilarCreatives="showSimilarCreatives"
      :translations="detailsTranslations"
    />
  </div>
</template>

<script setup lang="ts">
import { useCreativesFiltersStore } from '@/stores/useFiltersStore';
import { hidePlaceholderManually } from '@/vue-islands';
import { computed, onMounted, watch } from 'vue';
import type { Creative } from '../../types/creatives';
import InpageCreativeCard from './cards/InpageCreativeCard.vue';
import PushCreativeCard from './cards/PushCreativeCard.vue';
import SocialCreativeCard from './cards/SocialCreativeCard.vue';
import CreativeDetailsComponent from './CreativeDetailsComponent.vue';

interface Props {
  translations?: Record<string, string>;
  cardTranslations?: Record<string, string>;
  detailsTranslations?: Record<string, string>;
  perPage?: number;
  activeTab?: string;
  showSimilarCreatives?: boolean;
  userData?: {
    id: number | null;
    email: string | null;
    tariff: any;
    favoritesCount: number;
    isAuthenticated: boolean;
  };
}

const props = withDefaults(defineProps<Props>(), {
  translations: () => ({}),
  cardTranslations: () => ({}),
  detailsTranslations: () => ({}),
  perPage: 12,
  activeTab: 'push',
  showSimilarCreatives: false,
  userData: () => ({
    id: null,
    email: null,
    tariff: null,
    favoritesCount: 0,
    isAuthenticated: false,
  }),
});

// Подключение к store
const store = useCreativesFiltersStore();

// Computed свойства из store
const creatives = computed((): Creative[] => store.creatives);
const isLoading = computed((): boolean => store.isLoading);
const error = computed((): string | null => store.error);
const hasCreatives = computed((): boolean => store.hasCreatives);

// Отслеживание активной вкладки
const currentTab = computed((): string => {
  // Приоритет: активная вкладка из store > prop activeTab > 'push'
  return store.tabs?.activeTab || props.activeTab || 'push';
});

function handleRetry(): void {
  store.refreshCreatives();
}

/**
 * Обработчики событий от карточек креативов
 * Централизованная логика обрабатывается в Store через DOM события,
 * но здесь можем добавить дополнительную логику если нужно
 */
function handleToggleFavorite(creativeId: number, isFavorite: boolean): void {
  console.log(`Карточка эмитировала toggle-favorite: ${creativeId}, isFavorite: ${isFavorite}`);
  // Основная логика обрабатывается в Store через DOM события
}

function handleDownload(url: string): void {
  console.log(`Карточка эмитировала download:`, url);
  document.dispatchEvent(
    new CustomEvent('creatives:download', {
      detail: {
        url,
      },
    })
  );
}

function handleShowDetails(id: number): void {
  console.log(`Карточка эмитировала show-details для креатива ID: ${id}`);

  // Передаем только ID креатива - композабл сам загрузит данные
  document.dispatchEvent(
    new CustomEvent('creatives:show-details', {
      detail: {
        id,
      },
    })
  );
}

const handleOpenInNewTab = (url: string): void => {
  // Блокируем открытие в новой вкладке во время загрузки списка
  if (isLoading.value) {
    console.warn(`Открытие креатива в новой вкладке заблокировано: идет загрузка списка`);
    return;
  }

  // Эмитируем DOM событие для централизованной обработки
  document.dispatchEvent(
    new CustomEvent('creatives:open-in-new-tab', {
      detail: {
        url: url,
      },
    })
  );
};

// Watcher для скрытия placeholder когда данные загружены
watch(
  () => creatives.value.length,
  newLength => {
    if (newLength > 0) {
      hidePlaceholderManually('CreativesListComponent');
      console.log('🎯 Креативы загружены, placeholder скрыт', {
        creativesCount: newLength,
        currentTab: currentTab.value,
      });
    }
  },
  { immediate: true }
);

// Инициализация при монтировании
onMounted(() => {
  console.log('🎯 CreativesListComponent смонтирован, данные из store:', {
    hasCreatives: hasCreatives.value,
    creativesCount: creatives.value.length,
    isLoading: isLoading.value,
    error: error.value,
    currentTab: currentTab.value,
  });

  // Инициализируем данные пользователя в Store если переданы
  if (props.userData) {
    console.log('🔧 Инициализируем userData в Store:', props.userData);
    store.setUserData(props.userData);
  }

  // Убираем автоматическое скрытие placeholder при монтировании
  // Placeholder будет скрыт только через watcher когда появятся данные
});
</script>
