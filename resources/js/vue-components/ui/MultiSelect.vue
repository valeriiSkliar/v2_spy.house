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
          <span v-for="value in values" :key="value" class="multi-select__tag">
            {{ value }}
            <button class="multi-select__remove" @click.stop="removeValue(value)">×</button>
          </span>
        </template>
      </div>

      <div class="multi-select__dropdown" v-show="isOpen">
        <div class="multi-select__search">
          <input
            type="text"
            placeholder="Search"
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
            <input type="checkbox" :checked="values.includes(option.value)" @click.stop />
            {{ option.label }}
          </li>
          <li v-if="filteredOptions.length === 0" class="multi-select__no-options">
            No options found
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
}

interface Props {
  values: string[];
  placeholder?: string;
  disabled?: boolean;
  options?: Option[];
}

interface Emits {
  (e: 'add', value: string): void;
  (e: 'remove', value: string): void;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select options',
  disabled: false,
  options: () => [
    { value: 'option1', label: 'Option 1' },
    { value: 'option2', label: 'Option 2' },
    { value: 'option3', label: 'Option 3' },
    { value: 'option4', label: 'Option 4' },
    { value: 'option5', label: 'Option 5' },
  ],
});

const emit = defineEmits<Emits>();

const isOpen = ref(false);
const searchQuery = ref('');
const multiSelectRef = ref<HTMLElement>();

const filteredOptions = computed(() => {
  if (!searchQuery.value) {
    return props.options;
  }

  return props.options.filter(option =>
    option.label.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

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
