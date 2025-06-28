<!-- resources/js/vue-components/ui/FavoritesCounter.vue -->
<!-- Интерактивный счетчик избранного креативов -->
<template>
  <span
    class="btn__count favorites-counter"
    :class="{
      'favorites-counter--loading': isLoading,
      'favorites-counter--animated': shouldAnimate,
    }"
    @click.stop="handleCounterClick"
    :title="getTooltip()"
  >
    <transition name="counter-update" mode="out-in">
      <span :key="displayCount" class="favorites-counter__value">
        {{ displayCount }}
      </span>
    </transition>

    <!-- Индикатор загрузки -->
    <span v-if="isLoading" class="favorites-counter__loader">
      <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
    </span>
  </span>
</template>

<script setup lang="ts">
import { useCreativesFiltersStore } from '@/stores/useFiltersStore';
import { computed, ref, watch } from 'vue';

interface Props {
  /** Начальное количество избранного */
  initialCount?: number;
  /** Переводы для tooltip'ов */
  translations?: Record<string, string>;
  /** Включить анимацию при изменении */
  enableAnimation?: boolean;
  /** Показывать ли loader при загрузке */
  showLoader?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  initialCount: 0,
  translations: () => ({}),
  enableAnimation: true,
  showLoader: true,
});

// ============================================================================
// СОСТОЯНИЕ КОМПОНЕНТА
// ============================================================================

const store = useCreativesFiltersStore();
const isLoading = ref(false);
const shouldAnimate = ref(false);

// Локальное состояние счетчика (для оптимистичных обновлений)
const localCount = ref(props.initialCount);

// ============================================================================
// COMPUTED СВОЙСТВА
// ============================================================================

/** Отображаемое количество избранного */
const displayCount = computed(() => {
  // Приоритет: Store > локальное состояние > props
  return store.favoritesCount ?? localCount.value;
});

/** Получение tooltip'а для счетчика */
function getTooltip(): string {
  const key = 'favoritesCountTooltip';
  const defaultText = `Избранное: ${displayCount.value}`;
  return props.translations[key] || defaultText;
}

// ============================================================================
// МЕТОДЫ
// ============================================================================

/**
 * Обработчик клика по счетчику
 * Показывает список избранного или обновляет данные
 */
async function handleCounterClick(): Promise<void> {
  if (isLoading.value) return;

  try {
    isLoading.value = true;

    // Эмитим событие для родительского компонента
    emit('counter-clicked', {
      currentCount: displayCount.value,
      timestamp: new Date().toISOString(),
    });

    // Обновляем счетчик через Store
    await store.refreshFavoritesCount();

    // Анимация при успешном обновлении
    if (props.enableAnimation) {
      triggerAnimation();
    }
  } catch (error) {
    console.error('Ошибка при обновлении счетчика избранного:', error);

    // Эмитим событие об ошибке
    emit('counter-error', {
      error: error instanceof Error ? error.message : 'Unknown error',
      timestamp: new Date().toISOString(),
    });
  } finally {
    isLoading.value = false;
  }
}

/**
 * Запуск анимации счетчика
 */
function triggerAnimation(): void {
  shouldAnimate.value = true;
  setTimeout(() => {
    shouldAnimate.value = false;
  }, 600);
}

/**
 * Форматирование больших чисел (31 -> 31, 1500 -> 1.5k)
 */
function formatCount(count: number): string {
  if (count >= 1000000) {
    return `${(count / 1000000).toFixed(1)}m`;
  } else if (count >= 1000) {
    return `${(count / 1000).toFixed(1)}k`;
  }
  return count.toString();
}

// ============================================================================
// WATCHERS
// ============================================================================

// Отслеживаем изменения в Store и обновляем локальное состояние
watch(
  () => store.favoritesCount,
  newCount => {
    if (newCount !== undefined && newCount !== localCount.value) {
      localCount.value = newCount;

      if (props.enableAnimation) {
        triggerAnimation();
      }
    }
  }
);

// ============================================================================
// EVENTS
// ============================================================================

interface Events {
  'counter-clicked': [payload: { currentCount: number; timestamp: string }];
  'counter-error': [payload: { error: string; timestamp: string }];
  'counter-updated': [payload: { oldCount: number; newCount: number; timestamp: string }];
}

const emit = defineEmits<Events>();

// ============================================================================
// LIFECYCLE
// ============================================================================

// При монтировании компонента устанавливаем начальное значение в Store
if (store.favoritesCount === undefined && props.initialCount > 0) {
  store.setFavoritesCount(props.initialCount);
}
</script>

<style scoped>
/* ============================================================================
   БАЗОВЫЕ СТИЛИ СЧЕТЧИКА
   ============================================================================ */

.favorites-counter {
  position: relative;
  cursor: pointer;
  transition: all 0.2s ease;
  user-select: none;
}

.favorites-counter:hover {
  background-color: rgba(61, 201, 138, 0.1);
  transform: scale(1.05);
}

.favorites-counter:active {
  transform: scale(0.95);
}

/* ============================================================================
   СОСТОЯНИЯ СЧЕТЧИКА
   ============================================================================ */

.favorites-counter--loading {
  pointer-events: none;
  opacity: 0.7;
}

.favorites-counter--animated {
  animation: pulse-green 0.6s ease-in-out;
}

/* ============================================================================
   ИНДИКАТОР ЗАГРУЗКИ
   ============================================================================ */

.favorites-counter__loader {
  position: absolute;
  top: 50%;
  right: -20px;
  transform: translateY(-50%);
  opacity: 0.8;
}

.favorites-counter__loader .spinner-border-sm {
  width: 14px;
  height: 14px;
  border-width: 2px;
  color: #3dc98a;
}

/* ============================================================================
   АНИМАЦИИ
   ============================================================================ */

@keyframes pulse-green {
  0% {
    box-shadow: 0 0 0 0 rgba(61, 201, 138, 0.7);
  }
  50% {
    box-shadow: 0 0 0 8px rgba(61, 201, 138, 0.2);
    background-color: rgba(61, 201, 138, 0.1);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(61, 201, 138, 0);
  }
}

/* Анимация смены значения */
.counter-update-enter-active,
.counter-update-leave-active {
  transition: all 0.3s ease;
}

.counter-update-enter-from {
  opacity: 0;
  transform: translateY(-10px) scale(0.8);
}

.counter-update-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(0.8);
}

/* ============================================================================
   АДАПТИВНОСТЬ
   ============================================================================ */

@media (max-width: 768px) {
  .favorites-counter {
    padding: 6px 8px;
    font-size: 11px;
  }

  .favorites-counter__loader {
    right: -15px;
  }

  .favorites-counter__loader .spinner-border-sm {
    width: 12px;
    height: 12px;
  }
}

/* ============================================================================
   ACCESSIBILITY
   ============================================================================ */

.favorites-counter:focus {
  outline: 2px solid #3dc98a;
  outline-offset: 2px;
}

@media (prefers-reduced-motion: reduce) {
  .favorites-counter,
  .counter-update-enter-active,
  .counter-update-leave-active {
    transition: none;
  }

  .favorites-counter--animated {
    animation: none;
  }
}
</style>
