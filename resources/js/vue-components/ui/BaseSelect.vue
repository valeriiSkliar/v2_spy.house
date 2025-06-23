<!-- resources/js/vue-components/ui/BaseSelect.vue -->
<template>
  <div class="base-select" ref="selectRef">
    <div class="base-select__trigger" @click="toggleDropdown">
      <span class="base-select__value">{{ selectedLabel || placeholder }}</span>
      <span class="base-select__arrow" :class="{ 'is-open': isOpen }"></span>
    </div>
    <ul class="base-select__dropdown" v-show="isOpen">
      <li
        v-for="option in options"
        :key="option.value"
        class="base-select__option"
        :class="{ 'is-selected': option.value === value }"
        @click="selectOption(option)"
      >
        {{ option.label }}
      </li>
    </ul>
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
  placeholder: 'Select option',
});

const emit = defineEmits<Emits>();

const isOpen = ref(false);
const selectRef = ref<HTMLElement>();

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
  if (selectRef.value && !selectRef.value.contains(event.target as Node)) {
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

<!-- <style scoped>
.base-select {
  position: relative;
}

.base-select__trigger {
  background: white;
  border: 1px solid #ddd;
  padding: 8px 12px;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-radius: 4px;
}

.base-select__trigger:hover {
  border-color: #999;
}

.base-select__arrow {
  transition: transform 0.2s ease;
}

.base-select__arrow.is-open {
  transform: rotate(180deg);
}

.base-select__dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #ddd;
  border-top: none;
  max-height: 200px;
  overflow-y: auto;
  z-index: 1000;
  list-style: none;
  margin: 0;
  padding: 0;
}

.base-select__option {
  padding: 8px 12px;
  cursor: pointer;
  border-bottom: 1px solid #f0f0f0;
}

.base-select__option:hover {
  background-color: #f5f5f5;
}

.base-select__option.is-selected {
  background-color: #e3f2fd;
  font-weight: 500;
}

.base-select__option:last-child {
  border-bottom: none;
}
</style> -->
