<!-- 
DateSelect Component with Flatpickr Integration

Usage Example:
<DateSelect
  v-model:value="selectedValue"
  :options="dateOptions"
  :enable-custom-date="true"
  :mode="'single'"
  :date-format="'Y-m-d'"
  placeholder="Select date range"
  custom-date-label="Pick Custom Date"
  @custom-date-selected="handleCustomDate"
/>

Props:
- value: string - selected value
- options: Option[] - preset date options
- enableCustomDate: boolean - show custom date picker option
- mode: 'single' | 'range' - flatpickr mode
- dateFormat: string - date format for flatpickr
- minDate/maxDate: string | Date - date constraints
- locale: string - flatpickr locale
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
              :class="{ active: isCustomDate }"
              @click="openCustomDatePicker"
            >
              {{ customDateLabel }}
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
      </div>
    </div>
    <span class="icon-date"></span>
  </div>
</template>

<script setup lang="ts">
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.css';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';

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
  dateFormat: 'Y-m-d',
  mode: 'single',
});

const emit = defineEmits<Emits>();

const isOpen = ref(false);
const isCustomDate = ref(false);
const dateSelectRef = ref<HTMLElement>();
const flatpickrInput = ref<HTMLInputElement>();
let flatpickrInstance: flatpickr.Instance | null = null;

const selectedLabel = computed(() => {
  if (isCustomDate.value && flatpickrInstance?.selectedDates.length) {
    const dates = flatpickrInstance.selectedDates;
    if (props.mode === 'range' && dates.length === 2) {
      return `${formatDate(dates[0])} - ${formatDate(dates[1])}`;
    } else if (dates.length === 1) {
      return formatDate(dates[0]);
    }
  }

  const selected = props.options.find(option => option.value === props.value);
  return selected?.label || '';
});

function formatDate(date: Date): string {
  return date.toLocaleDateString();
}

function toggleDropdown(): void {
  isOpen.value = !isOpen.value;
}

function selectOption(option: Option): void {
  isCustomDate.value = false;
  emit('update:value', option.value);
  isOpen.value = false;
}

function openCustomDatePicker(): void {
  isCustomDate.value = true;
  isOpen.value = false;

  nextTick(() => {
    if (flatpickrInstance) {
      flatpickrInstance.open();
    }
  });
}

function closeDropdown(): void {
  isOpen.value = false;
}

function handleClickOutside(event: Event): void {
  if (dateSelectRef.value && !dateSelectRef.value.contains(event.target as Node)) {
    closeDropdown();
  }
}

function initializeFlatpickr(): void {
  if (!flatpickrInput.value || !props.enableCustomDate) return;

  const config: flatpickr.Options.Options = {
    dateFormat: props.dateFormat,
    mode: props.mode,
    ...(props.locale && { locale: props.locale as any }),
    minDate: props.minDate,
    maxDate: props.maxDate,
    onChange: (selectedDates: Date[]) => {
      if (selectedDates.length > 0) {
        isCustomDate.value = true;
        emit('custom-date-selected', selectedDates);

        // Create custom value for the selected date(s)
        const customValue = selectedDates
          .map(date => date.toISOString().split('T')[0])
          .join('_to_');

        emit('update:value', `custom_${customValue}`);
      }
    },
    onClose: () => {
      // Optional: handle close event
    },
  };

  flatpickrInstance = flatpickr(flatpickrInput.value, config);
}

function destroyFlatpickr(): void {
  if (flatpickrInstance) {
    flatpickrInstance.destroy();
    flatpickrInstance = null;
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  initializeFlatpickr();
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  destroyFlatpickr();
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

.flatpickr-input {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.icon-date {
  margin-left: 8px;
  color: #666;
  font-size: 16px;
}

// Flatpickr calendar positioning
:global(.flatpickr-calendar) {
  z-index: 1001 !important;
}
</style>
