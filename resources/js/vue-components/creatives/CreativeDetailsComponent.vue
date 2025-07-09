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
        <div class="creative-details__group">
          <h3 class="mb-20">{{ translations.similarCreatives_title.value }}</h3>
          <div v-if="!showSimilarCreatives" class="promo-premium">
            <p v-html="translations.promoPremium.value"></p>
            <a href="/tariffs" class="btn _flex _green _medium">{{ translations.go.value }}</a>
          </div>
          <div v-if="showSimilarCreatives" class="similar-creatives">
            <div class="creative-item">
              <div class="creative-item__head">
                <div class="creative-item__icon thumb thumb-with-controls-small mr-2">
                  <img :src="selectedCreative?.icon_url" :alt="selectedCreative?.title" />
                  <div class="thumb-controls">
                    <a href="#" class="btn-icon _black"><span class="icon-download2"></span></a>
                  </div>
                </div>
                <div class="creative-item__txt">
                  <div class="creative-item__active icon-dot">Active: 3 day</div>
                  <div class="text-with-copy">
                    <div class="text-with-copy__btn">
                      <button class="btn copy-btn _flex _dark js-copy">
                        <span class="icon-copy"></span>Copy<span class="copy-btn__copied"
                          >Copied</span
                        >
                      </button>
                    </div>
                    <div class="creative-item__title">
                      ⚡ What are the pensions the increase? 💰
                    </div>
                  </div>
                  <div class="text-with-copy">
                    <div class="text-with-copy__btn">
                      <button class="btn copy-btn _flex _dark js-copy">
                        <span class="icon-copy"></span>Copy<span class="copy-btn__copied"
                          >Copied</span
                        >
                      </button>
                    </div>
                    <div class="creative-item__desc">
                      How much did Kazakhstanis begin to receive
                    </div>
                  </div>
                </div>
              </div>
              <div class="creative-item__footer">
                <div class="creative-item__info">
                  <div class="creative-item-info">
                    <span class="creative-item-info__txt">Push.house</span>
                  </div>
                  <div class="creative-item-info">
                    <img :src="`img/flags/${selectedCreative?.country?.code}.svg`" alt="" />
                    {{ selectedCreative?.country?.name }}
                  </div>
                  <div class="creative-item-info">
                    <div class="icon-pc"></div>
                    PC
                  </div>
                </div>
                <div class="creative-item__btns">
                  <button class="btn-icon btn-favorite">
                    <span class="icon-favorite-empty"></span>
                  </button>
                  <button class="btn-icon _dark js-show-details">
                    <span class="icon-info"></span>
                  </button>
                </div>
              </div>
            </div>
          </div>
          <div v-if="!showSimilarCreatives" class="similar-creatives">
            <div class="similar-creative-empty _inpage"><img :src="emptyImage" alt="" /></div>
            <div class="similar-creative-empty _inpage"><img :src="emptyImage" alt="" /></div>
          </div>
          <div v-if="showSimilarCreatives" class="d-flex justify-content-center pt-3">
            <button class="btn _gray _flex _medium w-mob-100">
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
import { computed, onMounted } from 'vue';

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
</script>
