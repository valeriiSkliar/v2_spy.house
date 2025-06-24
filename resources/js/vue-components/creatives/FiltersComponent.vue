<!-- resources/js/vue-components/FiltersComponent.vue -->
<template>
  <div class="filter">
    <!-- Мобильный триггер -->
    <div class="filter__trigger-mobile" @click="toggleMobileFilters">
      <span class="btn-icon _dark _big _filter">
        <span class="icon-filter"></span>
        <span class="icon-up font-24" :class="{ rotated: isMobileFiltersOpen }"></span>
      </span>
      {{ getTranslation('filter', 'Filter') }}
    </div>

    <!-- Основной контент фильтров -->
    <div class="filter__content" :class="{ 'mobile-hidden': !isMobileFiltersOpen }">
      <div class="row align-items-end">
        <!-- Кнопка детальных фильтров (десктоп) -->
        <div class="col-12 col-md-auto mb-10 d-none d-md-block">
          <button
            class="btn-icon _dark _big _filter js-toggle-detailed-filtering"
            @click="initStore().toggleDetailedFilters()"
          >
            <span class="icon-filter"></span>
            <span
              class="icon-up font-24"
              :class="{ rotated: initStore().filters.isDetailedVisible }"
            ></span>
          </button>
        </div>

        <div class="col-12 col-md-auto flex-grow-1 w-md-1">
          <div class="row">
            <!-- Поиск по ключевым словам -->
            <div class="col-12 col-lg-4 mb-10">
              <div class="form-search">
                <span class="icon-search"></span>
                <input
                  type="search"
                  :placeholder="getTranslation('searchKeyword', 'Search by Keyword')"
                  :value="localSearchKeyword"
                  @input="handleSearchInput"
                />
              </div>
            </div>

            <!-- Выбор страны/категории -->
            <div class="col-12 col-md-6 col-lg-3 mb-10 w-lg-1 flex-grow-1">
              <BaseSelect
                :value="initStore().filters.country"
                :options="initStore().countryOptions"
                :placeholder="getTranslation('country', 'Country')"
                @update:value="initStore().setCountry($event)"
              />
            </div>

            <!-- Выбор даты создания -->
            <div class="col-12 col-md-6 col-lg-3 mb-10 w-lg-1 flex-grow-1">
              <!-- <DateSelect
                :value="initStore().filters.dateCreation"
                :options="initStore().dateRanges"
                placeholder="Date of creation"
                @update:value="initStore().setDateCreation($event)"
              /> -->
              <DateSelect
                v-model:value="initStore().filters.dateCreation"
                :options="initStore().dateRanges"
                :enable-custom-date="true"
                :mode="'range'"
                :date-format="'d-m-Y'"
                :placeholder="getTranslation('dateCreation', 'Date of creation')"
                custom-date-label="Pick Custom Date"
                @custom-date-selected="handleCustomDateSelected"
              />
            </div>

            <!-- Сортировка -->
            <div class="col-12 col-md-12 col-lg-3 mb-10 w-lg-1 flex-grow-1">
              <BaseSelect
                :value="initStore().filters.sortBy"
                :options="initStore().sortOptions"
                :placeholder="getTranslation('sortBy', 'Sort by')"
                @update:value="initStore().setSortBy($event)"
              />
            </div>
          </div>
        </div>

        <!-- Кнопка сброса (десктоп) -->
        <div class="col-12 col-md-auto mb-10 d-none d-md-block">
          <div class="reset-btn">
            <button class="btn-icon" @click="handleResetFilters()">
              <span class="icon-clear"></span>
              <span class="ml-2 d-md-none">Reset</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Детальные фильтры -->
      <div class="filter__detailed" v-show="initStore().filters.isDetailedVisible">
        <div class="filter__title">
          {{ getTranslation('isDetailedVisible', 'Detailed filtering') }}
        </div>
        <div class="row">
          <!-- Период отображения -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <DateSelect
              :value="initStore().filters.periodDisplay"
              :options="initStore().dateRanges"
              :placeholder="getTranslation('periodDisplay', 'Period of display')"
              @update:value="initStore().setPeriodDisplay($event)"
            />
          </div>

          <!-- Рекламные сети -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <MultiSelect
              :values="initStore().filters.advertisingNetworks"
              :options="getMultiSelectOptions('advertisingNetworks')"
              :placeholder="getTranslation('advertisingNetworks', 'Advertising networks')"
              @add="initStore().addToMultiSelect('advertisingNetworks', $event)"
              @remove="initStore().removeFromMultiSelect('advertisingNetworks', $event)"
            />
          </div>

          <!-- Языки -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <MultiSelect
              :values="initStore().filters.languages"
              :options="getMultiSelectOptions('languages')"
              :placeholder="getTranslation('languages', 'Languages')"
              @add="initStore().addToMultiSelect('languages', $event)"
              @remove="initStore().removeFromMultiSelect('languages', $event)"
            />
          </div>

          <!-- Операционные системы -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <MultiSelect
              :values="initStore().filters.operatingSystems"
              :options="getMultiSelectOptions('operatingSystems')"
              :placeholder="getTranslation('operatingSystems', 'Operation systems')"
              @add="initStore().addToMultiSelect('operatingSystems', $event)"
              @remove="initStore().removeFromMultiSelect('operatingSystems', $event)"
            />
          </div>

          <!-- Браузеры -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <MultiSelect
              :values="initStore().filters.browsers"
              :options="getMultiSelectOptions('browsers')"
              :placeholder="getTranslation('browsers', 'Browsers')"
              @add="initStore().addToMultiSelect('browsers', $event)"
              @remove="initStore().removeFromMultiSelect('browsers', $event)"
            />
          </div>

          <!-- Устройства -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <MultiSelect
              :values="initStore().filters.devices"
              :options="getMultiSelectOptions('devices')"
              :placeholder="getTranslation('devices', 'Devices')"
              @add="initStore().addToMultiSelect('devices', $event)"
              @remove="initStore().removeFromMultiSelect('devices', $event)"
            />
          </div>

          <!-- Размеры изображений -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <MultiSelect
              :values="initStore().filters.imageSizes"
              :options="getMultiSelectOptions('imageSizes')"
              :placeholder="getTranslation('imageSizes', 'Image sizes')"
              @add="initStore().addToMultiSelect('imageSizes', $event)"
              @remove="initStore().removeFromMultiSelect('imageSizes', $event)"
            />
          </div>

          <!-- Только для взрослых -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <label class="checkbox-toggle _with-background">
              <span class="icon-18 font-20"></span>
              <span class="mr-auto">{{ getTranslation('onlyAdult', 'Only adult') }}</span>
              <input
                type="checkbox"
                id="adult"
                :checked="initStore().filters.onlyAdult"
                @change="initStore().toggleAdultFilter()"
              />
              <span class="checkbox-toggle-visible"></span>
            </label>
          </div>

          <!-- Сохраненные настройки -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <MultiSelect
              :values="initStore().filters.savedSettings"
              :options="[]"
              :placeholder="getTranslation('savedSettings', 'Saved settings')"
              @add="initStore().addToMultiSelect('savedSettings', $event)"
              @remove="initStore().removeFromMultiSelect('savedSettings', $event)"
            />
          </div>

          <!-- Кнопка сохранения настроек -->
          <div class="col-12 col-md-auto mb-10">
            <button class="btn _flex _dark _medium w-100" @click="initStore().saveSettings()">
              <span class="icon-save mr-2 font-16"></span>
              {{ getTranslation('savePresetButton', 'Save settings') }}
            </button>
          </div>
        </div>

        <!-- Кнопка сброса (мобильный) -->
        <div class="reset-btn d-md-none">
          <button class="btn-icon" @click="handleResetFilters()">
            <span class="icon-clear"></span>
            <span class="ml-2">{{ getTranslation('resetButton', 'Reset') }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- URL Sync Status (только в development режиме) -->
    <div v-if="isDevelopment && enableUrlSync" class="url-sync-status">✅ URL Sync Active</div>

    <!-- Loading indicator когда опции ещё загружаются -->
    <div v-if="!isComponentReady" class="options-loading">
      <div class="loading-spinner"></div>
      <span>Loading filter options...</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { FilterState } from '@/types/creatives';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useFiltersStore } from '../../stores/creatives';
