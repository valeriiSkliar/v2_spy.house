<!-- resources/js/vue-components/ui/DateSelect.vue -->
<template>
  <div class="filter-date-select" ref="dateSelectRef">
    <div class="date-picker-container">
      <div class="date-select-field" role="button" :aria-expanded="isOpen" @click="toggleDropdown">
        <span>{{ selectedLabel || placeholder }}</span>
        <span class="dropdown-arrow" :class="{ 'is-open': isOpen }"></span>
      </div>
      <div class="date-options-dropdown" v-show="isOpen">
        <div class="preset-ranges">
          <button
            v-for="option in options"
            :key="option.value"
            class="range-option"
            :class="{ active: option.value === value }"
            @click="selectOption(option)"
          >
            {{ option.label }}
          </button>
        </div>
      </div>
    </div>
    <span class="icon-date"></span>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';

interface Option {
  value: string;
  label: string;
}

interface Props {
  value: string;
  options: Option[];
  placeholder?: string;
}

interface Emits {
  (e: 'update:value', value: string): void;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select date',
});

const emit = defineEmits<Emits>();

const isOpen = ref(false);
const dateSelectRef = ref<HTMLElement>();

const selectedLabel = computed(() => {
  const selected = props.options.find(option => option.value === props.value);
  return selected?.label || '';
});

function toggleDropdown(): void {
  isOpen.value = !isOpen.value;
}

function selectOption(option: Option): void {
  emit('update:value', option.value);
  isOpen.value = false;
}

function closeDropdown(): void {
  isOpen.value = false;
}

function handleClickOutside(event: Event): void {
  if (dateSelectRef.value && !dateSelectRef.value.contains(event.target as Node)) {
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

<style lang="scss">
.filter-date-select {
  position: relative;
  display: flex;
  align-items: center;
}

.date-picker-container {
  flex: 1;
  position: relative;
}

.date-select-field {
  background: white;
  border: 1px solid #ddd;
  padding: 8px 12px;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-radius: 4px;

  span {
    font-size: 14px;
    font-weight: 500;
  }
}

.date-select-field:hover {
  border-color: #999;
}

.dropdown-arrow {
  transition: transform 0.2s ease;
}

.dropdown-arrow.is-open {
  transform: rotate(180deg);
}

.date-options-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #ddd;
  border-top: none;
  z-index: 1000;
  border-radius: 0 0 4px 4px;
}

.preset-ranges {
  padding: 8px;
}

.range-option {
  display: block;
  width: 100%;
  padding: 6px 8px;
  border: none;
  background: none;
  text-align: left;
  cursor: pointer;
  border-radius: 3px;
  margin-bottom: 2px;
}

.range-option:hover {
  background-color: #f5f5f5;
}

.range-option.active {
  background-color: #e3f2fd;
  font-weight: 500;
}

.range-option:last-child {
  margin-bottom: 0;
}

.icon-date {
  margin-left: 8px;
  color: #666;
  font-size: 16px;
}
</style>
