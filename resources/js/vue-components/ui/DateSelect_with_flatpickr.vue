<!-- 
DateSelect Component with Flatpickr Integration

Особенности:
- Календарь flatpickr отображается внутри выпадающего списка компонента
- Выпадающий список не закрывается при открытом календаре
- Календарь позиционируется относительно контейнера компонента

Usage Example:
<DateSelect
  v-model:value="selectedValue"
  :options="dateOptions"
  :enable-custom-date="true"
  :mode="'range'"
  :date-format="'Y-m-d'"
  :emit-on-partial-range="false"
  placeholder="Select date range"
  custom-date-label="Pick Custom Date"
  @custom-date-selected="handleCustomDate"
/>

Range Mode Behavior:
- emitOnPartialRange=false (default): События только при выборе обеих дат
- emitOnPartialRange=true: События при выборе каждой даты (включая первую)

Props:
- value: string - selected value
- options: Option[] - preset date options
- enableCustomDate: boolean - show custom date picker option
- mode: 'single' | 'range' - flatpickr mode
- dateFormat: string - date format for flatpickr
- minDate/maxDate: string | Date - date constraints
- locale: string - flatpickr locale
- emitOnPartialRange: boolean - emit events on partial range selection (default: false)
-->
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
            :class="{ active: option.value === value && !isCustomDate }"
            @click="selectOption(option)"
          >
            {{ option.label }}
          </button>
          <div class="custom-date-section" v-if="enableCustomDate">
            <button
              class="range-option custom-date-trigger"
              :class="{
                active: isCustomDate,
                'incomplete-range': hasIncompleteRange,
              }"
              @click="openCustomDatePicker"
            >
              {{ customDateLabel }}
              <span v-if="hasIncompleteRange" class="range-status">
                (выберите конечную дату{{
                  props.emitOnPartialRange ? '' : ' для применения фильтра'
                }})
              </span>
            </button>
            <input
              ref="flatpickrInput"
              type="text"
              class="flatpickr-input"
              :placeholder="customDatePlaceholder"
              style="display: none"
            />
          </div>
        </div>
        <!-- Контейнер для календаря flatpickr -->
        <div ref="flatpickrContainer" class="flatpickr-container"></div>
      </div>
    </div>
    <span class="icon-date"></span>
  </div>
</template>

<script setup lang="ts">
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

interface Option {
  value: string;
  label: string;
}

interface Props {
  value: string;
  options: Option[];
  placeholder?: string;
  enableCustomDate?: boolean;
  customDateLabel?: string;
  customDatePlaceholder?: string;
  dateFormat?: string;
  mode?: 'single' | 'range';
  minDate?: string | Date;
  maxDate?: string | Date;
  locale?: string;
  /**
   * Генерировать события при частичном выборе диапазона (только первая дата)
   * По умолчанию false - события только при завершении диапазона
   */
  emitOnPartialRange?: boolean;
}

interface Emits {
  (e: 'update:value', value: string): void;
  (e: 'custom-date-selected', dates: Date[]): void;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Select date',
  enableCustomDate: false,
  customDateLabel: 'Custom Date',
  customDatePlaceholder: 'Pick a date',
  dateFormat: 'd.m.Y',
  mode: 'single',
  emitOnPartialRange: false,
});

const emit = defineEmits<Emits>();

const isOpen = ref(false);
const isCustomDate = ref(false);
const isCalendarOpen = ref(false);
const dateSelectRef = ref<HTMLElement>();
const flatpickrInput = ref<HTMLInputElement>();
const flatpickrContainer = ref<HTMLElement>();
let flatpickrInstance: flatpickr.Instance | null = null;

const selectedLabel = computed(() => {
  // Если значение сброшено на дефолтное, показываем опцию из списка
  const isDefaultValue =
    !props.value ||
    props.value === props.placeholder ||
    props.options.some(option => option.value === props.value && option.label === props.value);

  if (!isDefaultValue && isCustomDate.value && flatpickrInstance?.selectedDates.length) {
    const dates = flatpickrInstance.selectedDates;
    if (props.mode === 'range') {
      if (dates.length === 2) {
        return `${formatDate(dates[0])} - ${formatDate(dates[1])}`;
      } else if (dates.length === 1) {
        return `${formatDate(dates[0])} - ...`; // Показываем промежуточное состояние
      }
    } else if (dates.length === 1) {
      return formatDate(dates[0]);
    }
  }

  // Проверяем, является ли значение custom датой
  if (props.value && props.value.startsWith('custom_') && isCustomDate.value) {
    const customPart = props.value.replace('custom_', '');
    if (customPart.includes('_to_')) {
      // Range date
      const [startStr, endStr] = customPart.split('_to_');
      const startDate = new Date(startStr);
      const endDate = new Date(endStr);
      if (!isNaN(startDate.getTime()) && !isNaN(endDate.getTime())) {
        return `${formatDate(startDate)} - ${formatDate(endDate)}`;
      }
    } else {
      // Single date
      const date = new Date(customPart);
      if (!isNaN(date.getTime())) {
        return formatDate(date);
      }
    }
  }

  const selected = props.options.find(option => option.value === props.value);
  return selected?.label || '';
});

