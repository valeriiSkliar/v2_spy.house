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
        <!-- Push компонент (InpageCreativeCard используется для push) -->
        <InpageCreativeCard v-if="currentTab === 'inpage'" :creative="creative" />

        <!-- InPage компонент -->
        <!-- <InpageCreativeCard v-else-if="currentTab === 'inpage'" :creative="creative" /> -->

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
import { computed, defineComponent, onMounted, type PropType } from 'vue';
import type { Creative } from '../../types/creatives';
import InpageCreativeCard from './cards/InpageCreativeCard.vue';

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

/**
 * Компонент для социальных сетей (временная заглушка)
 * TODO: Заменить на полноценный SocialCreativeCard
 */
const SocialCreativeCard = defineComponent({
  props: {
    creative: { type: Object as PropType<Creative>, required: true },
    socialType: { type: String, required: true },
  },
  template: `
    <div class="creative-item" :class="'_' + socialType">
      <div class="creative-item__header">
        <h3 class="creative-item__title">
          {{ creative.name || \`Креатив #\${creative.id}\` }}
        </h3>
        <div class="creative-item__platform">
          <img :src="'/img/' + socialType + '.svg'" :alt="socialType" />
        </div>
      </div>
      <div class="creative-item__info">
        <p class="creative-item__description">
          {{ creative.category || 'Нет описания' }}
        </p>
        <div class="creative-item__social" v-if="socialType === 'facebook' || socialType === 'tiktok'">
          <div class="creative-item__social-item">
            <strong>{{ creative.social_likes || '285' }}</strong>
            <span>Likes</span>
          </div>
          <div class="creative-item__social-item">
            <strong>{{ creative.social_comments || '2' }}</strong>
            <span>Comments</span>
          </div>
          <div class="creative-item__social-item">
            <strong>{{ creative.social_shares || '7' }}</strong>
            <span>Shares</span>
          </div>
        </div>
      </div>
    </div>
  `,
});

/**
 * Универсальный компонент карточки (временная заглушка)
 * TODO: Заменить на специализированные компоненты
 */
const UniversalCreativeCard = defineComponent({
  props: {
    creative: { type: Object as PropType<Creative>, required: true },
    cardType: { type: String, required: true },
  },
  template: `
    <div class="creative-item">
      <div class="creative-item__header">
        <h3 class="creative-item__title">
          {{ creative.name || \`Креатив #\${creative.id}\` }}
        </h3>
        <span class="creative-item__type">{{ cardType.toUpperCase() }}</span>
      </div>
      <div class="creative-item__info">
        <p class="creative-item__description">
          {{ creative.category || 'Нет описания' }}
        </p>
        <div class="creative-item__meta">
          <span class="meta-item" v-if="creative.advertising_networks">
            <strong>Сеть:</strong> {{ formatArrayField(creative.advertising_networks) }}
          </span>
          <span class="meta-item" v-if="creative.country">
            <strong>Страна:</strong> {{ creative.country }}
          </span>
        </div>
      </div>
    </div>
  `,
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

<!-- <style scoped>
.creatives-list {
  width: 100%;
}

.creatives-list__loading,
.creatives-list__error,
.creatives-list__empty {
  padding: 2rem;
  text-align: center;
  color: #666;
}

.creatives-list__error {
  color: #dc3545;
}

/* Loading spinner */
.loading-spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

/* Placeholder стили */
.creative-item.placeholder {
  background: #f8f9fa;
  border-color: #e9ecef;
  pointer-events: none;
}

.placeholder-line {
  background: linear-gradient(90deg, #e9ecef 25%, #f8f9fa 50%, #e9ecef 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
  height: 1rem;
  margin-bottom: 0.5rem;
}

.placeholder-badge {
  background: linear-gradient(90deg, #e9ecef 25%, #f8f9fa 50%, #e9ecef 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
  height: 1.5rem;
  width: 60px;
}

.creative-item.placeholder .creative-item__title {
  width: 70%;
}

.creative-item.placeholder .creative-item__description {
  width: 100%;
}

.creative-item.placeholder .meta-item {
  width: 80px;
}

@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }
  100% {
    background-position: 200% 0;
  }
}

.creatives-list__items {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* Основные стили для карточек */
.creative-item {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 1rem;
  background: #fff;
  transition: box-shadow 0.2s ease;
}

.creative-item:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.creative-item__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.creative-item__title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #333;
  flex: 1;
  margin-right: 1rem;
}

.creative-item__type {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
  background: #e9ecef;
  color: #6c757d;
}

.creative-item__platform img {
  width: 22px;
  height: 22px;
}

.creative-item__description {
  margin: 0 0 0.75rem 0;
  color: #666;
  line-height: 1.4;
}

.creative-item__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

.meta-item {
  font-size: 0.9rem;
  color: #555;
}

.meta-item strong {
  color: #333;
}

/* Стили для социальных сетей */
.creative-item._facebook,
.creative-item._tiktok {
  padding: 0 18px 18px;
}

.creative-item__social {
  display: flex;
  gap: 1rem;
  margin-top: 0.75rem;
}

.creative-item__social-item {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  font-size: 16px;
  gap: 3px;
}

.creative-item__social-item strong {
  font-weight: 600;
}

.creative-item__social-item span {
  color: #85939a;
  font-size: 12px;
}

/* Responsive design */
@media (max-width: 768px) {
  .creative-item__header {
    flex-direction: column;
    gap: 0.5rem;
  }

  .creative-item__title {
    margin-right: 0;
  }

  .creative-item__meta {
    flex-direction: column;
    gap: 0.25rem;
  }

  .creative-item__social {
    flex-direction: row;
    justify-content: space-around;
  }
}
</style> -->
