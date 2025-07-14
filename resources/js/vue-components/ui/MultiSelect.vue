<!-- resources/js/vue-components/ui/MultiSelect.vue -->
<template>
  <div class="filter-section">
    <div class="multi-select" :disabled="disabled" ref="multiSelectRef">
      <div
        class="multi-select__tags"
        :class="{ 'is-empty': values.length === 0, 'is-open': isOpen }"
        @click="toggleDropdown"
      >
        <template v-if="values.length === 0">
          <span class="multi-select__placeholder">{{ placeholder }}</span>
        </template>
        <template v-else>
          <span class="multi-select__selected-text">{{ displayText }}</span>
        </template>
      </div>

      <div class="multi-select__dropdown" v-show="isOpen">
        <div class="multi-select__actions">
          <button
            type="button"
            v-if="isSelectAllVisible"
            class="multi-select__action-btn multi-select__action-btn--select-all"
            @click.stop="selectAll"
          >
            {{ translations.selectAll }}
          </button>
          <button
            type="button"
            class="multi-select__action-btn multi-select__action-btn--clear-all"
            @click.stop="clearAll"
          >
            {{ translations.clearAll }}
          </button>
        </div>

        <div class="multi-select__search">
          <input
            type="text"
            :placeholder="translations.search"
            class="multi-select__search-input"
            v-model="searchQuery"
            @click.stop
          />
        </div>
        <ul class="multi-select__options">
          <li
            v-for="option in filteredOptions"
            :key="option.value"
            class="multi-select__option"
            :class="{ 'is-selected': values.includes(option.value) }"
            @click="toggleOption(option.value)"
          >
            <input
              v-if="option.value !== 'default'"
              type="checkbox"
              :checked="values.includes(option.value)"
              @click.stop
            />
            <div class="multi-select__option-logo" v-if="showLogo">
              <img :src="option.logo" alt="Logo" />
            </div>
            <span class="multi-select__option-label">{{ option.label }}</span>
          </li>
          <li v-if="filteredOptions.length === 0" class="multi-select__no-options">
            {{ translations.noOptionsFound }}
          </li>
        </ul>
      </div>

      <span class="multi-select__arrow" :class="{ 'is-open': isOpen }"></span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Option {
  value: string;
  label: string;
  logo?: string;
}

interface Props {
  values: string[];
  placeholder?: string;
  disabled?: boolean;
  options?: Option[];
  showLogo?: boolean;
  isSelectAllVisible?: boolean;
  translations?: {
    selectAll?: string;
    clearAll?: string;
    noOptionsFound?: string;
    search?: string;
    selectedItems?: string;
  };
}

interface Emits {
  (e: 'add', value: string): void;
  (e: 'remove', value: string): void;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select options',
  disabled: false,
  options: () => [{ value: 'default', label: 'Select options' }],
  showLogo: false,
  isSelectAllVisible: false,
  translations: () => ({
    selectAll: 'Select All',
    clearAll: 'Clear All',
    noOptionsFound: 'No options found',
    search: 'Search',
    selectedItems: 'selected items',
  }),
});

const emit = defineEmits<Emits>();

const isOpen = ref(false);
const searchQuery = ref('');
const multiSelectRef = ref<HTMLElement>();

// Переводы с fallback значениями
const translations = computed(() => ({
  selectAll: props.translations?.selectAll || 'Select All',
  clearAll: props.translations?.clearAll || 'Clear All',
  noOptionsFound: props.translations?.noOptionsFound || 'No options found',
  search: props.translations?.search || 'Search',
  selectedItems: props.translations?.selectedItems || 'selected items',
}));

const filteredOptions = computed(() => {
  if (!searchQuery.value) {
    return props.options;
  }

  return props.options.filter(option =>
    option.label.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

const displayText = computed(() => {
  if (props.values.length === 1) {
    return getLabelByValue(props.values[0]);
  } else if (props.values.length > 1) {
    return `${props.values.length} ${translations.value.selectedItems}`;
  }
  return '';
});

function getLabelByValue(value: string): string {
  const option = props.options.find(option => option.value === value);
  return option ? option.label : value;
}

function toggleDropdown(): void {
  if (!props.disabled) {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
      searchQuery.value = '';
    }
  }
}

function toggleOption(value: string): void {
  if (props.values.includes(value)) {
    emit('remove', value);
  } else {
    emit('add', value);
  }
}

function selectAll(): void {
  const availableOptions = props.options.filter(option => option.value !== 'default');

  availableOptions.forEach(option => {
    if (!props.values.includes(option.value)) {
      emit('add', option.value);
    }
  });
}

function clearAll(): void {
  props.values.forEach(value => {
    emit('remove', value);
  });
}

function removeValue(value: string): void {
  emit('remove', value);
}

function closeDropdown(): void {
  isOpen.value = false;
}

function handleClickOutside(event: Event): void {
  if (multiSelectRef.value && !multiSelectRef.value.contains(event.target as Node)) {
    closeDropdown();
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>

<style scoped lang="scss">
@use '@scss/custom/creatives/multi-select-creative.scss';
</style>
