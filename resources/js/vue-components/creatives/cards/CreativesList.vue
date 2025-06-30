<template>
  <div class="creatives-list">
    <div class="creatives-grid" v-if="hasCreatives">
      <component
        :is="getCreativeCardComponent(creative)"
        v-for="creative in creatives"
        :key="creative.id"
        :creative="creative"
        :is-favorite="store.isFavoriteCreative(creative.id)"
        :is-favorite-loading="store.isFavoriteLoading(creative.id)"
        @toggle-favorite="handleToggleFavorite"
        @download="handleDownload"
        @show-details="handleShowDetails"
        @open-in-new-tab="handleOpenInNewTab"
      />
    </div>

    <div class="creatives-empty" v-else-if="!isLoading">
      <p>Креативы не найдены</p>
    </div>

    <div class="creatives-loading" v-if="isLoading">
      <p>Загрузка креативов...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useCreativesFiltersStore } from '@/stores/useFiltersStore';
import type { Creative } from '@/types/creatives.d';
import { computed } from 'vue';
import InpageCreativeCard from './InpageCreativeCard.vue';
import PushCreativeCard from './PushCreativeCard.vue';

// Используем централизованный Store
const store = useCreativesFiltersStore();

// Computed свойства из Store
const creatives = computed(() => store.creatives);
const isLoading = computed(() => store.isLoading);
const hasCreatives = computed(() => store.hasCreatives);
const activeTab = computed(() => store.tabs.activeTab);

/**
 * Определяет какой компонент карточки использовать для креатива
 */
function getCreativeCardComponent(creative: Creative) {
  const tab = activeTab.value;

  // Определяем компонент на основе активной вкладки
  if (tab === 'inpage') {
    return InpageCreativeCard;
  }

  // Для всех остальных вкладок используем PushCreativeCard
  return PushCreativeCard;
}

/**
 * Обработчики событий от карточек
 * Централизованная логика уже в Store через DOM события,
 * но можем добавить дополнительную логику здесь если нужно
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
</script>

<style scoped>
.creatives-list {
  width: 100%;
}

.creatives-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  padding: 20px 0;
}

.creatives-empty,
.creatives-loading {
  text-align: center;
  padding: 40px 20px;
  color: #666;
}

.creatives-loading {
  font-style: italic;
}

@media (max-width: 768px) {
  .creatives-grid {
    grid-template-columns: 1fr;
    gap: 15px;
    padding: 15px 0;
  }
}
</style>