const hasIncompleteRange = computed(() => {
  return (
    props.mode === 'range' && isCustomDate.value && flatpickrInstance?.selectedDates.length === 1
  );
});

function formatDate(date: Date): string {
  // D.M.YYYY — без ведущих нулей
  const day = date.getDate();
  const month = date.getMonth() + 1;
  const year = date.getFullYear();
  return `${day}.${month}.${year}`;
}

function toggleDropdown(): void {
  // Если закрываем dropdown, очищаем календарь
  if (isOpen.value) {
    hideCalendar();
  }
  isOpen.value = !isOpen.value;
}

function selectOption(option: Option): void {
  isCustomDate.value = false;
  hideCalendar(); // Скрываем календарь при выборе обычной опции
  emit('update:value', option.value);
  isOpen.value = false;
}

function openCustomDatePicker(): void {
  isCustomDate.value = true;
  isCalendarOpen.value = true;
  // НЕ закрываем dropdown, оставляем открытым для календаря

  nextTick(() => {
    // Инициализируем flatpickr если еще не инициализирован
    if (!flatpickrInstance) {
      initializeFlatpickr();

      // Ждем инициализации и показываем календарь
      setTimeout(() => {
        if (flatpickrInstance) {
          flatpickrInstance.open();
          forceShowCalendar();
        }
      }, 200);
    } else {
      // Открываем календарь стандартным способом
      flatpickrInstance.open();

      // Принудительно показываем если не отобразился
      setTimeout(() => {
        forceShowCalendar();
      }, 100);
    }
  });
}

function closeDropdown(): void {
  // Не закрываем dropdown если:
  // - открыт календарь
  // - выбрана только одна дата в range режиме
  if (!isCalendarOpen.value && !hasIncompleteRange.value) {
    isOpen.value = false;
    hideCalendar(); // Скрываем календарь при закрытии dropdown
    destroyFlatpickr();
  }
}

function handleClickOutside(event: Event): void {
  if (dateSelectRef.value && !dateSelectRef.value.contains(event.target as Node)) {
    // Проверяем, что клик не по календарю flatpickr
    const flatpickrCalendar = document.querySelector('.flatpickr-calendar');
    if (!flatpickrCalendar || !flatpickrCalendar.contains(event.target as Node)) {
      // Если есть незавершенный range, не закрываем dropdown
      if (!hasIncompleteRange.value) {
        hideCalendar();
        closeDropdown();
        if (flatpickrInstance) {
          flatpickrInstance.close();
          destroyFlatpickr();
        }
      }
    }
  }
}

