<!-- resources/js/vue-components/creatives/TabsComponent.vue -->
<template>
  <div class="filter-push">
    <button
      v-for="tab in sortedTabOptions"
      :key="tab.value"
      class="filter-push__item"
      :class="{ active: tab.value === store.tabs.activeTab }"
      @click="handleTabClick(tab.value)"
    >
      {{ tab.label.charAt(0).toUpperCase() + tab.label.slice(1) }}
      <span v-if="tab.count" class="filter-push__count">{{ formatCount(tab.count) }}</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { isValidTabValue, TABS_ORDER, TabsState } from '@/types/creatives.d';
import { computed, onMounted, onUnmounted } from 'vue';
import { useCreativesFiltersStore } from '../../stores/useFiltersStore';

interface Props {
  initialTabs?: Partial<TabsState>;
  tabOptions?: any;
  translations?: Record<string, string>;
  enableUrlSync?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  initialTabs: () => ({}),
  tabOptions: () => ({}),
  translations: () => ({}),
  enableUrlSync: true,
});

// ============================================================================
// STORE ИНИЦИАЛИЗАЦИЯ
// ============================================================================

// Используем новый useCreativesFiltersStore с композаблами
const store = useCreativesFiltersStore();

// ============================================================================
// COMPUTED PROPERTIES
// ============================================================================

/**
 * Сортированный массив вкладок в фиксированном порядке
 * Гарантирует, что вкладки всегда отображаются в том же порядке, что и в placeholder
 */
const sortedTabOptions = computed(() => {
  if (!store.tabOptions || !Array.isArray(store.tabOptions)) {
    return [];
  }

  // Создаем Map для быстрого поиска вкладок по значению
  const tabsMap = new Map(store.tabOptions.map((tab: any) => [tab.value, tab]));

  // Возвращаем вкладки в фиксированном порядке
  const sortedTabs = TABS_ORDER.map(tabValue => tabsMap.get(tabValue)).filter(
    tab => tab !== undefined
  ); // Исключаем вкладки, которых нет в данных

  // Логируем порядок для отладки
  console.log('🔄 TabsComponent sortedTabOptions:', {
    fixedOrder: TABS_ORDER,
    availableTabsOrder: sortedTabs.map(tab => tab.value),
    originalTabsOrder: store.tabOptions.map((tab: any) => tab.value),
  });

  return sortedTabs;
});

// ============================================================================
// УТИЛИТАРНЫЕ ФУНКЦИИ
// ============================================================================

/**
 * Форматирование счетчиков (170k, 3.1k, etc.)
 */
function formatCount(count: string | number): string {
  if (typeof count === 'string') {
    return count;
  }

  if (typeof count === 'number') {
    if (count >= 1000000) {
      return `${(count / 1000000).toFixed(1)}m`;
    } else if (count >= 1000) {
      return `${(count / 1000).toFixed(1)}k`;
    }
    return count.toString();
  }

  return '';
}

/**
 * Обработчик клика по вкладке
 */
function handleTabClick(tabValue: string): void {
  // Проверяем валидность значения и приводим тип
  if (!isValidTabValue(tabValue)) {
    console.warn('Invalid tab value:', tabValue);
    return;
  }

  if (store.tabs.activeTab !== tabValue) {
    console.log('Tab clicked:', tabValue);

    // Устанавливаем новую активную вкладку через store
    // Это автоматически триггерит URL синхронизацию и загрузку креативов через Store watchers
    store.setActiveTab(tabValue);
  }
}

/**
 * Обработчик внешних событий смены вкладки (обратная совместимость)
 */
function handleExternalTabChange(event: Event): void {
  const customEvent = event as CustomEvent;
  const { currentTab } = customEvent.detail;

  if (currentTab && store.tabs.activeTab !== currentTab) {
    console.log('External tab change detected:', currentTab);
    // Обновляем состояние через store (без генерации новых событий)
    store.tabs.activeTab = currentTab;
  }
}

// ============================================================================
// LIFECYCLE HOOKS
// ============================================================================

onMounted(async () => {
  console.log('TabsComponent mounting with props:', props);

  try {
    // TabsComponent не должен инициализировать весь store - только устанавливать свои опции
    console.log('🔧 Setting tab-specific options...');

    // Устанавливаем опции вкладок
    if (props.tabOptions) {
      store.setTabOptions(props.tabOptions);
    }

    // Устанавливаем переводы
    if (props.translations) {
      store.setTranslations(props.translations);
    }

    // Обновляем состояние вкладок через store.tabs напрямую, если переданы
    if (props.initialTabs) {
      if (props.initialTabs.availableTabs) {
        store.tabs.availableTabs = [...props.initialTabs.availableTabs];
      }
      if (props.initialTabs.tabCounts) {
        store.tabs.tabCounts = { ...props.initialTabs.tabCounts };
      }
      if (
        props.initialTabs.activeTab &&
        store.tabs.availableTabs.includes(props.initialTabs.activeTab)
      ) {
        store.tabs.activeTab = props.initialTabs.activeTab;
      }
    }

    // Если store ещё не инициализирован - пусть это сделает FiltersComponent с полными selectOptions
    console.log('📝 TabsComponent options applied. Store initialization:', {
      isInitialized: store.isInitialized,
      note: 'Full store initialization will be handled by FiltersComponent',
    });

    console.log('Store initialized:', {
      tabs: store.tabs,
      tabOptions: store.tabOptions,
      isInitialized: store.isInitialized,
    });

    // 2. Слушаем внешние события для обратной совместимости
    document.addEventListener('creatives:tab-changed', handleExternalTabChange);

    // 3. Эмитим событие готовности компонента
    const readyEvent = new CustomEvent('vue-component-ready', {
      detail: {
        component: 'CreativesTabsComponent',
        props: props,
        store: {
          tabs: store.tabs,
          activeTab: store.tabs.activeTab,
          tabOptions: store.tabOptions,
        },
        urlSyncEnabled: props.enableUrlSync,
        timestamp: new Date().toISOString(),
      },
    });
    document.dispatchEvent(readyEvent);

    console.log('TabsComponent successfully mounted and initialized');
  } catch (error) {
    console.error('Error initializing TabsComponent:', error);

    // Эмитим событие ошибки
    const errorEvent = new CustomEvent('vue-component-error', {
      detail: {
        component: 'CreativesTabsComponent',
        error: error,
        timestamp: new Date().toISOString(),
      },
    });
    document.dispatchEvent(errorEvent);
  }
});

onUnmounted(() => {
  // Очищаем обработчики событий
  document.removeEventListener('creatives:tab-changed', handleExternalTabChange);

  console.log('TabsComponent unmounted');
});
</script>

<style scoped>
.filter-push {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
}

.filter-push__item {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  border: none;
  background: none;
  font-size: 16px;
  font-weight: 500;
  color: #78888f;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: all 0.3s ease;
  text-decoration: none;
}

.filter-push__item:hover {
  color: #3dc98a;
}

.filter-push__item.active {
  border-bottom-color: #3dc98a;
  color: #3dc98a;
}

.filter-push__count {
  background: #f3f5f6;
  border-radius: 5px;
  font-size: 12px;
  color: #6e8087;
  padding: 3px 6px;
  font-weight: 400;
  min-width: 30px;
  text-align: center;
}

@media (max-width: 768px) {
  .filter-push {
    gap: 10px;
  }

  .filter-push__item {
    padding: 10px 12px;
    font-size: 14px;
  }

  .filter-push__count {
    font-size: 11px;
    padding: 2px 4px;
  }
}
</style>
