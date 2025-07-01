<template>
  <div class="creatives-list__details" :class="{ 'show-details': store.isDetailsVisible }">
    <div class="creative-details" v-if="store.hasSelectedCreative">
      <div class="creative-details__content">
        <!-- Заголовок с кнопками -->
        <div class="creative-details__head">
          <div class="row align-items-center">
            <div class="col-auto mr-auto">
              <h2 class="mb-0">
                {{ getTranslation('details.title', 'Details') }}
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
                    ? getTranslation('details.remove-from-favorites', 'Remove from favorites')
                    : getTranslation('details.add-to-favorites', 'Add to favorites')
                }}
              </button>
            </div>
            <div class="col-auto">
              <button class="btn-icon _dark" @click="store.hideCreativeDetails">
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
                <img :src="selectedCreative?.preview_url" :alt="selectedCreative?.title" />
              </div>
            </div>
            <div class="col-6">
              <p class="font-16 mb-15">
                <span class="font-weight-600">{{ getTranslation('details.icon', 'Icon') }}</span>
                {{ selectedCreative?.file_size }}
              </p>
              <div class="mb-10">
                <a href="#" class="btn _flex _medium _green w-100"
                  ><span class="icon-download2 font-16 mr-2"></span
                  >{{ getTranslation('details.download', 'Download') }}</a
                >
              </div>
              <div class="mb-0">
                <a
                  :href="selectedCreative?.icon_url"
                  target="_blank"
                  class="btn _flex _medium _gray w-100"
                  ><span class="icon-new-tab font-16 mr-2"></span
                  >{{ getTranslation('details.open-tab', 'Open in tab') }}</a
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
              <a href="#" class="btn _flex _medium _green w-100"
                ><span class="icon-download2 font-16 mr-2"></span
                >{{ getTranslation('details.download', 'Download') }}</a
              >
            </div>
            <div class="col-auto flex-grow-1 mb-10">
              <a href="#" class="btn _flex _medium _gray w-100"
                ><span class="icon-new-tab font-16 mr-2"></span
                >{{ getTranslation('details.open-tab', 'Open in tab') }}</a
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
                    ? getTranslation('details.remove-from-favorites', 'Remove from favorites')
                    : getTranslation('details.add-to-favorites', 'Add to favorites')
                }}
              </button>
            </div>
          </div>
        </div>

        <!-- Details second row (Image details) -->
        <div v-if="activeTab === 'push'" class="creative-details__group">
          <p class="font-16 mb-15">
            <span class="font-weight-600">{{ getTranslation('details.image', 'Image') }}</span>
            {{ selectedCreative?.main_image_size }}
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
                ><span class="icon-download2 font-16 mr-2"></span
                >{{ getTranslation('details.download', 'Download') }}</a
              >
            </div>
            <div class="col-6">
              <a
                :href="selectedCreative?.main_image_url"
                target="_blank"
                class="btn _flex _medium _gray w-100"
                ><span class="icon-new-tab font-16 mr-2"></span
                >{{ getTranslation('details.open-tab', 'Open in tab') }}</a
              >
            </div>
          </div>
        </div>
        <!-- Details second row (Text details) -->
        <div class="creative-details__group">
          <p class="mb-15 font-16 font-weight-600">
            {{ getTranslation('details.text', 'Text') }}
          </p>
          <div class="mb-20">
            <div class="mb-10 row align-items-center justify-content-between">
              <div class="col-auto">
                <span class="txt-gray">{{ getTranslation('details.title', 'Title') }}</span>
              </div>
              <div class="col-auto">
                <button class="btn copy-btn _flex _dark js-copy">
                  <span class="icon-copy"></span>
                  {{ getTranslation('details.copy', 'Copy') }}
                  <span class="copy-btn__copied">{{
                    getTranslation('details.copied', 'Copied')
                  }}</span>
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
                <span class="txt-gray">{{
                  getTranslation('details.description', 'Description')
                }}</span>
              </div>
              <div class="col-auto">
                <button class="btn copy-btn _flex _dark js-copy">
                  <span class="icon-copy"></span>
                  {{ getTranslation('details.copy', 'Copy') }}
                  <span class="copy-btn__copied">{{
                    getTranslation('details.copied', 'Copied')
                  }}</span>
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
            {{ getTranslation('details.redirects-details', 'Redirects details') }}
          </h3>
          <div class="form-link mb-25">
            <input type="url" :value="selectedCreative?.landing_page_url" readonly />
            <a href="#" target="_blank" class="btn-icon _small _white"
              ><span class="icon-new-tab"></span
            ></a>
          </div>
          <div class="details-table">
            <div class="details-table__row">
              <div class="details-table__col">
                {{ getTranslation('details.advertising-networks', 'Advertising networks') }}
              </div>
              <div class="details-table__col">
                <a href="#" class="link _gray">{{
                  selectedCreative?.advertising_networks?.join(', ')
                }}</a>
              </div>
            </div>
            <div class="details-table__row">
              <div class="details-table__col">
                {{ getTranslation('details.country', 'Country') }}
              </div>
              <div class="details-table__col">
                <img
                  :src="`img/flags/${selectedCreative?.country}.svg`"
                  :alt="selectedCreative?.country"
                />
                {{ selectedCreative?.country }}
              </div>
            </div>
            <div class="details-table__row">
              <div class="details-table__col">
                {{ getTranslation('details.language', 'Language') }}
              </div>
              <div class="details-table__col">
                {{ selectedCreative?.languages?.join(', ') }}
              </div>
            </div>
            <div class="details-table__row">
              <div class="details-table__col">
                {{ getTranslation('details.first-display-date', 'First display date') }}
              </div>
              <div class="details-table__col">
                {{ selectedCreative?.created_at_formatted }}
              </div>
            </div>
            <div class="details-table__row">
              <div class="details-table__col">
                {{ getTranslation('details.last-display-date', 'Last display date') }}
              </div>
              <div class="details-table__col">
                {{ selectedCreative?.last_activity_date_formatted }}
              </div>
            </div>
            <div class="details-table__row">
              <div class="details-table__col">
                {{ getTranslation('details.status', 'Status') }}
              </div>
              <div class="details-table__col">
                <div class="creative-status" :class="{ 'icon-dot': selectedCreative?.is_active }">
                  {{
                    selectedCreative?.is_active
                      ? getTranslation('details.active', 'Active')
                      : getTranslation('details.inactive', 'Inactive')
                  }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Похожие креативы (если включено) -->
        <div class="creative-details__group">
          <h3 class="mb-20">Similar creatives</h3>
          <div class="promo-premium">
            <p>Similar ads are available in the <strong>Premium plan</strong></p>
            <a href="#" class="btn _flex _green _medium">Go</a>
          </div>
          <div class="similar-creatives">
            <div class="similar-creative-empty _inpage"><img :src="emptyImage" alt="" /></div>
            <div class="similar-creative-empty _inpage"><img :src="emptyImage" alt="" /></div>
            <div class="creative-item">
              <div class="creative-item__head">
                <div class="creative-item__icon thumb thumb-with-controls-small mr-2">
                  <img :src="selectedCreative?.preview_url" :alt="selectedCreative?.title" />
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
                    <img :src="`img/flags/${selectedCreative?.country}.svg`" alt="" />
                    {{ selectedCreative?.country }}
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
          <div class="d-flex justify-content-center pt-3">
            <button class="btn _gray _flex _medium w-mob-100">
              <span class="icon-load-more font-16 mr-2"></span>Load more
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useCreativesFiltersStore } from '@/stores/useFiltersStore';
import type { Creative } from '@/types/creatives.d';
import emptyImage from '@img/empty.svg';
import facebookImage from '@img/facebook-2.jpg';
import { computed } from 'vue';

interface Props {
  showSimilarCreatives?: boolean;
  translations?: Record<string, string>;
}

const props = withDefaults(defineProps<Props>(), {
  showSimilarCreatives: true,
  translations: () => ({}),
});

// Подключение к store
const store = useCreativesFiltersStore();

// Computed свойства
const selectedCreative = computed((): Creative | null => store.currentCreativeDetails);
const activeTab = computed(() => store.tabs.activeTab);

// Избранное
const isFavorite = computed((): boolean => {
  if (!selectedCreative.value) return false;
  return store.isFavoriteCreative(selectedCreative.value.id);
});

const isFavoriteLoading = computed((): boolean => {
  if (!selectedCreative.value) return false;
  return store.isFavoriteLoading(selectedCreative.value.id);
});

// Методы
function getTranslation(key: string, fallback: string = key): string {
  return props.translations[key] || store.getTranslation(key, fallback);
}

function getFavoriteIconClass(): string {
  return isFavorite.value ? 'icon-favorite' : 'icon-favorite-empty';
}

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

function formatDate(dateString: string): string {
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString('ru-RU', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return dateString;
  }
}
</script>
