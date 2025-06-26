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
      <div v-for="creative in creatives" :key="creative.id" class="creative-item">
        <div class="creative-item__header">
          <h3 class="creative-item__title">
            {{ creative.name || `Креатив #${creative.id}` }}
          </h3>
          <span
            class="creative-item__status"
            :class="`status--${creative.is_adult ? 'adult' : 'not-adult'}`"
          >
            {{ creative.is_adult ? 'Adult' : 'Not Adult' }}
          </span>
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
            <span class="meta-item" v-if="creative.languages">
              <strong>Язык:</strong> {{ formatArrayField(creative.languages) }}
            </span>
            <span class="meta-item" v-if="creative.created_at">
              <strong>Дата создания:</strong> {{ formatDate(creative.created_at) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Пагинация -->
    <div v-if="hasCreatives" class="creatives-list__pagination">
      <div class="pagination-info">
        {{ translations.page || 'Страница' }} {{ pagination.currentPage }}
        {{ translations.of || 'из' }} {{ pagination.lastPage }} ({{ pagination.from }}-{{
          pagination.to
        }}
        {{ translations.of || 'из' }} {{ pagination.total }})
      </div>

      <div class="pagination-controls">
        <button
          @click="loadPreviousPage"
          :disabled="pagination.currentPage <= 1 || isLoading"
          class="btn btn-secondary"
        >
          {{ translations.previousPage || 'Предыдущая' }}
        </button>

        <button
          @click="loadNextPage"
          :disabled="pagination.currentPage >= pagination.lastPage || isLoading"
          class="btn btn-primary"
        >
          {{ translations.nextPage || 'Следующая' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useCreativesFiltersStore } from '@/stores/useFiltersStore';
import { computed, onMounted } from 'vue';
import type { Creative } from '../../types/creatives';

interface Props {
  translations?: Record<string, string>;
  perPage?: number;
}

const props = withDefaults(defineProps<Props>(), {
  translations: () => ({}),
  perPage: 12,
});

// Подключение к store
const store = useCreativesFiltersStore();

// Computed свойства из store
const creatives = computed((): Creative[] => store.creatives);
const pagination = computed(() => store.pagination);
const isLoading = computed((): boolean => store.isLoading);
const error = computed((): string | null => store.error);
const hasCreatives = computed((): boolean => store.hasCreatives);

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

// Методы пагинации
function loadNextPage(): void {
  store.loadNextPage();
}

function loadPreviousPage(): void {
  const prevPage = pagination.value.currentPage - 1;
  if (prevPage >= 1) {
    store.loadCreatives(prevPage);
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
  });

  // Эмитируем событие готовности компонента
  const readyEvent = new CustomEvent('vue-component-ready', {
    detail: {
      component: 'CreativesListComponent',
      hasData: hasCreatives.value,
    },
  });
  document.dispatchEvent(readyEvent);
});
</script>

<style scoped>
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

.creative-item__status {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.85rem;
  font-weight: 500;
  text-transform: uppercase;
}

.status--active {
  background: #d4edda;
  color: #155724;
}

.status--was-active,
.status--was {
  background: #f8d7da;
  color: #721c24;
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

.creatives-list__pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 2rem;
  padding: 1rem;
}

.pagination-info {
  font-size: 0.9rem;
  color: #666;
}

.btn {
  padding: 0.5rem 1rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  background: #fff;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  display: inline-block;
}

.btn:hover:not(:disabled) {
  background: #f8f9fa;
  border-color: #adb5bd;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-primary {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.btn-primary:hover:not(:disabled) {
  background: #0056b3;
  border-color: #0056b3;
}

.btn-secondary {
  background: #6c757d;
  color: white;
  border-color: #6c757d;
}

.btn-secondary:hover:not(:disabled) {
  background: #545b62;
  border-color: #545b62;
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

  .creatives-list__pagination {
    flex-direction: column;
    gap: 0.5rem;
  }
}
</style>
