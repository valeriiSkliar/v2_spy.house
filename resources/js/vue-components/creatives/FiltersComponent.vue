<template>
  <div class="filter">
    <!-- Мобильный триггер -->
    <div class="filter__trigger-mobile" @click="toggleMobileFilters">
      <span class="btn-icon _dark _big _filter">
        <span class="icon-filter"></span>
        <span class="icon-up font-24" :class="{ rotated: isMobileFiltersOpen }"></span>
      </span>
      {{ store.getTranslation('filter', 'Filter') }}
    </div>

    <!-- Основной контент фильтров -->
    <div class="filter__content" :class="{ 'mobile-hidden': !isMobileFiltersOpen }">
      <div class="row align-items-end">
        <!-- Кнопка детальных фильтров (десктоп) -->
        <div class="col-12 col-md-auto mb-10 d-none d-md-block">
          <button
            class="btn-icon _dark _big _filter js-toggle-detailed-filtering"
            @click="store.toggleDetailedFilters()"
          >
            <span class="icon-filter"></span>
            <span
              class="icon-up font-24"
              :class="{ rotated: store.filters.isDetailedVisible }"
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
                  :placeholder="store.getTranslation('searchKeyword', 'Search by Keyword')"
                  :value="localSearchKeyword"
                  @input="handleSearchInput"
                />
              </div>
            </div>

            <!-- Выбор страны/категории -->
            <div class="col-12 col-md-6 col-lg-3 mb-10 w-lg-1 flex-grow-1">
              <BaseSelect
                :value="store.filters.country"
                :options="store.countryOptions"
                :placeholder="store.getTranslation('country', 'Country')"
                @update:value="value => store.updateFilter('country', value)"
              />
            </div>

            <!-- Выбор даты создания -->
            <div class="col-12 col-md-6 col-lg-3 mb-10 w-lg-1 flex-grow-1">
              <DateSelect
                v-model:value="store.filters.dateCreation"
                :options="store.dateRanges"
                :enable-custom-date="true"
                :mode="'range'"
                :date-format="'d-m-Y'"
                :placeholder="store.getTranslation('dateCreation', 'Date of creation')"
                :custom-date-label="store.getTranslation('customDateLabel', 'Custom Date')"
                @update:value="value => store.updateFilter('dateCreation', value)"
                @custom-date-selected="handleDateCreationSelected"
              />
            </div>

            <!-- Сортировка -->
            <div class="col-12 col-md-12 col-lg-3 mb-10 w-lg-1 flex-grow-1">
              <BaseSelect
                :value="store.filters.sortBy"
                :options="store.sortOptions"
                :placeholder="store.getTranslation('sortBy', 'Sort by')"
                @update:value="value => store.updateFilter('sortBy', value)"
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
      <div class="filter__detailed" v-show="store.filters.isDetailedVisible">
        <div class="filter__title">
          {{ store.getTranslation('isDetailedVisible', 'Detailed filtering') }}
        </div>
        <div class="row">
          <!-- Период отображения -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <DateSelect
              v-model:value="store.filters.periodDisplay"
              :options="store.dateRanges"
              :enable-custom-date="true"
              :mode="'range'"
              :date-format="'d-m-Y'"
              :custom-date-label="store.getTranslation('customDateLabel', 'Custom Date')"
              :placeholder="store.getTranslation('periodDisplay', 'Period of display')"
              @update:value="value => store.updateFilter('periodDisplay', value)"
              @custom-date-selected="handlePeriodDisplaySelected"
            />
          </div>

          <!-- Рекламные сети -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <MultiSelect
              :show-logo="true"
              :values="store.filters.advertisingNetworks"
              :options="store.advertisingNetworksOptions"
              :placeholder="store.getTranslation('advertisingNetworks', 'Advertising networks')"
              @add="value => store.addToMultiSelect('advertisingNetworks', value)"
              @remove="value => store.removeFromMultiSelect('advertisingNetworks', value)"
            />
          </div>

          <!-- Языки -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <MultiSelect
              :values="store.filters.languages"
              :options="store.languagesOptions"
              :placeholder="store.getTranslation('languages', 'Languages')"
              @add="value => store.addToMultiSelect('languages', value)"
              @remove="value => store.removeFromMultiSelect('languages', value)"
            />
          </div>

          <!-- Операционные системы -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <MultiSelect
              :values="store.filters.operatingSystems"
              :options="store.operatingSystemsOptions"
              :placeholder="store.getTranslation('operatingSystems', 'Operation systems')"
              @add="value => store.addToMultiSelect('operatingSystems', value)"
              @remove="value => store.removeFromMultiSelect('operatingSystems', value)"
            />
          </div>

          <!-- Браузеры -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <MultiSelect
              :values="store.filters.browsers"
              :options="store.browsersOptions"
              :placeholder="store.getTranslation('browsers', 'Browsers')"
              @add="value => store.addToMultiSelect('browsers', value)"
              @remove="value => store.removeFromMultiSelect('browsers', value)"
            />
          </div>

          <!-- Устройства -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <MultiSelect
              :values="store.filters.devices"
              :options="store.devicesOptions"
              :placeholder="store.getTranslation('devices', 'Devices')"
              @add="value => store.addToMultiSelect('devices', value)"
              @remove="value => store.removeFromMultiSelect('devices', value)"
            />
          </div>

          <!-- Размеры изображений -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <MultiSelect
              :values="store.filters.imageSizes"
              :options="store.imageSizesOptions"
              :placeholder="store.getTranslation('imageSizes', 'Image sizes')"
              @add="value => store.addToMultiSelect('imageSizes', value)"
              @remove="value => store.removeFromMultiSelect('imageSizes', value)"
            />
          </div>

          <!-- Только для взрослых -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <label class="checkbox-toggle _with-background">
              <span class="icon-18 font-20"></span>
              <span class="mr-auto">{{ store.getTranslation('onlyAdult', 'Only adult') }}</span>
              <input
                type="checkbox"
                id="adult"
                :checked="store.filters.onlyAdult"
                @change="store.toggleAdultFilter()"
              />
              <span class="checkbox-toggle-visible"></span>
            </label>
          </div>

          <!-- Сохраненные настройки -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <BaseSelect
              :value="
                store.filters.savedSettings.length > 0 ? store.filters.savedSettings[0] : 'default'
              "
              :options="[]"
              :placeholder="store.getTranslation('savedSettings', 'Saved settings')"
              @update:value="() => {}"
            />
          </div>

          <!-- Кнопка сохранения настроек -->
          <div class="col-12 col-md-auto mb-10">
            <button class="btn _flex _dark _medium w-100" @click="store.saveSettings()">
              <span class="icon-save mr-2 font-16"></span>
              {{ store.getTranslation('saveSettings', 'Save settings') }}
            </button>
          </div>
        </div>

        <!-- Кнопка сброса (мобильная) -->
        <div class="reset-btn d-md-none">
          <button class="btn-icon" @click="handleResetFilters()">
            <span class="icon-clear"></span>
            <span class="ml-2 d-md-none">Reset</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { FilterState, SelectOptions, TabOptions } from '@/types/creatives';