function initializeFlatpickr(): void {
  if (!flatpickrInput.value || !props.enableCustomDate) return;

  // Уничтожаем предыдущий экземпляр если есть
  destroyFlatpickr();

  const config: flatpickr.Options.Options = {
    dateFormat: props.dateFormat,
    mode: props.mode,
    ...(props.locale && { locale: props.locale as any }),
    minDate: props.minDate,
    maxDate: props.maxDate,
    appendTo: flatpickrContainer.value || undefined, // Вставляем календарь в контейнер если он есть
    inline: false, // Календарь как popup
    allowInput: false, // Запрещаем ввод в поле
    clickOpens: false, // Открываем программно
    onChange: (selectedDates: Date[]) => {
      if (selectedDates.length > 0) {
        isCustomDate.value = true;

        // Определяем, нужно ли генерировать события
        const shouldEmitEvents =
          props.mode === 'single' || // В single режиме всегда генерируем
          (props.mode === 'range' && selectedDates.length === 2) || // Range завершен
          (props.mode === 'range' && selectedDates.length === 1 && props.emitOnPartialRange); // Частичный range с разрешением

        console.log('DateSelect onChange:', {
          mode: props.mode,
          selectedCount: selectedDates.length,
          emitOnPartialRange: props.emitOnPartialRange,
          shouldEmitEvents,
          dates: selectedDates.map(d => d.toISOString().split('T')[0]),
        });

        if (shouldEmitEvents) {
          emit('custom-date-selected', selectedDates);

          // Create custom value for the selected date(s)
          let customValue: string;
          if (props.mode === 'range' && selectedDates.length === 2) {
            customValue = selectedDates.map(date => date.toISOString().split('T')[0]).join('_to_');
          } else if (props.mode === 'range' && selectedDates.length === 1) {
            // Для частичного range (только если emitOnPartialRange=true)
            customValue = selectedDates[0].toISOString().split('T')[0];
          } else {
            // Single mode
            customValue = selectedDates[0].toISOString().split('T')[0];
          }

          emit('update:value', `custom_${customValue}`);
        }

        // Закрываем календарь только если:
        // - режим single и выбрана одна дата
        // - режим range и выбраны обе даты
        const shouldClose =
          (props.mode === 'single' && selectedDates.length === 1) ||
          (props.mode === 'range' && selectedDates.length === 2);

        if (shouldClose) {
          setTimeout(() => {
            if (flatpickrInstance) {
              flatpickrInstance.close();
            }
            hideCalendar();
            isOpen.value = false; // Закрываем весь dropdown
          }, 100);
        }
      }
    },
    onOpen: () => {
      isCalendarOpen.value = true;
    },
    onClose: () => {
      hideCalendar();
      isOpen.value = false; // Закрываем dropdown когда закрывается календарь
    },
    onReady: () => {
      // Если есть контейнер, перемещаем календарь туда после инициализации
      if (flatpickrContainer.value && flatpickrInstance) {
        const calendar = flatpickrInstance.calendarContainer;
        if (calendar && calendar.parentNode !== flatpickrContainer.value) {
          flatpickrContainer.value.appendChild(calendar);
        }
      }

      // Проверяем начальное значение и синхронизируем состояние
      if (props.value && props.value.startsWith('custom_')) {
        syncFromPropValue(props.value);
      }
    },
  };

  try {
    flatpickrInstance = flatpickr(flatpickrInput.value, config);
  } catch (error) {
    console.error('Failed to initialize flatpickr:', error);
  }
}

function destroyFlatpickr(): void {
  if (flatpickrInstance) {
    try {
      hideCalendar(); // Скрываем календарь перед уничтожением
      flatpickrInstance.destroy();
    } catch (error) {
      console.error('Error destroying flatpickr:', error);
    } finally {
      flatpickrInstance = null;
    }
  }

  // Дополнительная очистка контейнера
  if (flatpickrContainer.value) {
    flatpickrContainer.value.innerHTML = '';
  }
}

function forceShowCalendar(): void {
  if (flatpickrInstance && flatpickrContainer.value) {
    const calendar = flatpickrInstance.calendarContainer;
    if (calendar) {
      // Принудительно показываем календарь
      calendar.classList.add('open');
      calendar.style.display = 'block';
      calendar.style.position = 'static';

      // Перемещаем в контейнер если нужно
      if (calendar.parentNode !== flatpickrContainer.value) {
        flatpickrContainer.value.appendChild(calendar);
      }
    }
  }
}

function hideCalendar(): void {
  if (flatpickrInstance) {
    const calendar = flatpickrInstance.calendarContainer;
    if (calendar) {
      // Скрываем календарь
      calendar.classList.remove('open');
      calendar.style.display = 'none';

      // Удаляем из контейнера
      if (calendar.parentNode && flatpickrContainer.value?.contains(calendar)) {
        calendar.parentNode.removeChild(calendar);
      }
    }
  }
  isCalendarOpen.value = false;
}

// Отслеживание изменений value для сброса состояния календаря
watch(
  () => props.value,
  (newValue, oldValue) => {
    // Если значение изменилось на дефолтное (сброс фильтров)
    const isDefaultValue =
      !newValue ||
      newValue === props.placeholder ||
      props.options.some(option => option.value === newValue && option.label === newValue);
    const isFromCustom = oldValue && oldValue.startsWith('custom_');

    if (isDefaultValue && isFromCustom) {
      // Сбрасываем состояние кастомной даты
      isCustomDate.value = false;

      // Очищаем календарь
      if (flatpickrInstance) {
        try {
          flatpickrInstance.clear();
        } catch (error) {
          console.error('Error clearing flatpickr:', error);
        }
        hideCalendar();
      }
    }

    // Если значение изменилось на обычную опцию, также сбрасываем кастомное состояние
    if (newValue && !newValue.startsWith('custom_') && isCustomDate.value) {
      isCustomDate.value = false;

      if (flatpickrInstance) {
        try {
          flatpickrInstance.clear();
        } catch (error) {
          console.error('Error clearing flatpickr:', error);
        }
        hideCalendar();
      }
    }

    // Если новое значение является кастомной датой, восстанавливаем состояние
    if (newValue && newValue.startsWith('custom_') && !isCustomDate.value) {
      syncFromPropValue(newValue);
    }
  },
  { immediate: true } // Запускаем watcher сразу при инициализации
);

