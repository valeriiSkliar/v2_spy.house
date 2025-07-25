<template>
  <nav class="pagination-nav" role="navigation" aria-label="pagination" v-if="shouldShow">
    <ul class="pagination-list">
      <!-- Previous Page Link -->
      <li>
        <a
          class="pagination-link prev"
          :class="{ disabled: isOnFirstPage }"
          :aria-disabled="isOnFirstPage ? 'true' : 'false'"
          href="#"
          @click.prevent="goToPreviousPage"
        >
          <span class="icon-prev"></span>
          <span class="pagination-link__txt"></span>
        </a>
      </li>

      <!-- Page Numbers -->
      <li v-for="page in visiblePages" :key="page.number">
        <a
          v-if="page.type === 'page'"
          class="pagination-link"
          :class="{ active: page.number === currentPage }"
          :aria-current="page.number === currentPage ? 'page' : undefined"
          href="#"
          @click.prevent="goToPage(page.number)"
        >
          {{ page.number }}
        </a>

        <!-- Three Dots Separator -->
        <span v-else-if="page.type === 'dots'" class="pagination-dots"> ... </span>
      </li>

      <!-- Next Page Link -->
      <li>
        <a
          class="pagination-link next"
          :class="{ disabled: isOnLastPage }"
          :aria-disabled="isOnLastPage ? 'true' : 'false'"
          href="#"
          @click.prevent="goToNextPage"
        >
          <span class="pagination-link__txt"></span>
          <span class="icon-next"></span>
        </a>
      </li>
    </ul>

    <!-- Pagination Info -->
    <!-- <div class="pagination-info" v-if="showInfo">
      {{ translations.page || 'Страница' }} {{ currentPage }} {{ translations.of || 'из' }}
      {{ lastPage }} ({{ fromItem }}-{{ toItem }} {{ translations.of || 'из' }} {{ totalItems }})
    </div> -->
  </nav>
</template>

<script setup lang="ts">
import { useCreativesFiltersStore } from '@/stores/useFiltersStore';
import { hidePlaceholderManually } from '@/vue-islands';
import { computed, onBeforeUnmount, onMounted } from 'vue';

/**
 * Props компонента пагинации
 */
interface Props {
  translations?: Record<string, string>;
  showInfo?: boolean;
  maxVisiblePages?: number;
  alwaysShowFirstLast?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  translations: () => ({}),
  showInfo: true,
  maxVisiblePages: 7,
  alwaysShowFirstLast: true,
});

// ============================================================================
// STORE CONNECTION
// ============================================================================

const store = useCreativesFiltersStore();

// ============================================================================
// COMPUTED PROPERTIES
// ============================================================================

// Основные свойства пагинации из store
const pagination = computed(() => store.pagination);
const isLoading = computed(() => store.isLoading);

// Используем computed свойства из store для лучшей инкапсуляции
const currentPage = computed(() => store.currentPage);
const lastPage = computed(() => store.lastPage);
const totalItems = computed(() => store.totalItems);
const perPage = computed(() => store.perPage);
const fromItem = computed(() => store.fromItem);
const toItem = computed(() => store.toItem);

// Состояния пагинации из store
const isOnFirstPage = computed(() => store.isOnFirstPage);
const isOnLastPage = computed(() => store.isOnLastPage);
const shouldShow = computed(() => store.shouldShowPagination);

// ============================================================================
// PAGINATION LOGIC
// ============================================================================

/**
 * Интерфейс для элементов видимых страниц
 */
interface PageItem {
  type: 'page' | 'dots';
  number: number;
}

/**
 * Вычисляет видимые страницы с учетом многоточий
 */
