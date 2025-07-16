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
          :class="{
            'is-selected': Number(option.value) === currentPerPageValue,
            'debug-option': true,
          }"
          @click="selectOption(option)"
        >
          {{ option.label }}
        </li>
        <li v-if="perPageOptions.length === 0" class="base-select__no-options">
          {{ translationsComputed.noOptions }}
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
    noOptions?: string;
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
    onPage: 'On page',
    perPage: 'Per page',
    noOptions: 'No options available',
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

console.log('📄 PerPageSelect: Initial setup', {
  propsInitialPerPage: props.initialPerPage,
  localPerPageValue: localPerPage.value,
  propsOptions: props.options,
  storeInitialized: store.isInitialized,
  storePerPage: store.filters.perPage,
});

// ============================================================================
// COMPUTED PROPERTIES
// ============================================================================

// Переводы с fallback значениями
const translationsComputed = computed(() => ({
  onPage: props.translations?.onPage || 'On page',
  perPage: props.translations?.perPage || 'Per page',
  noOptions: props.translations?.noOptions || 'No options available',
}));

const perPageOptions = computed(() => {
  const filtered = props.options.filter(option => option.value > 0);
  console.log('📄 PerPageSelect perPageOptions computed:', {
    allOptions: props.options,
    filteredOptions: filtered,
    currentValue: currentPerPageValue.value,
  });
  return filtered;
});

const displayValue = computed(() => {
  // Используем currentPerPageValue для консистентности
  const currentValue = currentPerPageValue.value;
  return `${translationsComputed.value.onPage} ${currentValue}`;
});

const isComponentReady = computed(() => {
  // Компонент готов если у нас есть либо локальное значение, либо Store инициализирован
  return (
    localPerPage.value !== undefined || (store.isInitialized && store.filters.perPage !== undefined)
  );
});

const currentPerPageValue = computed(() => {
  // Приоритет: Store > localPerPage > props.initialPerPage
  const result =
    store.isInitialized && store.filters.perPage !== undefined
      ? Number(store.filters.perPage)
      : Number(localPerPage.value);

  console.log('📄 PerPageSelect currentPerPageValue computed:', {
    result,
    storeInitialized: store.isInitialized,
    storePerPage: store.filters.perPage,
    localPerPage: localPerPage.value,
    propsInitialPerPage: props.initialPerPage,
  });

  return result;
});

// ============================================================================
// METHODS
// ============================================================================
function toggleDropdown(): void {
  isOpen.value = !isOpen.value;
  console.log('📄 PerPageSelect: Dropdown toggled', {
    isOpen: isOpen.value,
    currentValue: currentPerPageValue.value,
    options: perPageOptions.value.map(opt => ({
      value: opt.value,
      label: opt.label,
      isSelected: Number(opt.value) === currentPerPageValue.value,
    })),
  });
}

function selectOption(option: PerPageOption): void {
  const numericValue = Number(option.value);

  // Обновляем локальное значение
  localPerPage.value = numericValue;

  // Если store инициализирован, обновляем его (данные перезагрузятся автоматически через watcher)
  if (store.isInitialized) {
    store.updateFilter('perPage', numericValue);
  }

  // Закрываем dropdown
  isOpen.value = false;

  console.log('📄 PerPageSelect: Selected option', {
    value: option.value,
    numericValue,
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
    if (newValue !== oldValue && newValue !== undefined) {
      console.log('📄 PerPageSelect: Store perPage changed', {
        from: oldValue,
        to: newValue,
        localPerPage: localPerPage.value,
        storeInitialized: store.isInitialized,
      });

      // Синхронизируем локальное значение с Store только если Store инициализирован
      if (store.isInitialized) {
        const numericValue = Number(newValue);
        if (numericValue !== localPerPage.value) {
          console.log('📄 PerPageSelect: Syncing store value to local:', numericValue);
          localPerPage.value = numericValue;
        }
      }
    }
  },
  { immediate: true }
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

      // При инициализации Store, локальное значение имеет приоритет
      // потому что оно было установлено из URL параметров или props
      if (store.filters.perPage !== localPerPage.value) {
        console.log('📄 PerPageSelect: Syncing local perPage to store:', localPerPage.value);
        store.updateFilter('perPage', localPerPage.value);
      }
    }
  },
  { immediate: true }
);

// Удаляем дублирующий watcher - синхронизация теперь в первом watcher

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
    currentPerPageValue: currentPerPageValue.value,
  });

  // Проверяем какое значение выбрано по умолчанию
  perPageOptions.value.forEach(option => {
    const isSelected = Number(option.value) === currentPerPageValue.value;
    console.log(
      `📄 PerPageSelect: Option ${option.value} (${option.label}) - selected: ${isSelected}`
    );
  });

  // Если Store уже инициализирован при монтировании,
  // приоритет у локального значения (из URL или props)
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