/**
 * Синхронизирует состояние компонента с пропом value для кастомных дат
 */
function syncFromPropValue(value: string): void {
  if (!value || !value.startsWith('custom_')) {
    return;
  }

  // Устанавливаем флаг кастомной даты
  isCustomDate.value = true;

  // Парсим даты из значения
  const customPart = value.replace('custom_', '');
  const dates: Date[] = [];

  if (customPart.includes('_to_')) {
    // Range date
    const [startStr, endStr] = customPart.split('_to_');
    const startDate = new Date(startStr);
    const endDate = new Date(endStr);

    if (!isNaN(startDate.getTime()) && !isNaN(endDate.getTime())) {
      dates.push(startDate, endDate);
    }
  } else {
    // Single date
    const date = new Date(customPart);
    if (!isNaN(date.getTime())) {
      dates.push(date);
    }
  }

  // Синхронизируем с flatpickr если он инициализирован
  if (flatpickrInstance && dates.length > 0) {
    try {
      // Устанавливаем даты в flatpickr
      flatpickrInstance.setDate(dates, false); // false чтобы не вызывать onChange
    } catch (error) {
      console.error('Error syncing dates with flatpickr:', error);
    }
  } else if (dates.length > 0) {
    // Если flatpickr еще не инициализирован, сохраняем даты для последующей синхронизации
    setTimeout(() => {
      if (flatpickrInstance) {
        try {
          flatpickrInstance.setDate(dates, false);
        } catch (error) {
          console.error('Error syncing dates with flatpickr (delayed):', error);
        }
      }
    }, 200);
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);

  // Инициализируем flatpickr после монтирования с небольшой задержкой
  if (props.enableCustomDate) {
    nextTick(() => {
      setTimeout(() => {
        initializeFlatpickr();
      }, 100);
    });
  }
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  destroyFlatpickr();
});
</script>

<style lang="scss">
@use 'sass:color';
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
  border: 1px solid #ddd;
  background: #fafafa;
  padding: 8px 12px;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-radius: 10px;

  span {
    font-size: 14px;
    font-weight: 500;
    margin-left: 10px;
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
  top: 115%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #ddd;
  z-index: 1000;
  border-radius: 10px;
  // max-height: 400px; // Ограничиваем максимальную высоту
  // overflow: hidden; // Скрываем переполнение
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
  &:active {
    background-color: var(--color-primary-light, rgba(59, 130, 246, 0.2));
    color: var(--color-primary);
  }
  &:hover {
    background-color: var(--color-primary-light, rgba(59, 130, 246, 0.1));
  }
  &:focus {
    outline: 1px solid var(--color-primary);
  }
}

.range-option.active {
  background-color: var(--color-primary-light, rgba(59, 130, 246, 0.2));
  color: var(--color-primary);
}

.range-option:last-child {
  margin-bottom: 0;
}

.custom-date-section {
  border-top: 1px solid #eee;
  margin-top: 4px;
  padding-top: 4px;
}

.custom-date-trigger {
  font-style: italic;
  color: #666;
}

.custom-date-trigger.active {
  color: #333;
  font-style: normal;
}

.custom-date-trigger.incomplete-range {
  background-color: #fff3cd;
  border-color: #ffeaa7;
  color: #856404;

  .range-status {
    font-size: 12px;
    font-weight: normal;
    color: #856404;
  }
}

.flatpickr-input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.flatpickr-container {
  position: relative;

  // Контейнер сжимается когда пустой
  &:empty {
    height: 0;
    min-height: 0;
    padding: 0;
    margin: 0;
  }

  // Стили для календаря внутри контейнера
  :deep(.flatpickr-calendar) {
    position: static !important;
    display: block !important;
    top: auto !important;
    left: auto !important;
    right: auto !important;
    bottom: auto !important;
    border-top: 1px solid #eee;
    margin-top: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    border-radius: 10px;
    width: 100%;

    &.open {
      display: block !important;
      opacity: 1;
      visibility: visible;
    }

    // Скрытый календарь
    &:not(.open) {
      display: none !important;
      height: 0;
      overflow: hidden;
    }

    &.animate {
      animation: none !important;
      transform: none !important;
    }

    &.arrowTop,
    &.arrowLeft {
      &:before,
      &:after {
        display: none !important;
      }
    }
  }
}

// Глобальные стили для календаря при необходимости
:global(.flatpickr-calendar) {
  &.inline {
    position: static !important;
    display: block !important;
  }

  // Принудительное скрытие календаря когда он не открыт
  &:not(.open) {
    display: none !important;
    height: 0 !important;
    min-height: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
    border: none !important;
  }
}

.icon-date {
  margin-left: 8px;
  color: #666;
  font-size: 16px;
}
</style>
