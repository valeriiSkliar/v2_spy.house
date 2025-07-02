<template>
  <div class="creatives-list">
    <!-- Состояние загрузки -->
    <!-- <div v-if="isLoading && !hasCreatives" class="creatives-list__loading">
      <div class="loading-spinner"></div>
      <p>{{ translations.loading || 'Загрузка креативов...' }}</p>
    </div> -->

    <!-- Состояние ошибки -->
    <div v-if="error && !hasCreatives" class="creatives-list__error">
      <p>{{ translations.error || 'Ошибка загрузки креативов' }}</p>
      <button @click="handleRetry" class="btn btn-secondary">
        {{ translations.retry || 'Повторить' }}
      </button>
    </div>

    <!-- Пустое состояние -->
    <!-- <div v-if="!hasCreatives && !isLoading" class="creatives-list__empty">
      <p>{{ translations.noData || 'Креативы не найдены' }}</p>
    </div> -->

    <!-- Список креативов -->
    <div v-else class="creatives-list__items">
      <template v-for="creative in creatives" :key="creative.id">
        <!-- Push компонент -->
        <PushCreativeCard
          v-if="currentTab === 'push'"
          :creative="creative"
          :is-favorite="store.isFavoriteCreative(creative.id)"
          :is-favorite-loading="store.isFavoriteLoading(creative.id)"
          :translations="cardTranslations"
          @toggle-favorite="handleToggleFavorite"
          @download="handleDownload"
          @show-details="handleShowDetails"
          @open-in-new-tab="handleOpenInNewTab"
        />

        <!-- InPage компонент -->
        <InpageCreativeCard
          v-else-if="currentTab === 'inpage'"
          :creative="creative"
          :is-favorite="store.isFavoriteCreative(creative.id)"
          :is-favorite-loading="store.isFavoriteLoading(creative.id)"
          :translations="cardTranslations"
          @toggle-favorite="handleToggleFavorite"
          @download="handleDownload"
          @show-details="handleShowDetails"
        />

        <!-- Facebook/TikTok компонент (пока используется универсальная разметка) -->
        <SocialCreativeCard
          v-else-if="currentTab === 'facebook' || currentTab === 'tiktok'"
          :active-tab="currentTab"
          :creative="creative"
          :is-favorite="store.isFavoriteCreative(creative.id)"
          :is-favorite-loading="store.isFavoriteLoading(creative.id)"
          :translations="cardTranslations"
          @toggle-favorite="handleToggleFavorite"
          @download="handleDownload"
          @show-details="handleShowDetails"
        />

        <!-- Универсальная карточка для остальных типов -->
        <!-- <UniversalCreativeCard 
          v-else 
          :creative="creative" 
          :card-type="currentTab"
          :is-favorite="store.isFavoriteCreative(creative.id)"
          :is-favorite-loading="store.isFavoritesLoading"
          @toggle-favorite="handleToggleFavorite"
          @download="handleDownload"
          @show-details="handleShowDetails"
        /> -->
      </template>
    </div>
    <CreativeDetailsComponent :showSimilarCreatives="true" :translations="detailsTranslations" />
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
}

const props = withDefaults(defineProps<Props>(), {
  translations: () => ({}),
  cardTranslations: () => ({}),
  detailsTranslations: () => ({}),
  perPage: 12,
  activeTab: 'push',
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

// Computed для определения типа списка (для CSS классов)
const listTypeClass = computed((): string => {
  switch (currentTab.value) {
    case 'facebook':
    case 'tiktok':
      return '_social';
    case 'inpage':
      return '_inpage';
    case 'push':
    default:
      return '_push';
  }
});

// Методы для форматирования данных
function formatArrayField(field: string[] | string | undefined): string {
  if (!field) return '';
  if (Array.isArray(field)) {
    return field.join(', ');
  }
  return String(field);
}

function formatDate(dateString: string): string {
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString('ru-RU', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    });
  } catch {
    return dateString;
  }
}

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

function handleDownload(creative: Creative): void {
  console.log(`Карточка эмитировала download:`, creative);
  // Основная логика обрабатывается в Store через DOM события
}

function handleShowDetails(creative: Creative): void {
  console.log(`Карточка эмитировала show-details:`, creative);
  // Основная логика обрабатывается в Store через DOM события
}

function handleOpenInNewTab(creative: Creative): void {
  console.log(`Карточка эмитировала open-in-new-tab:`, creative);
  // Основная логика обрабатывается в Store через DOM события
}

// Watcher для скрытия placeholder когда данные загружены
watch(
  () => creatives.value.length,
  newLength => {
    if (newLength > 0) {
      hidePlaceholderManually('CreativesListComponent');
      // Скрываем placeholder только когда есть данные
      // const readyEvent = new CustomEvent('vue-component-ready', {
      //   detail: {
      //     component: 'CreativesListComponent',
      //     hasData: true,
      //     activeTab: currentTab.value,
      //   },
      // });
      // document.dispatchEvent(readyEvent);

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

  // Убираем автоматическое скрытие placeholder при монтировании
  // Placeholder будет скрыт только через watcher когда появятся данные
});
</script>
