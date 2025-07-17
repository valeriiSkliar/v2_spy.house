<template>
  <div class="base-select-array-adapter">
    <BaseSelect
      :value="currentValue"
      :options="props.options"
      :placeholder="props.placeholder"
      :icon="props.icon"
      :translations="props.translations"
      :disabled="props.disabled"
      @update:value="handleValueUpdate"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import BaseSelect from './BaseSelect.vue';

interface Option {
  value: string;
  label: string;
  logo?: string;
}

interface Props {
  /** Массив выбранных значений (совместимость с MultiSelect/store) */
  values: string[];
  /** Опции для выбора */
  options: Option[];
  /** Текст плейсхолдера */
  placeholder?: string;
  /** Иконка для BaseSelect */
  icon?: string;
  /** Переводы для BaseSelect */
  translations?: {
    noOptions: string;
    selectOption?: string;
  };
  /** Отключен ли компонент */
  disabled?: boolean;
}

interface Emits {
  /** Событие добавления элемента (совместимость с MultiSelect) */
  (e: 'add', value: string): void;
  /** Событие удаления элемента (совместимость с MultiSelect) */
  (e: 'remove', value: string): void;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select option',
  disabled: false,
  translations: () => ({
    noOptions: 'No options available',
    selectOption: 'Select option',
  }),
});

const emit = defineEmits<Emits>();

/**
 * Текущее значение для BaseSelect
 * Преобразует массив в строку (берет первый элемент)
 */
const currentValue = computed(() => {
  return props.values.length > 0 ? props.values[0] : '';
});

/**
 * Обработчик изменения значения в BaseSelect
 */
function handleValueUpdate(value: string): void {
  // Если выбрано пустое значение - очищаем массив
  if (value === '' || value === 'default') {
    // Удаляем все существующие элементы
    props.values.forEach(existingValue => {
      emit('remove', existingValue);
    });
    return;
  }

  // Если выбрано новое значение
  if (value !== currentValue.value) {
    // Удаляем старое значение (если есть)
    if (props.values.length > 0) {
      props.values.forEach(existingValue => {
        emit('remove', existingValue);
      });
    }
    
    // Добавляем новое значение
    emit('add', value);
  }
}
</script>

<style scoped>
/* Стили наследуются от BaseSelect */
</style>