<template>
  <div class="creatives-list">
    <!-- Состояние загрузки -->
    <div v-if="isLoading && !hasCreatives" class="creatives-list__loading">
      <div class="loading-spinner"></div>
      <p>{{ translations.loading || 'Загрузка креативов...' }}</p>
    </div>

    <!-- Состояние ошибки -->
    <div v-else-if="error && !hasCreatives" class="creatives-list__error">
      <p>{{ translations.error || 'Ошибка загрузки креативов' }}</p>
      <button @click="handleRetry" class="btn btn-secondary">
        {{ translations.retry || 'Повторить' }}
      </button>
    </div>

    <!-- Пустое состояние -->
    <div v-else-if="!hasCreatives && !isLoading" class="creatives-list__empty">
      <p>{{ translations.noData || 'Креативы не найдены' }}</p>
    </div>

    <!-- Список креативов -->
    <div v-else class="creatives-list__items">
      <template v-for="creative in creatives" :key="creative.id">
        <!-- Push компонент -->
        <PushCreativeCard v-if="currentTab === 'push'" :creative="creative" />

        <!-- InPage компонент -->
        <InpageCreativeCard v-else-if="currentTab === 'inpage'" :creative="creative" />

        <!-- Facebook/TikTok компонент (пока используется универсальная разметка) -->
        <!-- <SocialCreativeCard
          v-else-if="currentTab === 'facebook' || currentTab === 'tiktok'"
          :creative="creative"
          :social-type="currentTab"
        /> -->

        <!-- Универсальная карточка для остальных типов -->
        <!-- <UniversalCreativeCard v-else :creative="creative" :card-type="currentTab" /> -->
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useCreativesFiltersStore } from '@/stores/useFiltersStore';
import { computed, onMounted } from 'vue';
import type { Creative } from '../../types/creatives';
import InpageCreativeCard from './cards/InpageCreativeCard.vue';
import PushCreativeCard from './cards/PushCreativeCard.vue';

interface Props {
  translations?: Record<string, string>;
  perPage?: number;
  activeTab?: string;
}

const props = withDefaults(defineProps<Props>(), {
  translations: () => ({}),
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

// Инициализация при монтировании
onMounted(() => {
  console.log('🎯 CreativesListComponent смонтирован, данные из store:', {
    hasCreatives: hasCreatives.value,
    creativesCount: creatives.value.length,
    isLoading: isLoading.value,
    error: error.value,
    currentTab: currentTab.value,
  });

  // Эмитируем событие готовности компонента
  const readyEvent = new CustomEvent('vue-component-ready', {
    detail: {
      component: 'CreativesListComponent',
      hasData: hasCreatives.value,
      activeTab: currentTab.value,
    },
  });
  document.dispatchEvent(readyEvent);
});
</script>