import BaseSelect from '../ui/BaseSelect.vue';
import DateSelect from '../ui/DateSelect_with_flatpickr.vue';
import MultiSelect from '../ui/MultiSelect.vue';

// Простая debounce функция
function debounce<T extends (...args: any[]) => any>(
  func: T,
  wait: number
): T & { cancel: () => void } {
  let timeout: NodeJS.Timeout | null = null;

  const debounced = ((...args: Parameters<T>) => {
    if (timeout) clearTimeout(timeout);
    timeout = setTimeout(() => func(...args), wait);
  }) as T & { cancel: () => void };

  debounced.cancel = () => {
    if (timeout) {
      clearTimeout(timeout);
      timeout = null;
    }
  };

  return debounced;
}

interface Props {
  initialFilters?: Partial<FilterState>;
  selectOptions?: any;
  translations?: Record<string, string>;
  enableUrlSync?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  initialFilters: () => ({}),
  selectOptions: () => ({}),
  translations: () => ({}),
  enableUrlSync: true,
});

// Store instance
let storeInstance: ReturnType<typeof useFiltersStore> | null = null;

// Development mode check
const isDevelopment = computed(() => import.meta.env.DEV);

// Локальное состояние для поля поиска с дебаунсом
const localSearchKeyword = ref('');

// Создаем debounced функцию с помощью lodash
const debouncedUpdateSearch = debounce((value: string) => {
  const store = initStore();
  console.log('Updating search keyword in store:', value);
  store.setSearchKeyword(value);
}, 300);

