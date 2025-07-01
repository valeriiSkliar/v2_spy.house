<template>
  <div class="creatives-list__details" :class="{ 'show-details': store.isDetailsVisible }">
    <div class="creative-details" v-if="store.hasSelectedCreative">
      <div class="creative-details__content">
        <!-- Заголовок с кнопками -->
        <div class="creative-details__head">
          <div class="row align-items-center">
            <div class="col-auto mr-auto">
              <h2 class="mb-0">{{ getTranslation('details.title', 'Details') }}</h2>
            </div>
            <div class="col-auto d-md-none">
              <button
                class="btn _flex _gray _small btn-favorite"
                :class="{ active: isFavorite }"
                @click="handleFavoriteClick"
                :disabled="isFavoriteLoading"
              >
                <span :class="getFavoriteIconClass() + ' font-16 mr-2'"></span>
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

        <!-- Базовая информация о креативе -->
        <div class="creative-details__group _first" v-if="selectedCreative">
          <div class="alert alert-info">
            <h4>{{ selectedCreative.title || selectedCreative.name }}</h4>
            <p>{{ selectedCreative.description }}</p>
            <p><strong>ID:</strong> {{ selectedCreative.id }}</p>
            <p>
              <strong>{{ getTranslation('details.country', 'Country') }}:</strong>
              {{ selectedCreative.country }}
            </p>
            <p>
              <strong>{{ getTranslation('details.created', 'Created') }}:</strong>
              {{ formatDate(selectedCreative.created_at) }}
            </p>
          </div>
        </div>

        <!-- Техническая информация -->
        <div class="creative-details__group" v-if="selectedCreative">
          <h3>{{ getTranslation('details.technical-info', 'Technical Information') }}</h3>
          <div class="details-table">
            <div class="details-table__row" v-if="selectedCreative.advertising_networks?.length">
              <div class="details-table__col">
                {{ getTranslation('details.networks', 'Networks') }}
              </div>
              <div class="details-table__col">
                {{ selectedCreative.advertising_networks.join(', ') }}
              </div>
            </div>
            <div class="details-table__row" v-if="selectedCreative.devices?.length">
              <div class="details-table__col">
                {{ getTranslation('details.devices', 'Devices') }}
              </div>
              <div class="details-table__col">{{ selectedCreative.devices.join(', ') }}</div>
            </div>
            <div class="details-table__row" v-if="selectedCreative.languages?.length">
              <div class="details-table__col">
                {{ getTranslation('details.languages', 'Languages') }}
              </div>
              <div class="details-table__col">{{ selectedCreative.languages.join(', ') }}</div>
            </div>
            <div class="details-table__row" v-if="selectedCreative.browsers?.length">
              <div class="details-table__col">
                {{ getTranslation('details.browsers', 'Browsers') }}
              </div>
              <div class="details-table__col">{{ selectedCreative.browsers.join(', ') }}</div>
            </div>
          </div>
        </div>

        <!-- Медиа файлы -->
        <div
          class="creative-details__group"
          v-if="selectedCreative && (selectedCreative.file_url || selectedCreative.preview_url)"
        >
          <h3>{{ getTranslation('details.media', 'Media') }}</h3>
          <div class="row">
            <div class="col-12 mb-15" v-if="selectedCreative.preview_url">
              <div class="thumb thumb-image">
                <img :src="selectedCreative.preview_url" :alt="selectedCreative.title" />
              </div>
            </div>
            <div class="col-6" v-if="selectedCreative.file_url">
              <a :href="selectedCreative.file_url" class="btn _flex _medium _green w-100" download>
                <span class="icon-download2 font-16 mr-2"></span>
                {{ getTranslation('details.download', 'Download') }}
              </a>
            </div>
            <div class="col-6" v-if="selectedCreative.file_url">
              <a
                :href="selectedCreative.file_url"
                class="btn _flex _medium _gray w-100"
                target="_blank"
              >
                <span class="icon-new-tab font-16 mr-2"></span>
                {{ getTranslation('details.open-tab', 'Open in tab') }}
              </a>
            </div>
          </div>
        </div>

        <!-- Похожие креативы (если включено) -->
        <div class="creative-details__group" v-if="showSimilarCreatives && selectedCreative">
          <h3>{{ getTranslation('details.similar', 'Similar Creatives') }}</h3>
          <div class="alert alert-warning">
            {{
              getTranslation(
                'details.similar-placeholder',
                'Similar creatives functionality will be implemented later'
              )
            }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useCreativesFiltersStore } from '@/stores/useFiltersStore';
import type { Creative } from '@/types/creatives.d';
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
