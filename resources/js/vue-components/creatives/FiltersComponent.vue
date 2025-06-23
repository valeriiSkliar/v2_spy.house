<!-- resources/js/vue-components/FiltersComponent.vue -->
<template>
  <div class="filter">
    <!-- Мобильный триггер -->
    <div class="filter__trigger-mobile" @click="toggleMobileFilters">
      <span class="btn-icon _dark _big _filter">
        <span class="icon-filter"></span>
        <span class="icon-up font-24" :class="{ rotated: isMobileFiltersOpen }"></span>
      </span>
      Filter
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
                  placeholder="Search by Keyword"
                  :value="initStore().filters.searchKeyword"
                  @input="initStore().setSearchKeyword(($event.target as HTMLInputElement).value)"
                />
              </div>
            </div>

            <!-- Выбор страны/категории -->
            <div class="col-12 col-md-6 col-lg-3 mb-10 w-lg-1 flex-grow-1">
              <BaseSelect
                :value="initStore().filters.country"
                :options="initStore().countryOptions"
                placeholder="Country"
                @update:value="initStore().setCountry($event)"
              />
            </div>

            <!-- Выбор даты создания -->
            <div class="col-12 col-md-6 col-lg-3 mb-10 w-lg-1 flex-grow-1">
              <DateSelect
                :value="initStore().filters.dateCreation"
                :options="initStore().dateRanges"
                placeholder="Date of creation"
                @update:value="initStore().setDateCreation($event)"
              />
            </div>

            <!-- Сортировка -->
            <div class="col-12 col-md-12 col-lg-3 mb-10 w-lg-1 flex-grow-1">
              <BaseSelect
                :value="initStore().filters.sortBy"
                :options="initStore().sortOptions"
                placeholder="Sort by"
                @update:value="initStore().setSortBy($event)"
              />
            </div>
          </div>
        </div>

        <!-- Кнопка сброса (десктоп) -->
        <div class="col-12 col-md-auto mb-10 d-none d-md-block">
          <div class="reset-btn">
            <button class="btn-icon" @click="initStore().resetFilters()">
              <span class="icon-clear"></span>
              <span class="ml-2 d-md-none">Reset</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Детальные фильтры -->
      <div class="filter__detailed" v-show="initStore().filters.isDetailedVisible">
        <div class="filter__title">Detailed filtering</div>
        <div class="row">
          <!-- Период отображения -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <DateSelect
              :value="initStore().filters.periodDisplay"
              :options="initStore().dateRanges"
              placeholder="Period of display"
              @update:value="initStore().setPeriodDisplay($event)"
            />
          </div>

          <!-- Рекламные сети -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <MultiSelect
              :values="initStore().filters.advertisingNetworks"
              placeholder="Advertising networks"
              @add="initStore().addToMultiSelect('advertisingNetworks', $event)"
              @remove="initStore().removeFromMultiSelect('advertisingNetworks', $event)"
            />
          </div>

          <!-- Языки -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <MultiSelect
              :values="initStore().filters.languages"
              placeholder="Languages"
              @add="initStore().addToMultiSelect('languages', $event)"
              @remove="initStore().removeFromMultiSelect('languages', $event)"
            />
          </div>

          <!-- Операционные системы -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <MultiSelect
              :values="initStore().filters.operatingSystems"
              placeholder="Operation systems"
              @add="initStore().addToMultiSelect('operatingSystems', $event)"
              @remove="initStore().removeFromMultiSelect('operatingSystems', $event)"
            />
          </div>

          <!-- Браузеры -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <MultiSelect
              :values="initStore().filters.browsers"
              placeholder="Browsers"
              @add="initStore().addToMultiSelect('browsers', $event)"
              @remove="initStore().removeFromMultiSelect('browsers', $event)"
            />
          </div>

          <!-- Устройства -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <MultiSelect
              :values="initStore().filters.devices"
              placeholder="Devices"
              @add="initStore().addToMultiSelect('devices', $event)"
              @remove="initStore().removeFromMultiSelect('devices', $event)"
            />
          </div>

          <!-- Размеры изображений -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <MultiSelect
              :values="initStore().filters.imageSizes"
              placeholder="Image sizes"
              @add="initStore().addToMultiSelect('imageSizes', $event)"
              @remove="initStore().removeFromMultiSelect('imageSizes', $event)"
            />
          </div>

          <!-- Только для взрослых -->
          <div class="col-12 col-md-6 col-lg-3 mb-15">
            <label class="checkbox-toggle _with-background">
              <span class="icon-18 font-20"></span>
              <span class="mr-auto">Only adult</span>
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
              placeholder="Saved settings"
              @add="initStore().addToMultiSelect('savedSettings', $event)"
              @remove="initStore().removeFromMultiSelect('savedSettings', $event)"
            />
          </div>

          <!-- Кнопка сохранения настроек -->
          <div class="col-12 col-md-auto mb-10">
            <button class="btn _flex _dark _medium w-100" @click="initStore().saveSettings()">
              <span class="icon-save mr-2 font-16"></span>
              Save settings
            </button>
          </div>
        </div>

        <!-- Кнопка сброса (мобильный) -->
        <div class="reset-btn d-md-none">
          <button class="btn-icon" @click="initStore().resetFilters()">
            <span class="icon-clear"></span>
            <span class="ml-2">Reset</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useFiltersStore } from '../../stores/creatives';
import BaseSelect from '../ui/BaseSelect.vue';
import DateSelect from '../ui/DateSelect.vue';
import MultiSelect from '../ui/MultiSelect.vue';

interface Props {
  initialFilters?: Partial<typeof useFiltersStore.prototype.filters>;
}

const props = withDefaults(defineProps<Props>(), {
  initialFilters: () => ({}),
});

// Ленивая инициализация store согласно архитектуре островков
let store: ReturnType<typeof useFiltersStore>;

function initStore() {
  if (!store) {
    store = useFiltersStore();
  }
  return store;
}

// Локальное состояние для мобильного интерфейса
const isMobileFiltersOpen = ref(false);

function toggleMobileFilters(): void {
  isMobileFiltersOpen.value = !isMobileFiltersOpen.value;
}

// Обработчик изменения размера экрана
function handleResize(): void {
  if (window.innerWidth >= 768) {
    isMobileFiltersOpen.value = false;
  }
}

// Lifecycle hooks
onMounted(() => {
  // Инициализируем store и применяем начальные фильтры
  const storeInstance = initStore();

  // Применяем переданные фильтры
  if (props.initialFilters) {
    Object.assign(storeInstance.filters, props.initialFilters);
  }

  // Добавляем обработчик resize
  window.addEventListener('resize', handleResize);

  console.log('Filters store инициализирован:', storeInstance);
});

onUnmounted(() => {
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
</style>
