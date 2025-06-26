<!-- resources/js/vue-components/creatives/TabsComponent.vue -->
<template>
  <div class="filter-push">
    <button
      v-for="tab in initStore().tabOptions"
      :key="tab.value"
      class="filter-push__item"
      :class="{ active: tab.value === initStore().tabs.activeTab }"
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
import { useFiltersStore } from '../../stores/creatives';

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

// Store instance
let storeInstance: ReturnType<typeof useFiltersStore> | null = null;

// Инициализация store
function initStore() {
  if (storeInstance) return storeInstance;

  storeInstance = useFiltersStore();
  console.log('Tabs store создан');
  return storeInstance;
}

console.log('TabsComponent props:', props.initialTabs, props.tabOptions);

// Функция для получения переводов с fallback
function getTranslation(key: string, fallback: string = key): string {
  const store = initStore();
  return store.getTranslation(key, fallback);
}

// Форматирование счетчиков (170k, 3.1k, etc.)
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

// Обработчик клика по вкладке
function handleTabClick(tabValue: string): void {
  const store = initStore();

  if (store.tabs.activeTab !== tabValue) {
    console.log('Tab clicked:', tabValue);
    store.setActiveTab(tabValue);

    // Эмитим событие для компонентов, которые не используют Pinia
    const event = new CustomEvent('tabs:changed', {
      detail: {
        activeTab: tabValue,
        previousTab: store.tabs.activeTab,
        tabOption: store.tabOptions.find(t => t.value === tabValue),
      },
    });
    document.dispatchEvent(event);
  }
}

// Обработчик внешних событий смены вкладки
function handleExternalTabChange(event: Event): void {
  const customEvent = event as CustomEvent;
  const { currentTab } = customEvent.detail;

  if (currentTab) {
    const store = initStore();
    if (store.tabs.activeTab !== currentTab) {
      console.log('External tab change detected:', currentTab);
      // Просто обновляем состояние, не генерируем новые события
      store.tabs.activeTab = currentTab;
    }
  }
}

onMounted(async () => {
  console.log('TabsComponent props:', props);

  // 1. Инициализируем store (если еще не инициализирован)
  const store = initStore();

  // 2. Применяем конфигурацию вкладок если переданы props
  if (Object.keys(props.initialTabs).length > 0 || Object.keys(props.tabOptions).length > 0) {
    console.log('Initializing tabs with options...');

    // Сначала применяем опции вкладок (включая activeTab из сервера)
    if (props.tabOptions && Object.keys(props.tabOptions).length > 0) {
      store.setTabOptions(props.tabOptions);
    }

    // Затем применяем начальное состояние (только если нет activeTab в tabOptions)
    if (props.initialTabs && Object.keys(props.initialTabs).length > 0) {
      // Не перезаписываем activeTab если он уже установлен из tabOptions
      const { activeTab, ...restInitialTabs } = props.initialTabs;
      Object.assign(store.tabs, restInitialTabs);

      // Устанавливаем activeTab только если он не был установлен из tabOptions
      if (activeTab && !props.tabOptions?.activeTab) {
        store.tabs.activeTab = activeTab;
      }
    }
  }

  console.log('Tabs store инициализирован:', store.tabs);

  // 3. Слушаем внешние события смены вкладок
  document.addEventListener('creatives:tab-changed', handleExternalTabChange);

  // Эмитим событие готовности компонента
  const event = new CustomEvent('vue-component-ready', {
    detail: {
      component: 'CreativesTabsComponent',
      props: props,
      tabs: store.tabs,
      urlSyncEnabled: props.enableUrlSync,
      timestamp: new Date().toISOString(),
    },
  });
  document.dispatchEvent(event);
});

onUnmounted(() => {
  // Очищаем обработчики событий
  document.removeEventListener('creatives:tab-changed', handleExternalTabChange);
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