import debounce from 'lodash.debounce';
import { onMounted, onUnmounted, ref } from 'vue';
import { useCreativesFiltersStore } from '../../stores/useFiltersStore';
import BaseSelect from '../ui/BaseSelect.vue';
import DateSelect from '../ui/DateSelect_with_flatpickr.vue';
import MultiSelect from '../ui/MultiSelect.vue';

// ============================================================================
// ИНТЕРФЕЙСЫ И PROPS
// ============================================================================

interface Props {
  initialFilters?: Partial<FilterState>;
  selectOptions?: Partial<SelectOptions>;
  translations?: Record<string, string>;
  tabOptions?: Partial<TabOptions>;
  enableUrlSync?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  initialFilters: () => ({}),
  selectOptions: () => ({}),
  translations: () => ({}),
  tabOptions: () => ({}),
  enableUrlSync: true,
});

// ============================================================================
// СОСТОЯНИЕ И STORE
// ============================================================================

// Основной store с новыми композаблами
const store = useCreativesFiltersStore();

// Локальное состояние компонента
const isMobileFiltersOpen = ref(false);
const localSearchKeyword = ref('');
const isComponentReady = ref(false);

// ============================================================================
// ОБРАБОТЧИКИ СОБЫТИЙ
// ============================================================================

/**
 * Обработчик ввода в поле поиска с debounce
 */
const debouncedUpdateSearch = debounce((value: string) => {
  store.updateFilter('searchKeyword', value);
}, 300);

/**
 * Обработчик изменения поля поиска
 */
function handleSearchInput(event: Event): void {
  const target = event.target as HTMLInputElement;
  const value = target.value;

  localSearchKeyword.value = value;
  debouncedUpdateSearch(value);
}

/**
 * Обработчик выбора пользовательской даты создания
 */
function handleDateCreationSelected(dates: Date[]): void {
  if (dates.length >= 2) {
    const from = dates[0].toLocaleDateString('en-GB'); // dd/mm/yyyy format
    const to = dates[1].toLocaleDateString('en-GB');
    const customValue = `${from}_${to}`;
    store.updateFilter('dateCreation', customValue);
  }
}