const visiblePages = computed((): PageItem[] => {
  const pages: PageItem[] = [];
  const current = currentPage.value;
  const last = lastPage.value;
  const maxVisible = props.maxVisiblePages;

  if (last <= maxVisible) {
    // Если страниц мало, показываем все
    for (let i = 1; i <= last; i++) {
      pages.push({ type: 'page', number: i });
    }
    return pages;
  }

  // Сложная логика для большого количества страниц
  const halfVisible = Math.floor(maxVisible / 2);

  let startPage = Math.max(1, current - halfVisible);
  let endPage = Math.min(last, current + halfVisible);

  // Корректируем диапазон если мы близко к краям
  if (current <= halfVisible) {
    endPage = Math.min(last, maxVisible - 1);
  } else if (current >= last - halfVisible) {
    startPage = Math.max(1, last - maxVisible + 2);
  }

  // Всегда показываем первую страницу
  if (props.alwaysShowFirstLast && startPage > 1) {
    pages.push({ type: 'page', number: 1 });
    if (startPage > 2) {
      pages.push({ type: 'dots', number: -1 });
    }
  }

  // Добавляем основной диапазон
  for (let i = startPage; i <= endPage; i++) {
    pages.push({ type: 'page', number: i });
  }

  // Всегда показываем последнюю страницу
  if (props.alwaysShowFirstLast && endPage < last) {
    if (endPage < last - 1) {
      pages.push({ type: 'dots', number: -2 });
    }
    pages.push({ type: 'page', number: last });
  }

  return pages;
});

// ============================================================================
// NAVIGATION METHODS
// ============================================================================

/**
 * Все методы навигации используют методы store для правильной инкапсуляции.
 * Store обеспечивает синхронизацию с URL и корректное управление состоянием.
 */

/**
 * Переход на конкретную страницу
 */
function goToPage(page: number): void {
  if (page < 1 || page > lastPage.value || page === currentPage.value || isLoading.value) {
    return;
  }

  console.log(`🔄 Pagination: переход на страницу ${page}`);
  store.loadPage(page);
}

/**
 * Переход на предыдущую страницу
 */
function goToPreviousPage(): void {
  if (!isOnFirstPage.value) {
    store.goToPreviousPage();
  }
}

/**
 * Переход на следующую страницу
 */
function goToNextPage(): void {
  if (!isOnLastPage.value) {
    store.goToNextPage();
  }
}

/**
 * Переход на первую страницу
 */
function goToFirstPage(): void {
  store.goToFirstPage();
}

/**
 * Переход на последнюю страницу
 */
function goToLastPage(): void {
  store.goToLastPage();
}

// ============================================================================
// KEYBOARD NAVIGATION
// ============================================================================

/**
 * Обработка навигации клавиатурой
 */
function handleKeydown(event: KeyboardEvent): void {
  // Проверяем что фокус на элементе пагинации
  const target = event.target as HTMLElement;
  if (!target.closest('.pagination-nav')) return;

  switch (event.key) {
    case 'ArrowLeft':
      event.preventDefault();
      goToPreviousPage();
      break;
    case 'ArrowRight':
      event.preventDefault();
      goToNextPage();
      break;
    case 'Home':
      event.preventDefault();
      goToFirstPage();
      break;
    case 'End':
      event.preventDefault();
      goToLastPage();
      break;
  }
}

// ============================================================================
// LIFECYCLE
// ============================================================================

onMounted(() => {
  console.log('🎯 PaginationComponent смонтирован, текущая пагинация:', {
    currentPage: currentPage.value,
    lastPage: lastPage.value,
    totalItems: totalItems.value,
    shouldShow: shouldShow.value,
  });

  // Добавляем обработчик клавиатуры
  document.addEventListener('keydown', handleKeydown);
  hidePlaceholderManually('PaginationComponent');
  // Эмитируем событие готовности компонента
  // const readyEvent = new CustomEvent('vue-component-ready', {
  //   detail: {
  //     component: 'PaginationComponent',
  //     currentPage: currentPage.value,
  //     lastPage: lastPage.value,
  //   },
  // });
  // document.dispatchEvent(readyEvent);
});

// Очистка при размонтировании
onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeydown);
});

// ============================================================================
// EXPOSE METHODS (для программного доступа)
// ============================================================================

defineExpose({
  goToPage,
  goToPreviousPage,
  goToNextPage,
  goToFirstPage,
  goToLastPage,
  currentPage,
  lastPage,
  isLoading,
});
</script>
<style lang="scss" scoped>
.pagination-list {
  li {
    display: flex;
    align-items: center;
    .pagination-dots {
      display: flex;
      justify-content: center;
      align-items: end;
    }
  }
}
</style>
