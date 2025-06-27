<template>
  <div class="base-select-icon" ref="selectRef">
    <div class="base-select" :class="{ 'is-loading': !isComponentReady }">
      <div class="base-select__trigger" @click="toggleDropdown">
        <span class="base-select__value">{{ displayValue }}</span>
        <span class="base-select__arrow" :class="{ 'is-open': isOpen }"></span>
      </div>
      <ul class="base-select__dropdown" v-show="isOpen">
        <li
          v-for="option in perPageOptions"
          :key="option.value"
          :data-value="option.value"
          class="base-select__option"
          :class="{ 'is-selected': option.value === currentPerPageValue }"
          @click="selectOption(option)"
        >
          {{ option.label }}
        </li>
        <li v-if="perPageOptions.length === 0" class="base-select__no-options">
          No options available
        </li>
      </ul>
    </div>
    <span class="icon-list"></span>
  </div>
</template>

<script setup lang="ts">
import { useCreativesFiltersStore } from '@/stores/useFiltersStore';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

interface PerPageOption {
  value: number;
  label: string;
}

interface Props {
  options?: PerPageOption[];
  translations?: {
    onPage: string;
    perPage: string;
  };
  initialPerPage?: number;
}

const props = withDefaults(defineProps<Props>(), {
  options: () => [
    { value: 12, label: '12' },
    { value: 24, label: '24' },
    { value: 48, label: '48' },
    { value: 96, label: '96' },
  ],
  translations: () => ({
    onPage: 'На странице',
    perPage: 'Элементов на странице',
  }),
  initialPerPage: 12,
});

// ============================================================================
// STORE INTEGRATION
// ============================================================================
const store = useCreativesFiltersStore();

// ============================================================================
// LOCAL STATE
// ============================================================================
const isOpen = ref(false);
const selectRef = ref<HTMLElement>();

// Локальное состояние perPage до инициализации Store
const localPerPage = ref<number>(props.initialPerPage);

// ============================================================================
// COMPUTED PROPERTIES
// ============================================================================
const perPageOptions = computed(() => {
  return props.options.filter(option => option.value > 0);
});

const displayValue = computed(() => {
  // Используем значение из Store если он инициализирован, иначе локальное значение
  const currentValue = store.isInitialized
    ? store.filters.perPage ?? localPerPage.value
    : localPerPage.value;
  return `${props.translations.onPage} — ${currentValue}`;
});

const isComponentReady = computed(() => {
  // Компонент готов если у нас есть либо локальное значение, либо Store инициализирован
  return (
    localPerPage.value !== undefined || (store.isInitialized && store.filters.perPage !== undefined)
  );
});

const currentPerPageValue = computed(() => {
  return store.isInitialized ? store.filters.perPage ?? localPerPage.value : localPerPage.value;
});

// ============================================================================
// METHODS
// ============================================================================
function toggleDropdown(): void {
  isOpen.value = !isOpen.value;
}

function selectOption(option: PerPageOption): void {
  // Обновляем локальное значение
  localPerPage.value = option.value;

  // Если store инициализирован, обновляем его и перезагружаем данные
  if (store.isInitialized) {
    store.updateFilter('perPage', option.value);
    store.loadCreatives(1);
  }

  // Закрываем dropdown
  isOpen.value = false;

  console.log('📄 PerPageSelect: Selected option', {
    value: option.value,
    label: option.label,
    localPerPage: localPerPage.value,
    storePerPage: store.filters.perPage,
    storeInitialized: store.isInitialized,
  });
}

function closeDropdown(): void {
  isOpen.value = false;
}

function handleClickOutside(event: Event): void {
  if (selectRef.value && !selectRef.value.contains(event.target as Node)) {
    closeDropdown();
  }
}

// ============================================================================
// WATCHERS
// ============================================================================
// Следим за изменениями perPage в store (может быть изменено другими компонентами)
watch(
  () => store.filters.perPage,
  (newValue, oldValue) => {
    if (newValue !== oldValue) {
      console.log('📄 PerPageSelect: Store perPage changed', {
        from: oldValue,
        to: newValue,
      });
    }
  }
);

// Следим за инициализацией store
watch(
  () => store.isInitialized,
  isInitialized => {
    if (isInitialized) {
      console.log('📄 PerPageSelect: Store initialized', {
        storePerPage: store.filters.perPage,
        localPerPage: localPerPage.value,
        propsInitialPerPage: props.initialPerPage,
      });

      // Синхронизируем Store с локальным значением
      if (store.filters.perPage !== localPerPage.value) {
        console.log('📄 PerPageSelect: Syncing local perPage to store:', localPerPage.value);
        store.updateFilter('perPage', localPerPage.value);
      }
    }
  },
  { immediate: true }
);

// Следим за изменениями perPage в store и синхронизируем с локальным значением
watch(
  () => store.filters.perPage,
  newStoreValue => {
    if (
      store.isInitialized &&
      newStoreValue !== undefined &&
      newStoreValue !== localPerPage.value
    ) {
      console.log('📄 PerPageSelect: Syncing store perPage to local:', newStoreValue);
      localPerPage.value = newStoreValue;
    }
  }
);

// ============================================================================
// LIFECYCLE
// ============================================================================
onMounted(() => {
  document.addEventListener('click', handleClickOutside);

  console.log('📄 PerPageSelect: Component mounted', {
    storePerPage: store.filters.perPage,
    localPerPage: localPerPage.value,
    propsInitialPerPage: props.initialPerPage,
    isStoreInitialized: store.isInitialized,
    options: perPageOptions.value,
    translations: props.translations,
  });

  // Если Store уже инициализирован при монтировании, синхронизируем
  if (store.isInitialized && store.filters.perPage !== localPerPage.value) {
    console.log(
      '📄 PerPageSelect: Store already initialized, syncing local to store:',
      localPerPage.value
    );
    store.updateFilter('perPage', localPerPage.value);
  }
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped>
/* Стили уже определены в базовой теме, просто добавляем специфичные для PerPageSelect */
.base-select-icon {
  position: relative;
  display: flex;
  align-items: center;
  gap: 8px;
}

.base-select-icon .icon-list {
  pointer-events: none;
  color: var(--color-text-secondary, #666);
}

.base-select {
  min-width: 140px;
}

.base-select__value {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.base-select.is-loading {
  opacity: 0.7;
  pointer-events: none;
}

.base-select.is-loading .base-select__trigger {
  cursor: default;
}
</style>