/**
 * Обработчик выбора пользовательского периода отображения
 */
function handlePeriodDisplaySelected(dates: Date[]): void {
  if (dates.length >= 2) {
    const from = dates[0].toLocaleDateString('en-GB'); // dd/mm/yyyy format
    const to = dates[1].toLocaleDateString('en-GB');
    const customValue = `${from}_${to}`;
    store.updateFilter('periodDisplay', customValue);
  }
}

/**
 * Обработчик сброса фильтров
 */
function handleResetFilters(): void {
  // Сбрасываем локальное состояние поиска
  localSearchKeyword.value = '';

  // Сбрасываем фильтры в store
  store.resetFilters();

  // Эмитим событие для обратной совместимости
  emitFiltersReset();
}

/**
 * Переключение мобильных фильтров
 */
function toggleMobileFilters(): void {
  isMobileFiltersOpen.value = !isMobileFiltersOpen.value;
}

/**
 * Синхронизация локального поля поиска с store
 */
function syncLocalSearchWithStore(): void {
  // Устанавливаем начальное значение из store
  localSearchKeyword.value = store.filters.searchKeyword || '';

  // При программном изменении searchKeyword в store обновляем локальное значение
  // Это предотвращает рассинхронизацию при загрузке из URL
}

/**
 * Обработчик изменения размера экрана
 */
function handleResize(): void {
  if (window.innerWidth >= 768) {
    isMobileFiltersOpen.value = false;
  }
}

// ============================================================================
// СОБЫТИЯ ДЛЯ ОБРАТНОЙ СОВМЕСТИМОСТИ
// ============================================================================

/**
 * Эмитит событие готовности компонента
 */
function emitComponentReady(): void {
  const event = new CustomEvent('vue-component-ready', {
    detail: {
      component: 'CreativesFiltersComponent',
      props: props,
      filters: store.filters,
      urlSyncEnabled: props.enableUrlSync,
      hasActiveFilters: store.hasActiveFilters,
      timestamp: new Date().toISOString(),
    },
  });
  document.dispatchEvent(event);
}

/**
 * Эмитит событие сброса фильтров
 */
function emitFiltersReset(): void {
  const event = new CustomEvent('creatives:filters-reset', {
    detail: {
      source: 'user',
      timestamp: new Date().toISOString(),
    },
  });
  document.dispatchEvent(event);
}

/**
 * Эмитит событие изменения фильтров
 */
function emitFiltersChanged(): void {
  const event = new CustomEvent('creatives:filters-changed', {
    detail: {
      filters: store.filters,
      hasActiveFilters: store.hasActiveFilters,
      activeFiltersCount: store.activeFiltersCount,
      source: 'user',
      timestamp: new Date().toISOString(),
    },
  });
  document.dispatchEvent(event);
}

// ============================================================================
// LIFECYCLE HOOKS
// ============================================================================

onMounted(async () => {
  console.log('FiltersComponent mounting with new store integration...');

  try {
    // 1. FiltersComponent всегда выполняет полную инициализацию store с selectOptions
    // Даже если TabsComponent уже установил свои опции - это не проблема
    console.log('🚀 FiltersComponent performing full store initialization...');
    await store.initializeFilters(
      props.initialFilters,
      props.selectOptions,
      props.translations,
      props.tabOptions
    );

    console.log('Store initialized with composables:', {
      filters: store.filters,
      isInitialized: store.isInitialized,
      urlSyncEnabled: props.enableUrlSync,
    });

    // 2. Синхронизируем локальное поле поиска
    syncLocalSearchWithStore();

    // 3. Настраиваем обработчики
    window.addEventListener('resize', handleResize);

    // 4. Эмитим событие готовности
    emitComponentReady();

    isComponentReady.value = true;

    console.log('FiltersComponent successfully mounted with new composables integration');
  } catch (error) {
    console.error('Error initializing FiltersComponent:', error);

    // Эмитим событие ошибки
    const errorEvent = new CustomEvent('vue-component-error', {
      detail: {
        component: 'CreativesFiltersComponent',
        error: error,
        timestamp: new Date().toISOString(),
      },
    });
    document.dispatchEvent(errorEvent);
  }
});

onUnmounted(() => {
  // Отменяем pending debounced вызовы
  debouncedUpdateSearch.cancel();

  // Очищаем обработчики
  window.removeEventListener('resize', handleResize);

  console.log('FiltersComponent unmounted');
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
.url-sync-indicator {
  position: fixed;
  top: 10px;
  right: 10px;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  z-index: 9999;
  pointer-events: none;
}
</style>
