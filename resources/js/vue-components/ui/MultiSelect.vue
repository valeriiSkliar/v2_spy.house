<!-- resources/js/vue-components/ui/MultiSelect.vue -->
<template>
  <div class="filter-section">
    <div class="multi-select" :disabled="disabled" ref="multiSelectRef">
      <div
        class="multi-select__tags"
        :class="{ 'is-empty': values.length === 0 }"
        @click="toggleDropdown"
      >
        <template v-if="values.length === 0">
          <span class="multi-select__placeholder">{{ placeholder }}</span>
        </template>
        <template v-else>
          <span v-for="value in values" :key="value" class="multi-select__tag">
            {{ value }}
            <button class="multi-select__tag-remove" @click.stop="removeValue(value)">×</button>
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
import { ref, computed, onMounted, onUnmounted } from 'vue';

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

<style scoped>
.multi-select {
  position: relative;
  cursor: pointer;
}

.multi-select[disabled='true'] {
  cursor: not-allowed;
  opacity: 0.6;
}

.multi-select__tags {
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 4px 8px;
  min-height: 38px;
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  align-items: center;
}

.multi-select__tags:hover {
  border-color: #999;
}

.multi-select__tags.is-empty {
  color: #999;
}

.multi-select__placeholder {
  color: #999;
}

.multi-select__tag {
  background: #e3f2fd;
  border: 1px solid #90caf9;
  border-radius: 3px;
  padding: 2px 6px;
  font-size: 12px;
  display: flex;
  align-items: center;
  gap: 4px;
}

.multi-select__tag-remove {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  line-height: 1;
  font-size: 14px;
  color: #666;
}

.multi-select__tag-remove:hover {
  color: #000;
}

.multi-select__dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #ddd;
  border-top: none;
  z-index: 1000;
  max-height: 200px;
  overflow-y: auto;
}

.multi-select__search {
  padding: 8px;
  border-bottom: 1px solid #eee;
}

.multi-select__search-input {
  width: 100%;
  border: 1px solid #ddd;
  border-radius: 3px;
  padding: 4px 8px;
  outline: none;
}

.multi-select__search-input:focus {
  border-color: #90caf9;
}

.multi-select__options {
  list-style: none;
  margin: 0;
  padding: 0;
}

.multi-select__option {
  padding: 8px 12px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  border-bottom: 1px solid #f0f0f0;
}

.multi-select__option:hover {
  background-color: #f5f5f5;
}

.multi-select__option.is-selected {
  background-color: #e3f2fd;
}

.multi-select__option:last-child {
  border-bottom: none;
}

.multi-select__no-options {
  padding: 8px 12px;
  color: #999;
  text-align: center;
  font-style: italic;
}

.multi-select__arrow {
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  transition: transform 0.2s ease;
  pointer-events: none;
}

.multi-select__arrow.is-open {
  transform: translateY(-50%) rotate(180deg);
}

.multi-select__arrow::before {
  content: '▼';
  font-size: 10px;
  color: #666;
}
</style>
