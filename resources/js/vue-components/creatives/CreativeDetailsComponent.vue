<template>
  <div class="creatives-list__details" :class="{ 'show-details': store.isDetailsVisible }">
    <div class="creative-details" v-if="store.hasSelectedCreative">
      <div class="creative-details__content">
        <!-- Заголовок с кнопками -->
        <div class="creative-details__head">
          <div class="row align-items-center">
            <div class="col-auto mr-auto">
              <h2 class="mb-0">
                {{ translations.title.value }}
              </h2>
            </div>
            <div class="col-auto">
              <button
                v-if="activeTab === 'push' || activeTab === 'inpage'"
                class="btn _flex _gray _small btn-favorite"
                :class="{ active: isFavorite }"
                @click="handleFavoriteClick"
                :disabled="isFavoriteLoading"
              >
                <span class="icon-favorite-empty font-16 mr-2"></span>
                {{
                  isFavorite
                    ? translations.removeFromFavorites.value
                    : translations.addToFavorites.value
                }}
              </button>
            </div>
            <div class="col-auto">
              <button
                class="btn-icon _dark"
                @click="store.detailsManager.handleHideCreativeDetails"
              >
                <span class="icon-x font-18"></span>
              </button>
            </div>
          </div>
        </div>

        <!-- Details first row (Icon details) -->
        <div
          v-if="activeTab === 'push' || activeTab === 'inpage'"
          class="creative-details__group _first"
        >
          <div class="row _offset20 align-items-center">
            <div class="col-5">
              <div class="thumb thumb-icon">
                <img :src="selectedCreative?.icon_url" :alt="selectedCreative?.title" />
              </div>
            </div>
            <div class="col-6">
              <p class="font-16 mb-15">
                <span class="font-weight-600">{{ translations.icon.value }}</span>
                {{ iconSize }}
              </p>
              <div class="mb-10">
                <a
                  href="#"
                  class="btn _flex _medium _green w-100"
                  @click.prevent="handleDownload(selectedCreative?.icon_url ?? '')"
                  ><span class="icon-download2 font-16 mr-2"></span
                  >{{ translations.download.value }}</a
                >
              </div>
              <div class="mb-0">
                <a
                  :href="selectedCreative?.icon_url"
                  target="_blank"
                  class="btn _flex _medium _gray w-100"
                  @click.prevent="handleOpenInNewTab(selectedCreative?.landing_url ?? '')"
                  ><span class="icon-new-tab font-16 mr-2"></span
                  >{{ translations.openTab.value }}</a
                >
              </div>
            </div>
          </div>
        </div>
        <div v-else class="creative-details__group _first">
          <div class="creative-video _single">
            <div class="thumb">
              <img :src="facebookImage" :alt="selectedCreative?.title" class="thumb-blur" />
              <img :src="facebookImage" :alt="selectedCreative?.title" class="thumb-contain" />
            </div>
            <span class="icon-play"></span>
            <div class="creative-video__time">00:45</div>
            <div
              class="creative-video__content"
              data-video="https://dev.vitaliimaksymchuk.com.ua/spy/img/video-2.mp4"
            ></div>
          </div>
          <div class="row">
            <div class="col-auto flex-grow-1 mb-10">
              <a
                href="#"
                class="btn _flex _medium _green w-100"
                @click.prevent="handleDownload(selectedCreative?.icon_url ?? '')"
                ><span class="icon-download2 font-16 mr-2"></span
                >{{ translations.download.value }}</a
              >
            </div>
            <div class="col-auto flex-grow-1 mb-10">
              <a href="#" class="btn _flex _medium _gray w-100"
                ><span class="icon-new-tab font-16 mr-2"></span>{{ translations.openTab.value }}</a
              >
            </div>
            <div class="col-auto flex-grow-1 mb-10 d-none d-md-block">
              <button
                class="btn _flex _gray _medium btn-favorite w-100"
                :class="{ active: isFavorite }"
                @click="handleFavoriteClick"
                :disabled="isFavoriteLoading"
              >
                <span class="icon-favorite font-16 mr-2"></span
                >{{
                  isFavorite
                    ? translations.removeFromFavorites.value
                    : translations.addToFavorites.value
                }}
              </button>
            </div>
          </div>
        </div>

        <!-- Details second row (Image details) -->
        <div v-if="activeTab === 'push'" class="creative-details__group">
          <p class="font-16 mb-15">
            <span class="font-weight-600">{{ translations.image.value }}</span>
            {{ mainImageSize }}
          </p>
          <div class="thumb thumb-image mb-15">
            <img :src="selectedCreative?.main_image_url" :alt="selectedCreative?.title" />
          </div>
          <div class="row _offset20">
            <div class="col-6">
              <a
                :href="selectedCreative?.main_image_url"
                download
                class="btn _flex _medium _green w-100"
                @click.prevent="handleDownload(selectedCreative?.main_image_url ?? '')"
                ><span class="icon-download2 font-16 mr-2"></span
                >{{ translations.download.value }}</a
              >
            </div>
            <div class="col-6">
              <a
                :href="selectedCreative?.main_image_url"
                target="_blank"
                class="btn _flex _medium _gray w-100"
                ><span class="icon-new-tab font-16 mr-2"></span>{{ translations.openTab.value }}</a
              >
            </div>
          </div>
        </div>
        <!-- Details second row (Text details) -->
        <div class="creative-details__group">
          <p class="mb-15 font-16 font-weight-600">
            {{ translations.text.value }}
          </p>
          <div class="mb-20">
            <div class="mb-10 row align-items-center justify-content-between">
              <div class="col-auto">
                <span class="txt-gray">{{ translations.titleField.value }}</span>
              </div>
              <div class="col-auto">
                <button class="btn copy-btn _flex _dark js-copy" @click="handleCopyTitle">
                  <span class="icon-copy"></span>
                  {{ translations.copy.value }}
                  <span class="copy-btn__copied">{{ translations.copied.value }}</span>
                </button>
              </div>
            </div>
            <p class="font-roboto font-weight-500 font-16">
              {{ selectedCreative?.title }}
            </p>
          </div>
          <div class="mb-20">
            <div class="mb-10 row align-items-center justify-content-between">
              <div class="col-auto">
                <span class="txt-gray">{{ translations.description.value }}</span>
              </div>
              <div class="col-auto">
                <button class="btn copy-btn _flex _dark js-copy" @click="handleCopyDescription">
                  <span class="icon-copy"></span>
                  {{ translations.copy.value }}
                  <span class="copy-btn__copied">{{ translations.copied.value }}</span>
                </button>
              </div>
            </div>
            <p class="font-roboto font-16">{{ selectedCreative?.description }}</p>
          </div>
          <!-- <div class="pt-2">
            <button class="btn _flex _gray _medium">
              <span class="icon-translate font-18 mr-2"></span>
              {{ getTranslation('details.translate-text', 'Translate text') }}
            </button>
          </div> -->
        </div>

        <!-- Details third row (Redirects details) -->
        <div class="creative-details__group">
          <h3 class="mb-20">
            {{ translations.redirectsDetails.value }}
          </h3>
          <div class="form-link mb-25">
            <input type="url" :value="selectedCreative?.landing_url" readonly />
            <a
              :href="selectedCreative?.landing_url ?? ''"
              target="_blank"
              class="btn-icon _small _white"
              @click.prevent="handleOpenInNewTab(selectedCreative?.landing_url ?? '')"
              ><span class="icon-new-tab"></span
            ></a>
          </div>
          <div class="details-table">
            <div class="details-table__row">
              <div class="details-table__col">
                {{ translations.advertisingNetworks.value }}
              </div>
              <div class="details-table__col">
                <a href="#" class="link _gray">{{
                  selectedCreative?.advertising_networks?.join(', ')
                }}</a>
              </div>
            </div>
            <div class="details-table__row">
              <div class="details-table__col">
                {{ translations.country.value }}
              </div>
              <div class="details-table__col">
                <img
                  :src="`img/flags/${selectedCreative?.country?.code}.svg`"
                  :alt="selectedCreative?.country?.name"
                />
                {{ selectedCreative?.country?.name }}
              </div>
            </div>
            <div v-if="selectedCreative?.language" class="details-table__row">
              <div class="details-table__col">
                {{ translations.language.value }}
              </div>
              <div class="details-table__col">
                {{ selectedCreative?.language?.name }}
              </div>
            </div>
            <div class="details-table__row">
              <div class="details-table__col">
                {{ translations.firstDisplayDate.value }}
              </div>
              <div class="details-table__col">
                {{ selectedCreative?.created_at }}
              </div>
            </div>
            <div v-if="selectedCreative?.last_seen_at" class="details-table__row">
              <div class="details-table__col">
                {{ translations.lastDisplayDate.value }}
              </div>
              <div class="details-table__col">
                {{ selectedCreative?.last_seen_at }}
              </div>
            </div>
            <div class="details-table__row">
              <div class="details-table__col">
                {{ translations.status.value }}
              </div>
              <div class="details-table__col">
                <div class="creative-status" :class="{ 'icon-dot': selectedCreative?.is_active }">
                  {{
                    selectedCreative?.is_active
                      ? translations.active.value
                      : translations.inactive.value
                  }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Похожие креативы (если включено) -->
        <div class="creative-details__group" ref="similarCreativesSection">
          <h3 class="mb-20">{{ translations.similarCreatives_title.value }}</h3>

          <!-- Состояние загрузки -->
          <div v-if="similarCreativesLoading" class="text-center py-4">
            <div class="spinner-border" role="status">
              <span class="sr-only">Загрузка...</span>
            </div>
            <p class="mt-2 text-muted">Загружаем похожие креативы...</p>
          </div>

          <!-- Ошибка загрузки -->
          <div v-else-if="similarCreativesError" class="alert alert-warning">
            <p>Не удалось загрузить похожие креативы: {{ similarCreativesError }}</p>
            <button
              class="btn btn-sm btn-outline-primary"
              @click="loadSimilarCreatives"
              :disabled="similarCreativesLoading"
            >
              Попробовать снова
            </button>
          </div>

          <!-- Загруженные похожие креативы -->
          <div
            v-else-if="similarCreativesLoaded && similarCreatives.length > 0"
            class="similar-creatives"
          >
            <div v-for="similar in similarCreatives" :key="similar.id" class="creative-item">
              <div class="creative-item__head">
                <div class="creative-item__icon thumb thumb-with-controls-small mr-2">
                  <img :src="similar.icon_url" :alt="similar.title" />
                  <div class="thumb-controls">
                    <a
                      href="#"
                      class="btn-icon _black"
                      @click.prevent="handleDownload(similar.icon_url ?? '')"
                    >
                      <span class="icon-download2"></span>
                    </a>
                  </div>
                </div>
                <div class="creative-item__txt">
                  <div class="creative-item__active icon-dot" v-if="similar.is_active">
                    Active: {{ similar.created_at }}
                  </div>
                  <div class="text-with-copy">
                    <div class="text-with-copy__btn">
                      <button
                        class="btn copy-btn _flex _dark js-copy"
                        @click="handleCopyCreativeTitle(similar)"
                      >
                        <span class="icon-copy"></span>
                        {{ translations.copy.value }}
                        <span class="copy-btn__copied">{{ translations.copied.value }}</span>
                      </button>
                    </div>
                    <div class="creative-item__title">
                      {{ similar.title }}
                    </div>
                  </div>
                  <div class="text-with-copy" v-if="similar.description">
                    <div class="text-with-copy__btn">
                      <button
                        class="btn copy-btn _flex _dark js-copy"
                        @click="handleCopyCreativeDescription(similar)"
                      >
                        <span class="icon-copy"></span>
                        {{ translations.copy.value }}
                        <span class="copy-btn__copied">{{ translations.copied.value }}</span>
                      </button>
                    </div>
                    <div class="creative-item__desc">
                      {{ similar.description }}
                    </div>
                  </div>
                </div>
              </div>
              <div class="creative-item__footer">
                <div class="creative-item__info">
                  <div class="creative-item-info" v-if="similar.advertising_networks?.length">
                    <span class="creative-item-info__txt">{{
                      similar.advertising_networks[0]
                    }}</span>
                  </div>
                  <div class="creative-item-info" v-if="similar.country">
                    <img
                      :src="`img/flags/${similar.country.code}.svg`"
                      :alt="similar.country.name"
                    />
                    {{ similar.country.name }}
                  </div>
                  <div class="creative-item-info" v-if="similar.devices?.length">
                    <div class="icon-pc"></div>
                    {{ similar.devices[0] }}
                  </div>
                </div>
                <div class="creative-item__btns">
                  <button
                    class="btn-icon btn-favorite"
                    :class="{ active: similar.isFavorite }"
                    @click="handleSimilarFavoriteClick(similar)"
                  >
                    <span class="icon-favorite-empty"></span>
                  </button>
                  <button
                    class="btn-icon _dark js-show-details"
                    @click="handleShowSimilarDetails(similar)"
                  >
                    <span class="icon-info"></span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Нет доступа к похожим креативам ИЛИ загружено но пустой результат -->
          <div
            v-else-if="
              !showSimilarCreatives || (similarCreativesLoaded && similarCreatives.length === 0)
            "
            class="promo-premium"
          >
            <p v-html="translations.promoPremium.value"></p>
            <a href="/tariffs" class="btn _flex _green _medium">{{ translations.go.value }}</a>
          </div>

          <!-- Заглушка пока не загружено (по умолчанию) -->
          <div v-else class="similar-creatives">
            <div class="similar-creative-empty _inpage"><img :src="emptyImage" alt="" /></div>
            <div class="similar-creative-empty _inpage"><img :src="emptyImage" alt="" /></div>

            <!-- Кнопка для ручной загрузки (fallback) -->
            <div class="text-center mt-3">
              <button
                class="btn _gray _medium"
                @click="loadSimilarCreatives"
                :disabled="similarCreativesLoading"
              >
                <span
                  v-if="similarCreativesLoading"
                  class="spinner-border spinner-border-sm mr-2"
                ></span>
                {{ similarCreativesLoading ? 'Загрузка...' : 'Загрузить похожие' }}
              </button>
            </div>
          </div>

          <!-- Кнопка "Загрузить еще" (пока не реализована) -->
          <div
            v-if="similarCreativesLoaded && similarCreatives.length >= 6"
            class="d-flex justify-content-center pt-3"
          >
            <button class="btn _gray _flex _medium w-mob-100" disabled>
              <span class="icon-load-more font-16 mr-2"></span>{{ translations.loadMore.value }}
            </button>
          </div>
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
import { useCreativesFiltersStore } from '@/stores/useFiltersStore';
import type { Creative } from '@/types/creatives.d';
import emptyImage from '@img/empty.svg';
import facebookImage from '@img/facebook-2.jpg';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

interface Props {
  showSimilarCreatives?: boolean;
  translations?: Record<string, string>;
  handleOpenInNewTab?: (url: string) => void;
  handleDownload?: (url: string) => void;
}

const props = withDefaults(defineProps<Props>(), {
  showSimilarCreatives: true,
  translations: () => ({}),
  handleOpenInNewTab: () => {},
  handleDownload: () => {},
});

// Подключение к store и композаблу переводов
const store = useCreativesFiltersStore();
const { waitForReady, t } = useTranslations();

// Состояние для похожих креативов
const similarCreatives = ref<Creative[]>([]);
const similarCreativesLoaded = ref(false);
const similarCreativesLoading = ref(false);
const similarCreativesError = ref<string | null>(null);

// Ссылка на DOM элемент секции похожих креативов
const similarCreativesSection = ref<HTMLElement | null>(null);
const intersectionObserver = ref<IntersectionObserver | null>(null);

// Создаем reactive переводы для часто используемых ключей с fallback значениями
const translations = createReactiveTranslations(
  {
    title: 'title',
    addToFavorites: 'addToFavorites',
    removeFromFavorites: 'removeFromFavorites',
    download: 'download',
    openTab: 'openTab',
    copy: 'copy',
    copied: 'copied',
    icon: 'icon',
    image: 'image',
    text: 'text',
    titleField: 'titleField',
    description: 'description',
    translateText: 'translateText',
    redirectsDetails: 'redirectsDetails',
    advertisingNetworks: 'advertisingNetworks',
    country: 'country',
    language: 'language',
    firstDisplayDate: 'firstDisplayDate',
    lastDisplayDate: 'lastDisplayDate',
    status: 'status',
    active: 'active',
    inactive: 'inactive',
    similarCreatives_title: 'similarCreatives_title',
    promoPremium: 'promo-premium',
    go: 'go',
    loadMore: 'loadMore',
  },
  {
    // Fallback значения
    title: 'Details',
    addToFavorites: 'Add to favorites',
    removeFromFavorites: 'Remove from favorites',
    download: 'Download',
    openTab: 'Open in new tab',
    copy: 'Copy',
    copied: 'Copied',
    icon: 'Icon',
    image: 'Image',
    text: 'Text',
    titleField: 'Title',
    description: 'Description',
    translateText: 'Translate',
    redirectsDetails: 'Redirects',
    advertisingNetworks: 'Advertising networks',
    country: 'Country',
    language: 'Language',
    firstDisplayDate: 'First display date',
    lastDisplayDate: 'Last display date',
    status: 'Status',
    active: 'Active',
    inactive: 'Inactive',
    similarCreatives_title: 'Similar creatives',
    promoPremium: 'Similar ads are available in the <strong>Premium plan</strong>',
    go: 'Go',
    loadMore: 'Load more',
  }
);

// Объединяем переводы из props со store (для обратной совместимости)
onMounted(async () => {
  // Мержим переводы из props с Store для обратной совместимости
  if (Object.keys(props.translations).length > 0) {
    mergePropsTranslations(props.translations, store.setTranslations);
  } else {
    console.warn('⚠️ No translations in props! Props are empty.');
  }
  // Ждем готовности переводов для предотвращения race condition
  await waitForReady();

  // Настраиваем Intersection Observer после рендеринга
  await nextTick();
  setupIntersectionObserver();
});

// Очищаем наблюдатель при размонтировании компонента
onUnmounted(() => {
  cleanupIntersectionObserver();
});

// Computed свойства
const selectedCreative = computed((): Creative | null => store.currentCreativeDetails);
const activeTab = computed(() => store.tabs.activeTab);

// Computed свойства для размеров файлов из новой структуры file_sizes_detailed
const iconSize = computed((): string => {
  // Используем новую структуру file_sizes_detailed если доступна
  if (
    selectedCreative.value?.file_sizes_detailed &&
    Array.isArray(selectedCreative.value.file_sizes_detailed)
  ) {
    const iconFile = selectedCreative.value.file_sizes_detailed.find(file => file.type === 'icon');
    if (iconFile?.formatted_size) {
      return iconFile.formatted_size;
    }
  }

  // Fallback к старым полям для обратной совместимости
  if (selectedCreative.value?.icon_size) {
    return selectedCreative.value.icon_size;
  }

  // Fallback к общему размеру файла
  if (selectedCreative.value?.file_size && typeof selectedCreative.value.file_size === 'string') {
    return selectedCreative.value.file_size;
  }

  return 'N/A';
});

const mainImageSize = computed((): string => {
  // Используем новую структуру file_sizes_detailed если доступна
  if (
    selectedCreative.value?.file_sizes_detailed &&
    Array.isArray(selectedCreative.value.file_sizes_detailed)
  ) {
    const mainImageFile = selectedCreative.value.file_sizes_detailed.find(
      file => file.type === 'main_image'
    );
    if (mainImageFile?.formatted_size) {
      return mainImageFile.formatted_size;
    }
  }

  // Fallback к старым полям для обратной совместимости
  if (selectedCreative.value?.main_image_size) {
    return selectedCreative.value.main_image_size;
  }

  // Fallback к общему размеру файла
  if (selectedCreative.value?.file_size && typeof selectedCreative.value.file_size === 'string') {
    return selectedCreative.value.file_size;
  }

  return 'N/A';
});

// Избранное
const isFavorite = computed((): boolean => {
  if (!selectedCreative.value) return false;
  return store.isFavoriteCreative(selectedCreative.value.id);
});

const isFavoriteLoading = computed((): boolean => {
  if (!selectedCreative.value) return false;
  return store.isFavoriteLoading(selectedCreative.value.id);
});

async function handleFavoriteClick(): Promise<void> {
  if (!selectedCreative.value || isFavoriteLoading.value) return;

  try {
    if (isFavorite.value) {
      await store.removeFromFavorites(selectedCreative.value.id);
    } else {
      await store.addToFavorites(selectedCreative.value.id);
    }
  } catch (error) {
    console.error('Ошибка обработки избранного в деталях:', error);
  }
}

/**
 * Обработчик копирования заголовка креатива
 * Использует централизованную событийную систему
 */
function handleCopyTitle(): void {
  if (!selectedCreative.value?.title) {
    console.warn('Заголовок креатива недоступен для копирования');
    return;
  }

  // Эмитируем событие для централизованной обработки
  document.dispatchEvent(
    new CustomEvent('creatives:copy-text', {
      detail: {
        text: selectedCreative.value.title,
        type: 'title',
        creativeId: selectedCreative.value.id,
      },
    })
  );
}

/**
 * Обработчик копирования описания креатива
 * Использует централизованную событийную систему
 */
function handleCopyDescription(): void {
  if (!selectedCreative.value?.description) {
    console.warn('Описание креатива недоступно для копирования');
    return;
  }

  // Эмитируем событие для централизованной обработки
  document.dispatchEvent(
    new CustomEvent('creatives:copy-text', {
      detail: {
        text: selectedCreative.value.description,
        type: 'description',
        creativeId: selectedCreative.value.id,
      },
    })
  );
}

/**
 * Обработчик копирования заголовка похожего креатива
 */
function handleCopyCreativeTitle(creative: Creative): void {
  if (!creative.title) {
    console.warn('Заголовок креатива недоступен для копирования');
    return;
  }

  document.dispatchEvent(
    new CustomEvent('creatives:copy-text', {
      detail: {
        text: creative.title,
        type: 'title',
        creativeId: creative.id,
      },
    })
  );
}

/**
 * Обработчик копирования описания похожего креатива
 */
function handleCopyCreativeDescription(creative: Creative): void {
  if (!creative.description) {
    console.warn('Описание креатива недоступно для копирования');
    return;
  }

  document.dispatchEvent(
    new CustomEvent('creatives:copy-text', {
      detail: {
        text: creative.description,
        type: 'description',
        creativeId: creative.id,
      },
    })
  );
}

/**
 * Обработчик добавления похожего креатива в избранное
 */
async function handleSimilarFavoriteClick(creative: Creative): Promise<void> {
  if (!creative || isFavoriteLoading.value) return;

  try {
    if (creative.isFavorite) {
      await store.removeFromFavorites(creative.id);
    } else {
      await store.addToFavorites(creative.id);
    }
  } catch (error) {
    console.error('Ошибка обработки избранного похожего креатива:', error);
  }
}

/**
 * Обработчик открытия деталей похожего креатива
 */
function handleShowSimilarDetails(creative: Creative): void {
  store.detailsManager.handleShowCreativeDetails(creative.id);
}

/**
 * Загрузка похожих креативов через API
 */
async function loadSimilarCreatives(): Promise<void> {
  console.log('🔄 Загрузка похожих креативов для ID:', selectedCreative.value?.id);

  if (similarCreativesLoaded.value || similarCreativesLoading.value) {
    console.log('⚠️ Загрузка пропущена - уже загружено или выполняется');
    return;
  }

  if (!selectedCreative.value) {
    console.warn('❌ Нет выбранного креатива для загрузки похожих');
    return;
  }

  if (!props.showSimilarCreatives) {
    console.log('⚠️ Загрузка похожих отключена в props');
    return;
  }

  similarCreativesLoading.value = true;
  similarCreativesError.value = null;

  try {
    const apiUrl = `/api/creatives/${selectedCreative.value.id}/similar?limit=6`;
    console.log('🌐 API запрос:', apiUrl);

    const response = await fetch(apiUrl, {
      method: 'GET',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        // Добавляем CSRF токен если доступен
        ...((window as any).csrf_token && { 'X-CSRF-TOKEN': (window as any).csrf_token }),
      },
      credentials: 'same-origin', // Для передачи cookies с аутентификацией
    });

    if (!response.ok) {
      // Обрабатываем ошибки доступа к Premium функции
      if (response.status === 403) {
        const errorData = await response.json();
        console.info('ℹ️ Доступ к похожим креативам ограничен (Premium)');
        similarCreatives.value = [];
        similarCreativesLoaded.value = true;
        return;
      }
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    if (data.status === 'success') {
      const receivedCreatives = data.data.similar_creatives || [];
      console.log('✅ Загружено похожих креативов:', receivedCreatives.length);
      similarCreatives.value = receivedCreatives;
    } else {
      throw new Error(data.message || 'Ошибка загрузки похожих креативов');
    }

    similarCreativesLoaded.value = true;
  } catch (error) {
    console.error('❌ Ошибка загрузки похожих креативов:', error);
    similarCreativesError.value = error instanceof Error ? error.message : 'Неизвестная ошибка';
    similarCreatives.value = [];
  } finally {
    similarCreativesLoading.value = false;
  }
}

/**
 * Настройка Intersection Observer для отслеживания появления секции похожих креативов
 */
function setupIntersectionObserver(): void {
  if (!similarCreativesSection.value) {
    console.warn('❌ Элемент секции похожих креативов не найден');
    return;
  }

  if (intersectionObserver.value) {
    return; // Observer уже существует
  }

  // Проверяем видимость элемента
  const rect = similarCreativesSection.value.getBoundingClientRect();
  const isCurrentlyVisible = rect.top < window.innerHeight && rect.bottom > 0;

  intersectionObserver.value = new IntersectionObserver(
    entries => {
      entries.forEach(entry => {
        if (
          entry.isIntersecting &&
          !similarCreativesLoaded.value &&
          !similarCreativesLoading.value
        ) {
          console.log('👁️ Секция похожих креативов стала видимой, запускаем загрузку');
          loadSimilarCreatives();
        }
      });
    },
    {
      root: null, // viewport
      rootMargin: '50px', // Загружаем за 50px до появления секции
      threshold: 0.1, // Срабатывает когда 10% элемента видно
    }
  );

  intersectionObserver.value.observe(similarCreativesSection.value);

  // Если элемент уже виден - запускаем загрузку немедленно
  if (isCurrentlyVisible && !similarCreativesLoaded.value && !similarCreativesLoading.value) {
    console.log('🚀 Элемент уже виден, запускаем загрузку немедленно');
    loadSimilarCreatives();
  }
}

/**
 * Очистка Intersection Observer
 */
function cleanupIntersectionObserver(): void {
  if (intersectionObserver.value) {
    intersectionObserver.value.disconnect();
    intersectionObserver.value = null;
  }
}

/**
 * Сброс состояния похожих креативов при смене креатива
 */
function resetSimilarCreativesState(): void {
  similarCreatives.value = [];
  similarCreativesLoaded.value = false;
  similarCreativesLoading.value = false;
  similarCreativesError.value = null;
  cleanupIntersectionObserver();
}

// Отслеживаем изменения выбранного креатива для сброса состояния
watch(
  selectedCreative,
  (newCreative, oldCreative) => {
    if (newCreative) {
      resetSimilarCreativesState();
      // Настраиваем наблюдатель после небольшой задержки, чтобы DOM обновился
      nextTick(() => {
        setTimeout(() => {
          setupIntersectionObserver();
        }, 100);
      });
    } else {
      cleanupIntersectionObserver();
    }
  },
  { immediate: true }
);

// Дополнительный watch на видимость деталей как fallback
watch(
  () => store.isDetailsVisible,
  (isVisible, wasVisible) => {
    if (isVisible && !wasVisible && selectedCreative.value) {
      nextTick(() => {
        setTimeout(() => {
          setupIntersectionObserver();

          // Если секция уже видна и ничего не загружено - запускаем принудительно
          if (
            similarCreativesSection.value &&
            !similarCreativesLoaded.value &&
            !similarCreativesLoading.value
          ) {
            const rect = similarCreativesSection.value.getBoundingClientRect();
            const isCurrentlyVisible = rect.top < window.innerHeight && rect.bottom > 0;

            if (isCurrentlyVisible) {
              console.log('🚀 Fallback: принудительная загрузка похожих креативов');
              loadSimilarCreatives();
            }
          }
        }, 150);
      });
    }
  }
);
</script>
