<template>
  <!-- Условный рендеринг: с иконкой или без -->
  <div v-if="icon" data-vue-test class="base-select-icon">
    <div class="base-select" ref="selectRef">
      <div class="base-select__trigger" @click="toggleDropdown">
        <span class="base-select__value">{{ displayValue }}</span>
        <span class="base-select__arrow" :class="{ 'is-open': isOpen }"></span>
      </div>
      <ul class="base-select__dropdown" v-show="isOpen">
        <li
          v-for="option in safeOptions"
          :key="option.value"
          :data-value="option.value"
          class="base-select__option"
          :class="{ 'is-selected': String(option.value) === String(value) }"
          @click="selectOption(option)"
        >
          <img v-if="option.logo" :src="option.logo" alt="" class="base-select__logo" />
          {{ option.label }}
        </li>
        <li v-if="safeOptions.length === 0" class="base-select__no-options">
          {{ translationsComputed.noOptions }}
        </li>
      </ul>
    </div>
    <span :class="`icon-${icon}`" data-vue-test></span>
  </div>

  <!-- Обычный вариант без иконки -->
  <div v-else class="base-select" ref="selectRef">
    <div class="base-select__trigger" @click="toggleDropdown">
      <span class="base-select__value">{{ selectedLabel || placeholder }}</span>
      <span class="base-select__arrow" :class="{ 'is-open': isOpen }"></span>
    </div>
    <ul class="base-select__dropdown" v-show="isOpen">
      <li
        v-for="option in safeOptions"
        :key="option.value"
        :data-value="option.value"
        class="base-select__option"
        :class="{ 'is-selected': String(option.value) === String(value) }"
        @click="selectOption(option)"
      >
        <div class="multi-select__option-logo" v-if="option.logo" >
          <img :src="option.logo" alt="Logo" />
        </div>
        {{ option.label }}
      </li>
      <li v-if="safeOptions.length === 0" class="base-select__no-options">
        {{ translationsComputed.noOptions }}
      </li>
    </ul>
  </div>
</template>

<style>
.base-select__no-options {
  padding: 10px;
  color: #666;
  text-align: center;
}
</style>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface Option {
  value: string;
  label: string;
  logo?: string;
}

interface OnPageTranslations {
  onPage: string;
  perPage: string;
}

interface Translations {
  noOptions: string;
  selectOption?: string;
}

interface Props {
  value: string | number;
  options: Option[];
  placeholder?: string;
  icon?: string;
  initialValue?: string | number;
  onPageTranslations?: OnPageTranslations;
  translations?: Translations;
}

interface Emits {
  (e: 'update:value', value: string): void;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select option',
  icon: undefined,
  initialValue: '12',
  onPageTranslations: () => ({
    onPage: 'On page',
    perPage: 'Per page',
  }),
  translations: () => ({
    noOptions: 'No options available',
    selectOption: 'Select option',
  }),
});

const emit = defineEmits<Emits>();

const isOpen = ref(false);
const selectRef = ref<HTMLElement>();

// Переводы с fallback значениями
const translationsComputed = computed(() => ({
  noOptions: props.translations?.noOptions || 'No options available',
  selectOption: props.translations?.selectOption || 'Select option',
  onPage: props.onPageTranslations?.onPage || 'On page',
  perPage: props.onPageTranslations?.perPage || 'Per page',
}));

const selectedLabel = computed(() => {
  if (!Array.isArray(props.options)) {
    console.warn('BaseSelect: options prop must be an array, got:', typeof props.options);
    return '';
  }

  const selected = props.options.find(option => String(option.value) === String(props.value));
  return selected?.label || '';
});

// Для варианта с иконкой используем специальный формат отображения как в Blade
const displayValue = computed(() => {
  if (props.icon) {
    const currentValue = props.value || props.initialValue;
    return `${translationsComputed.value.onPage} — ${currentValue}`;
  }
  return selectedLabel.value || translationsComputed.value.selectOption;
});

const safeOptions = computed(() => {
  const isValidArray = Array.isArray(props.options);
  const hasOptions = isValidArray && props.options.length > 0;

  // Only log when we have actual options to avoid race condition logging
  if (hasOptions) {
    console.log('🔍 BaseSelect safeOptions:', {
      options: props.options,
      length: props.options.length,
      firstOption: props.options[0],
      placeholder: props.placeholder,
    });
  }

  return isValidArray ? props.options.filter(option => option.value !== 'default') : [];
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
