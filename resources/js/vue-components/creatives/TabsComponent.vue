<!-- resources/js/vue-components/creatives/TabsComponent.vue -->
<template>
  <div class="filter-push">
    <button
      v-for="tab in store.tabOptions"
      :key="tab.value"
      class="filter-push__item"
      :class="{ active: tab.value === store.tabs.activeTab }"
      @click="handleTabClick(tab.value)"
    >
      {{ tab.label }}
      <span v-if="tab.count" class="filter-push__count">{{ formatCount(tab.count) }}</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import type { TabsState } from '@/types/creatives';
import { onMounted, onUnmounted } from 'vue';
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
  if (store.tabs.activeTab !== tabValue) {
    console.log('Tab clicked:', tabValue);

    // Устанавливаем новую активную вкладку через store
    // Это автоматически триггерит URL синхронизацию и загрузку креативов
    store.setActiveTab(tabValue);

    // Эмитим событие для компонентов, которые не используют новую систему (обратная совместимость)
    const event = new CustomEvent('tabs:changed', {
      detail: {
        activeTab: tabValue,
        previousTab: store.tabs.activeTab,
        tabOption: store.tabOptions.find(t => t.value === tabValue),
      },
    });
    document.dispatchEvent(event);

    // Также эмитим новое событие для полной совместимости
    const creativesEvent = new CustomEvent('creatives:tab-changed', {
      detail: {
        currentTab: tabValue,
        previousTab: store.tabs.activeTab,
        source: 'user',
      },
    });
    document.dispatchEvent(creativesEvent);
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
    // 1. Инициализируем store с переданными параметрами
    await store.initializeFilters(
      props.initialTabs, // начальное состояние вкладок
      undefined, // selectOptions не нужны для вкладок
      props.translations, // переводы
      props.tabOptions // опции вкладок (activeTab, counts, etc.)
    );

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
