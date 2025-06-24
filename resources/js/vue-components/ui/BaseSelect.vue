<!-- resources/js/vue-components/ui/BaseSelect.vue -->
<template>
  <div class="base-select" ref="selectRef">
    <div class="base-select__trigger" @click="toggleDropdown">
      <span class="base-select__value">{{ selectedLabel || placeholder }}</span>
      <span class="base-select__arrow" :class="{ 'is-open': isOpen }"></span>
    </div>
    <ul class="base-select__dropdown" v-show="isOpen">
      <li
        v-for="option in safeOptions"
        :key="option.value"
        class="base-select__option"
        :class="{ 'is-selected': option.value === value }"
        @click="selectOption(option)"
      >
        {{ option.label }}
      </li>
      <li v-if="safeOptions.length === 0" class="base-select__no-options">No options available</li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';

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
  if (!Array.isArray(props.options)) {
    console.warn('BaseSelect: options prop must be an array, got:', typeof props.options);
    return '';
  }

  const selected = props.options.find(option => option.value === props.value);
  return selected?.label || '';
});

const safeOptions = computed(() => {
  return Array.isArray(props.options) ? props.options : [];
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
