<template>
  <div class="mb-20">
    <div class="search-count">
      <span>{{ searchCount }}</span>
      {{ getPluralizationText() }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useCreativesFiltersStore } from '../../stores/useFiltersStore';

interface Props {
  /** Начальное количество креативов */
  initialCount?: number;
  /** Переводы для pluralization */
  translations?: {
    /** Singular form */
    advertisement?: string;
    /** Plural form */
    advertisements?: string;
    /** Genitive plural form (русский) */
    advertisementsGenitive?: string;
  };
}

const props = withDefaults(defineProps<Props>(), {
  initialCount: 0,
  translations: () => ({
    advertisement: 'advertisement',
    advertisements: 'advertisements',
    advertisementsGenitive: 'креативов',
  }),
});

// Инициализация store
const store = useCreativesFiltersStore();

// Реактивное количество креативов из store
const searchCount = computed(() => store.searchCount);

// Функция для правильного склонения (русский язык)
function getPluralizationText(): string {
  const count = searchCount.value;
  const { advertisement, advertisements, advertisementsGenitive } = props.translations;

  // Если переводы на английском
  if (advertisement === 'advertisement') {
    return count === 1 ? advertisement ?? 'advertisement' : advertisements ?? 'advertisements';
  }

  // Русские правила склонения
  if (count % 10 === 1 && count % 100 !== 11) {
    return advertisement ?? 'креатив';
  } else if ([2, 3, 4].includes(count % 10) && ![12, 13, 14].includes(count % 100)) {
    return advertisements ?? 'креатива';
  } else {
    return advertisementsGenitive ?? 'креативов';
  }
}

// Lifecycle hooks
onMounted(() => {
  // Устанавливаем начальное значение если оно передано
  if (props.initialCount !== undefined) {
    store.setSearchCount(props.initialCount);
  }

  console.log('SearchCountComponent инициализирован с количеством:', searchCount.value);
});
</script>
