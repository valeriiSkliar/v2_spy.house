<template>
  <div class="filter">
    <!-- Мобильный триггер -->
    <div class="filter__trigger-mobile" @click="toggleMobileFilters">
      <span class="btn-icon _dark _big _filter">
        <span class="icon-filter"></span>
        <span class="icon-up font-24" :class="{ rotated: isMobileFiltersOpen }"></span>
      </span>
      {{ translations.title.value }}
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
                  :placeholder="translations.searchKeyword.value"
                  :value="localSearchKeyword"
                  @input="handleSearchInput"
                />
              </div>
            </div>

            <!-- Выбор стран (BaseSelect с поддержкой массивов) -->
            <div class="col-12 col-md-6 col-lg-3 mb-10 w-lg-1 flex-grow-1">
              <BaseSelectArrayAdapter
                :values="store.filters.countries"
                :options="store.countriesOptions"
                :placeholder="translations.countries.value"
                :translations="baseSelectTranslations"
                @add="value => store.addToMultiSelect('countries', value)"
                @remove="value => store.removeFromMultiSelect('countries', value)"
              />
            </div>

            <!-- Выбор даты создания -->
            <div class="col-12 col-md-6 col-lg-3 mb-10 w-lg-1 flex-grow-1">
              <DateSelect
                v-model:value="store.filters.dateCreation"
                :options="store.dateRanges"
                :enable-custom-date="true"
                :mode="'range'"
                :date-format="'d.m.Y'"
                :placeholder="translations.dateCreation.value"
                :custom-date-label="translations.customDateLabel.value"
                @update:value="value => store.updateFilter('dateCreation', value)"
                @custom-date-selected="handleDateCreationSelected"
              />
            </div>

            <!-- Сортировка -->
            <div class="col-12 col-md-12 col-lg-3 mb-10 w-lg-1 flex-grow-1">
              <BaseSelect
                :value="store.filters.sortBy"
                :options="store.sortOptions"
                :placeholder="translations.sortBy.value"
                :translations="baseSelectTranslations"
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
              <span class="ml-2 d-md-none">{{ translations.resetButton.value }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Детальные фильтры -->
      <div class="filter__detailed" v-show="store.filters.isDetailedVisible">
        <div class="filter__title">
          {{ translations.isDetailedVisible.value }}
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
              :custom-date-label="translations.customDateLabel.value"
              :placeholder="translations.periodDisplay.value"
              @update:value="value => store.updateFilter('periodDisplay', value)"
              @custom-date-selected="handlePeriodDisplaySelected"
            />
          </div>

          <!-- Рекламные сети -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <BaseSelectArrayAdapter
              :values="store.filters.advertisingNetworks"
              :options="store.advertisingNetworksOptions"
              :placeholder="translations.advertisingNetworks.value"
              :translations="baseSelectTranslations"
              @add="value => store.addToMultiSelect('advertisingNetworks', value)"
              @remove="value => store.removeFromMultiSelect('advertisingNetworks', value)"
            />
          </div>

          <!-- Языки -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <BaseSelectArrayAdapter
              :values="store.filters.languages"
              :options="store.languagesOptions"
              :placeholder="translations.languages.value"
              :translations="baseSelectTranslations"
              @add="value => store.addToMultiSelect('languages', value)"
              @remove="value => store.removeFromMultiSelect('languages', value)"
            />
          </div>

          <!-- Операционные системы -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <BaseSelectArrayAdapter
              :values="store.filters.operatingSystems"
              :options="store.operatingSystemsOptions"
              :placeholder="translations.operatingSystems.value"
              :translations="baseSelectTranslations"
              @add="value => store.addToMultiSelect('operatingSystems', value)"
              @remove="value => store.removeFromMultiSelect('operatingSystems', value)"
            />
          </div>

          <!-- Браузеры -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <BaseSelectArrayAdapter
              :values="store.filters.browsers"
              :options="store.browsersOptions"
              :placeholder="translations.browsers.value"
              :translations="baseSelectTranslations"
              @add="value => store.addToMultiSelect('browsers', value)"
              @remove="value => store.removeFromMultiSelect('browsers', value)"
            />
          </div>

          <!-- Устройства -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <BaseSelectArrayAdapter
              :values="store.filters.devices"
              :options="store.devicesOptions"
              :placeholder="translations.devices.value"
              :translations="baseSelectTranslations"
              @add="value => store.addToMultiSelect('devices', value)"
              @remove="value => store.removeFromMultiSelect('devices', value)"
            />
          </div>

          <!-- Размеры изображений -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <BaseSelectArrayAdapter
              :values="store.filters.imageSizes"
              :options="store.imageSizesOptions"
              :placeholder="translations.imageSizes.value"
              :translations="baseSelectTranslations"
              @add="value => store.addToMultiSelect('imageSizes', value)"
              @remove="value => store.removeFromMultiSelect('imageSizes', value)"
            />
          </div>

          <!-- Только для взрослых -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <label class="checkbox-toggle _with-background">
              <span class="icon-18 font-20"></span>
              <span class="mr-auto">{{ translations.onlyAdult.value }}</span>
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
              :value="store.selectedPresetId?.toString() || 'default'"
              :options="store.presetOptions"
              :placeholder="translations.savedSettings.value"
              :disabled="store.isPresetsLoading"
              :translations="baseSelectTranslations"
              @update:value="handlePresetSelection"
            />
          </div>

          <!-- Кнопка сохранения настроек -->
          <div class="col-12 col-md-auto mb-10">
            <button class="btn _flex _dark _medium w-100" @click="store.saveSettings()">
              <span class="icon-save mr-2 font-16"></span>
              {{ translations.savePresetButton.value }}
            </button>
          </div>
        </div>

        <!-- Кнопка сброса (мобильная) -->
        <div class="reset-btn d-md-none">
          <button class="btn-icon" @click="handleResetFilters()">
            <span class="icon-clear"></span>
            <span class="ml-2 d-md-none">{{ translations.resetButton.value }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {
  createReactiveTranslations,
  mergePropsTranslations,
  useTranslations,
} from '@/composables/useTranslations';
import type { FilterState, SelectOptions, TabOptions } from '@/types/creatives.d';
import { CREATIVES_CONSTANTS } from '@/types/creatives.d';
import debounce from 'lodash.debounce';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useCreativesFiltersStore } from '../../stores/useFiltersStore';
import BaseSelect from '../ui/BaseSelect.vue';
import BaseSelectArrayAdapter from '../ui/BaseSelectArrayAdapter.vue';
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
// ПЕРЕВОДЫ И STORE
// ============================================================================

// Основной store с новыми композаблами
const store = useCreativesFiltersStore();

// Новая система переводов с защитой от race condition
const { t, isReady, waitForReady } = useTranslations();

// Reactive переводы для UI элементов
const translations = createReactiveTranslations(
  {
    title: 'title',
    searchKeyword: 'searchKeyword',
    countries: 'countries',
    dateCreation: 'dateCreation',
    sortBy: 'sortBy',
    resetButton: 'resetButton',
    isDetailedVisible: 'isDetailedVisible',
    customDateLabel: 'customDateLabel',
    periodDisplay: 'periodDisplay',
    advertisingNetworks: 'advertisingNetworks',
    languages: 'languages',
    operatingSystems: 'operatingSystems',
    browsers: 'browsers',
    devices: 'devices',
    imageSizes: 'imageSizes',
    onlyAdult: 'onlyAdult',
    savedSettings: 'savedSettings',
    savePresetButton: 'savePresetButton',
    // Переводы для MultiSelect
    multiSelectAll: 'multiSelect.selectAll',
    multiClearAll: 'multiSelect.clearAll',
    multiNoOptionsFound: 'multiSelect.noOptionsFound',
    multiSearch: 'multiSelect.search',
    multiSelectedItems: 'multiSelect.selectedItems',
    // Переводы для BaseSelect
    baseSelectOption: 'baseSelect.selectOption',
    baseNoOptionsAvailable: 'baseSelect.noOptionsAvailable',
    baseOnPage: 'baseSelect.onPage',
    basePerPage: 'baseSelect.perPage',
  },
  {
    // Fallback значения для критических переводов
    title: 'Filter',
    searchKeyword: 'Search by Keyword',
    countries: 'Countries',
    dateCreation: 'Date of creation',
    sortBy: 'Sort by',
    resetButton: 'Reset',
    isDetailedVisible: 'Detailed filtering',
    customDateLabel: 'Custom Date',
    periodDisplay: 'Period of display',
    advertisingNetworks: 'Advertising networks',
    languages: 'Languages',
    operatingSystems: 'Operation systems',
    browsers: 'Browsers',
    devices: 'Devices',
    imageSizes: 'Image sizes',
    onlyAdult: 'Only adult',
    savedSettings: 'Saved settings',
    savePresetButton: 'Save settings',
    // Fallback для MultiSelect
    multiSelectAll: 'Select All',
    multiClearAll: 'Clear All',
    multiNoOptionsFound: 'No options found',
    multiSearch: 'Search',
    multiSelectedItems: 'selected items',
    // Fallback для BaseSelect
    baseSelectOption: 'Select option',
    baseNoOptionsAvailable: 'No options available',
    baseOnPage: 'On page',
    basePerPage: 'Per page',
  }
);

// Переводы для MultiSelect компонентов
const multiSelectTranslations = computed(() => ({
  selectAll: translations.multiSelectAll.value,
  clearAll: translations.multiClearAll.value,
  noOptionsFound: translations.multiNoOptionsFound.value,
  search: translations.multiSearch.value,
  selectedItems: translations.multiSelectedItems.value,
}));

// Переводы для BaseSelect компонентов
const baseSelectTranslations = computed(() => ({
  selectOption: translations.baseSelectOption.value,
  noOptions: translations.baseNoOptionsAvailable.value,
}));

// Переводы для onPage в BaseSelect
const onPageTranslations = computed(() => ({
  onPage: translations.baseOnPage.value,
  perPage: translations.basePerPage.value,
}));

// ============================================================================
// СОСТОЯНИЕ
// ============================================================================

// Локальное состояние компонента
const isMobileFiltersOpen = ref(false);
const localSearchKeyword = ref('');
const isComponentReady = ref(false);

// ============================================================================
// ОБРАБОТЧИКИ СОБЫТИЙ
// ============================================================================

/**
 * Обработчик ввода в поле поиска с debounce
 * Поиск срабатывает только при длине запроса >= MIN_SEARCH_LENGTH символов
 */
const debouncedUpdateSearch = debounce((value: string) => {
  // Проверяем минимальную длину поискового запроса
  if (value.length >= CREATIVES_CONSTANTS.MIN_SEARCH_LENGTH || value.length === 0) {
    store.updateFilter('searchKeyword', value);
  }
  // Если длина меньше MIN_SEARCH_LENGTH символов и больше 0 - ничего не делаем
  // Это предотвращает ненужные API запросы
}, CREATIVES_CONSTANTS.DEBOUNCE_DELAY);

/**
 * Обработчик изменения поля поиска
 */
function handleSearchInput(event: Event): void {
  const target = event.target as HTMLInputElement;
  const value = target.value;

  // Всегда обновляем локальное состояние для отображения
  localSearchKeyword.value = value;

  // Вызываем debounced функцию которая сама проверит длину
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
 * Обработчик выбора пресета фильтров
 */
async function handlePresetSelection(value: string): Promise<void> {
  try {
    // Если выбран default или пустая строка - очищаем выбор пресета
    if (value === 'default' || !value) {
      store.clearSelectedPreset();
      return;
    }

    // Преобразуем строку в число
    const presetId = parseInt(value, 10);
    if (isNaN(presetId)) {
      console.error('Invalid preset ID:', value);
      return;
    }

    // Применяем пресет через store
    await store.applyFilterPreset(presetId);

    // Синхронизируем локальное поле поиска после применения пресета
    syncLocalSearchWithStore();

    // Эмитим событие изменения фильтров
    emitFiltersChanged();

    console.log('✅ Preset applied successfully:', presetId);
  } catch (error) {
    console.error('❌ Error applying preset:', error);

    // В случае ошибки сбрасываем выбор пресета
    store.clearSelectedPreset();

    // Показываем сообщение об ошибке
    store.showMessage('Error loading preset', 'error');
  }
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
  console.log('FiltersComponent mounting with new translation system...');

  try {
    // 1. Обратная совместимость - устанавливаем переводы из props
    mergePropsTranslations(props.translations, store.setTranslations);

    // 2. Ожидаем готовности переводов для защиты от race condition
    console.log('⏳ Waiting for translations to be ready...');
    await waitForReady();
    console.log('✅ Translations are ready, proceeding with initialization...');

    // 3. FiltersComponent всегда выполняет полную инициализацию store с selectOptions
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
      translationsReady: isReady.value,
    });

    // 4. Синхронизируем локальное поле поиска
    syncLocalSearchWithStore();

    // 5. Настраиваем обработчики
    window.addEventListener('resize', handleResize);

    // 6. Эмитим событие готовности
    emitComponentReady();

    isComponentReady.value = true;

    console.log('✅ FiltersComponent successfully mounted with new translation system');
  } catch (error) {
    console.error('❌ Error initializing FiltersComponent:', error);

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