// Инициализация store
function initStore() {
  if (storeInstance) return storeInstance;

  storeInstance = useFiltersStore();
  console.log('Filters store создан');
  return storeInstance;
}

console.log('FiltersComponent props:', props.initialFilters, props.selectOptions);

// Проверка загрузки опций от сервера
const isOptionsLoaded = computed(() => {
  const store = initStore();
  return (
    store.countryOptions.length > 1 || // Больше одной дефолтной опции
    store.sortOptions.length > 1 ||
    store.dateRanges.length > 1
  );
});

// Проверка готовности компонента для отображения
const isComponentReady = computed(() => {
  return isOptionsLoaded.value;
});

// Функция для получения переводов с fallback
function getTranslation(key: string, fallback: string = key): string {
  const store = initStore();
  return store.getTranslation(key, fallback);
}

// Локальное состояние для мобильного интерфейса
const isMobileFiltersOpen = ref(false);

function toggleMobileFilters(): void {
  isMobileFiltersOpen.value = !isMobileFiltersOpen.value;
}

function handleCustomDateSelected(dates: Date[]): void {
  if (dates.length > 0) {
    const dateString = dates[0].toISOString().split('T')[0];
    initStore().setDateCreation(dateString);
  }
}

// Получение опций для мультиселектов
function getMultiSelectOptions(field: string): Array<{ value: string; label: string }> {
  const store = initStore();

  // Получаем опции из computed свойств store
  switch (field) {
    case 'advertisingNetworks':
      return store.advertisingNetworksOptions;
    case 'languages':
      return store.languagesOptions;
    case 'operatingSystems':
      return store.operatingSystemsOptions;
    case 'browsers':
      return store.browsersOptions;
    case 'devices':
      return store.devicesOptions;
    case 'imageSizes':
      return store.imageSizesOptions;
    default:
      return [];
  }
}

// Обработчик ввода в поле поиска с debouncedом
function handleSearchInput(event: Event): void {
  const target = event.target as HTMLInputElement;
  const value = target.value;

  // Немедленно обновляем локальное состояние для отображения
  localSearchKeyword.value = value;

  // Используем debounced функцию для обновления store
  debouncedUpdateSearch(value);
}

// Синхронизация локального состояния с store при изменениях извне
function syncLocalSearchWithStore(): void {
  const store = initStore();

  // Инициализируем локальное значение текущим значением из store
  localSearchKeyword.value = store.filters.searchKeyword;
}

// Кастомный resetFilters с синхронизацией локального поиска
function handleResetFilters(): void {
  const store = initStore();

  // Отменяем pending debounced вызовы
  debouncedUpdateSearch.cancel();

  // Сбрасываем фильтры в store
  store.resetFilters();

  // Синхронизируем локальное состояние поиска
  localSearchKeyword.value = store.filters.searchKeyword;
}

// Обработчик изменения размера экрана
function handleResize(): void {
  if (window.innerWidth >= 768) {
    isMobileFiltersOpen.value = false;
  }
}

onMounted(async () => {
  console.log('FiltersComponent props:', props);

  // 1. Инициализируем store с унифицированным подходом
  // Приоритет: URL → Props → Defaults
  const store = initStore();

  console.log('Initializing filters with unified approach...');
  store.initializeFilters(props.initialFilters, props.selectOptions, props.translations);

  console.log('Filters store инициализирован:', store.filters);

  // 2. Настраиваем синхронизацию поля поиска
  syncLocalSearchWithStore();

  // 3. Настраиваем наблюдение за изменениями store будет через events

  // Эмитим событие готовности компонента
  const event = new CustomEvent('vue-component-ready', {
    detail: {
      component: 'CreativesFiltersComponent',
      props: props,
      filters: store.filters,
      urlSyncEnabled: props.enableUrlSync,
      timestamp: new Date().toISOString(),
    },
  });
  document.dispatchEvent(event);

  // Добавляем обработчик resize
  window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
  // Отменяем pending debounced вызовы при размонтировании
  debouncedUpdateSearch.cancel();

  window.removeEventListener('resize', handleResize);
});
</script>

<style scoped>
.mobile-hidden {
  display: none;
}

@media (min-width: 768px) {
  .mobile-hidden {
    display: block;
  }
}

.icon-up.rotated {
  transform: rotate(180deg);
  transition: transform 0.3s ease;
}

.icon-up {
  transition: transform 0.3s ease;
}

/* Индикатор URL синхронизации для отладки */
.url-sync-status {
  position: fixed;
  top: 10px;
  right: 10px;
  background: #4ade80;
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  z-index: 9999;
}

/* Индикатор загрузки опций */
.options-loading {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px;
  background: #f8f9fa;
  border-radius: 4px;
  margin-top: 10px;
  color: #6c757d;
  font-size: 14px;
}

.loading-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid #e9ecef;
  border-top-color: #007bff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}
</style>
