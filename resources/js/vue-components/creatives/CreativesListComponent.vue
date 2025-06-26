<template>
  <div class="creatives-list">
    <!-- Статус загрузки -->
    <div v-if="isLoading" class="creatives-list__loading">
      <p>{{ translations.loading || 'Загрузка...' }}</p>
    </div>

    <!-- Ошибка загрузки -->
    <div v-else-if="error" class="creatives-list__error">
      <p>{{ translations.error || 'Ошибка загрузки' }}: {{ error }}</p>
      <button @click="() => loadCreatives()" class="btn btn-primary">
        {{ translations.retry || 'Повторить' }}
      </button>
    </div>

    <!-- Список креативов -->
    <div v-else-if="creatives.length > 0" class="creatives-list__items">
      <div v-for="creative in creatives" :key="creative.id" class="creative-item">
        <!-- Простое отображение для тестирования -->
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
              <strong>Сеть:</strong> {{ creative.advertising_networks }}
            </span>
            <span class="meta-item" v-if="creative.country">
              <strong>Страна:</strong> {{ creative.country }}
            </span>
            <span class="meta-item" v-if="creative.languages">
              <strong>Язык:</strong> {{ creative.languages }}
            </span>
            <span class="meta-item" v-if="creative.created_at">
              <strong>Дата создания:</strong> {{ creative.created_at }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Пустое состояние -->
    <div v-else class="creatives-list__empty">
      <p>{{ translations.noData || 'Нет данных для отображения' }}</p>
    </div>
  </div>
  <!-- Пагинация (если есть) -->
  <div v-if="pagination && pagination.last_page > 1" class="creatives-list__pagination">
    <button
      @click="() => loadPage((pagination?.current_page || 1) - 1)"
      :disabled="(pagination?.current_page || 1) <= 1"
      class="btn btn-secondary"
    >
      {{ translations.previousPage || 'Назад' }}
    </button>

    <span class="pagination-info">
      {{ translations.page || 'Страница' }} {{ pagination?.current_page || 1 }}
      {{ translations.of || 'из' }} {{ pagination?.last_page || 1 }}
    </span>

    <button
      @click="() => loadPage((pagination?.current_page || 1) + 1)"
      :disabled="(pagination?.current_page || 1) >= (pagination?.last_page || 1)"
      class="btn btn-secondary"
    >
      {{ translations.nextPage || 'Вперед' }}
    </button>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import type { Creative } from '../../types/creatives';

interface ApiResponse {
  status: string;
  data: {
    items: Creative[];
    pagination: {
      total: number;
      perPage: number;
      currentPage: number;
      lastPage: number;
      from: number;
      to: number;
    };
    meta: {
      hasSearch: boolean;
      activeFiltersCount: number;
      cacheKey: string;
      appliedFilters: any;
    };
  };
}

interface Props {
  viewMode?: 'grid' | 'list';
  enableInfiniteScroll?: boolean;
  enableSelection?: boolean;
  translations?: Record<string, string>;
  activeTab?: string;
  apiEndpoint?: string;
}

const props = withDefaults(defineProps<Props>(), {
  viewMode: 'list',
  enableInfiniteScroll: false,
  enableSelection: true,
  translations: () => ({}),
  activeTab: 'inpage',
  apiEndpoint: '/api/creatives',
});

// Реактивные данные
const creatives = ref<Creative[]>([]);
const pagination = ref<{
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
} | null>(null);
const isLoading = ref(false);
const error = ref<string | null>(null);

// Методы
const loadCreatives = async (page: number = 1) => {
  isLoading.value = true;
  error.value = null;

  try {
    const params = new URLSearchParams({
      tab: props.activeTab,
      page: page.toString(),
      per_page: '12',
    });

    const response = await fetch(`${props.apiEndpoint}?${params}`);

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data: ApiResponse = await response.json();

    creatives.value = data.data.items;
    pagination.value = {
      current_page: data.data.pagination.currentPage,
      last_page: data.data.pagination.lastPage,
      per_page: data.data.pagination.perPage,
      total: data.data.pagination.total,
      from: data.data.pagination.from,
      to: data.data.pagination.to,
    };

    console.log('Креативы загружены:', data);
  } catch (err) {
    console.error('Ошибка загрузки креативов:', err);
    error.value = err instanceof Error ? err.message : 'Неизвестная ошибка';
  } finally {
    isLoading.value = false;
  }
};

const loadPage = (page: number) => {
  if (page >= 1 && pagination.value && page <= pagination.value.last_page) {
    loadCreatives(page);
  }
};

// Инициализация при монтировании
onMounted(() => {
  loadCreatives();
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
