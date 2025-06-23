# Vue 3 + Pinia интерактивные островки

## Установленные пакеты

✅ **Vue 3** (v3.5.17) - Composition API для интерактивных компонентов  
✅ **Pinia** (v2.3.1) - Управление состоянием Vue компонентов  
✅ **Vite** (v6.2.4) - Сборка и hot reload  
✅ **Axios** (v1.8.2) - HTTP клиент для API запросов  
✅ **@vitejs/plugin-vue** (v5.2.4) - Плагин Vite для обработки .vue файлов

## Архитектура

### Концепция "островков" (Islands Architecture)

Подход позволяет интегрировать Vue 3 компоненты в существующее Laravel приложение без полной миграции на SPA. Каждый Vue компонент работает как независимый "островок" интерактивности на серверно-рендеренной странице.

### Особенности инициализации Pinia

**⚠️ Важно:** В архитектуре островков каждый компонент создает отдельное Vue приложение, но все они используют **единый глобальный экземпляр Pinia**. Это позволяет компонентам обмениваться состоянием между собой.

#### Проблемы и решения

1. **Проблема раннего доступа к store:**

   ```typescript
   // ❌ НЕПРАВИЛЬНО - store недоступен в setup()
   const store = useExampleStore(); // Ошибка: getActivePinia() not found
   ```

2. **Правильная ленивая инициализация:**

   ```typescript
   // ✅ ПРАВИЛЬНО - ленивая инициализация
   let store: ReturnType<typeof useExampleStore>;

   function initStore() {
     if (!store) {
       store = useExampleStore();
     }
     return store;
   }

   // Использование в template
   <template>
     <div>{{ initStore().count }}</div>
     <button @click="initStore().increment">+1</button>
   </template>
   ```

3. **Watchers только после монтирования:**

   ```typescript
   // ✅ ПРАВИЛЬНО - watchers в onMounted
   onMounted(() => {
     const storeInstance = initStore();

     watch(
       () => storeInstance.count,
       newCount => {
         emit('countChanged', newCount);
       }
     );
   });
   ```

### Структура файлов

```
resources/js/
├── vue-islands.ts           # Основной файл инициализации
├── vue-components/          # Vue компоненты
│   └── ExampleCounter.vue   # Пример компонента
├── stores/                  # Pinia stores
│   └── example.ts           # Пример store
└── types/                   # TypeScript типы
```

## Использование

### 1. Создание Vue компонента

```vue
<!-- resources/js/vue-components/MyComponent.vue -->
<template>
  <div class="my-component">
    <h3>{{ title }}</h3>
    <button @click="initStore().increment">{{ initStore().count }}</button>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { useExampleStore } from '../stores/example';

interface Props {
  title?: string;
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Мой компонент',
});

// Ленивая инициализация store
let store: ReturnType<typeof useExampleStore>;

function initStore() {
  if (!store) {
    store = useExampleStore();
  }
  return store;
}

// Lifecycle hooks
onMounted(() => {
  // Инициализируем store после монтирования
  const storeInstance = initStore();
  console.log('Store инициализирован:', storeInstance);
});
</script>
```

### 2. Использование в Blade шаблоне

```blade
{{-- Подключение скрипта --}}
@vite(['resources/js/vue-islands.ts'])

{{-- Размещение компонента --}}
<div
    data-vue-component="MyComponent"
    data-vue-props='{"title": "Заголовок компонента"}'
></div>
```

### 3. Создание Pinia Store

```typescript
// resources/js/stores/myStore.ts
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useMyStore = defineStore('myStore', () => {
  const count = ref<number>(0);

  const doubleCount = computed(() => count.value * 2);

  function increment(): void {
    count.value++;
  }

  return { count, doubleCount, increment };
});
```

## Интеграция с существующими системами

### AJAX и динамический контент

```javascript
// Автоматическая переинициализация после AJAX загрузки
document.addEventListener('blog:content-updated', event => {
  window.initVueIslands();
});
```

### Работа с Laravel данными

```blade
{{-- Передача данных из Laravel --}}
<div
    data-vue-component="UserProfile"
    data-vue-props='{
        "userId": {{ auth()->user()->id }},
        "userName": "{{ auth()->user()->name }}",
        "isAdmin": {{ auth()->user()->is_admin ? 'true' : 'false' }}
    }'
></div>
```

## API Reference

### Глобальные функции

- `window.initVueIslands()` - Инициализация всех Vue компонентов на странице
- `window.axios` - Настроенный Axios клиент с CSRF токеном

### Атрибуты элементов

- `data-vue-component` - Имя Vue компонента (обязательно)
- `data-vue-props` - JSON строка с пропсами (опционально)
- `data-vue-initialized` - Флаг инициализации (автоматически)

### События

- `blog:content-updated` - Событие обновления контента (интеграция с блогом)

## Примеры компонентов

### Счетчик с Pinia (правильная инициализация)

```vue
<template>
  <div>
    <p>Счетчик: {{ initStore().count }}</p>
    <button @click="initStore().increment">+1</button>
  </div>
</template>

<script setup lang="ts">
import { useExampleStore } from '../stores/example';

// Ленивая инициализация
let store: ReturnType<typeof useExampleStore>;

function initStore() {
  if (!store) {
    store = useExampleStore();
  }
  return store;
}
</script>
```

### Демонстрация разделяемого состояния

```blade
{{-- Несколько компонентов используют один store --}}
<div class="row">
  <div class="col-md-6">
    <h4>Счетчик №1</h4>
    <div data-vue-component="ExampleCounter" data-vue-props='{"title": "Первый счетчик"}'></div>
  </div>

  <div class="col-md-6">
    <h4>Счетчик №2</h4>
    <div data-vue-component="ExampleCounter" data-vue-props='{"title": "Второй счетчик"}'></div>
  </div>
</div>
{{-- Оба компонента будут показывать одинаковое значение счетчика! --}}
{{-- Нажатие +1 в любом из них обновит значение в обоих --}}
```

### Форма с валидацией

```vue
<template>
  <form @submit.prevent="submitForm">
    <input v-model="form.name" :class="{ 'is-invalid': errors.name }" />
    <div v-if="errors.name" class="invalid-feedback">{{ errors.name }}</div>
    <button type="submit" :disabled="loading">Отправить</button>
  </form>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue';

const form = reactive({ name: '' });
const errors = ref({});
const loading = ref(false);

const submitForm = async () => {
  loading.value = true;
  try {
    await window.axios.post('/api/submit', form);
  } catch (error) {
    errors.value = error.response.data.errors;
  } finally {
    loading.value = false;
  }
};
</script>
```

## Преимущества

1. **Постепенная миграция** - можно добавлять Vue компоненты по мере необходимости
2. **Изолированность** - каждый компонент работает независимо
3. **TypeScript поддержка** - полная типизация
4. **Интеграция с Laravel** - использование Blade, роутов, авторизации
5. **Hot Reload** - быстрая разработка с Vite
6. **Shared State** - единый Pinia экземпляр для обмена состоянием между островками
7. **Реактивность между компонентами** - изменения в одном компоненте автоматически отражаются в других
8. **Глобальное состояние** - stores сохраняют данные между переходами пользователя по островкам

## Рекомендации

1. **Используйте островки для интерактивных элементов** (формы, счетчики, модалки)
2. **Создавайте отдельные stores для разных доменов**
3. **Тестируйте переинициализацию после AJAX загрузки**
4. **Документируйте пропсы компонентов с TypeScript**
5. **Используйте событийную систему для коммуникации с существующим кодом**

### ⚠️ Важные правила для Pinia

1. **Никогда не вызывайте `useStore()` в корне `setup()`** - это приведет к ошибке
2. **Всегда используйте ленивую инициализацию** через функцию `initStore()`
3. **Watchers создавайте только в `onMounted()`** после инициализации store
4. **Computed properties работают через `initStore()`** в template
5. **Все Vue островки используют один экземпляр Pinia** - состояние разделяется

### Типичные ошибки

```typescript
// ❌ ОШИБКА: раннее обращение к store
const store = useExampleStore(); // Error: getActivePinia() not found

// ❌ ОШИБКА: watcher в setup()
watch(() => store.count, ...); // store еще не инициализирован

// ❌ ОШИБКА: обращение к store в computed в setup()
const doubleCount = computed(() => store.count * 2); // Error
```

```typescript
// ✅ ПРАВИЛЬНО: все через ленивую инициализацию
let store: ReturnType<typeof useExampleStore>;

function initStore() {
  if (!store) {
    store = useExampleStore();
  }
  return store;
}

// ✅ ПРАВИЛЬНО: в template
<template>
  <div>{{ initStore().count }}</div>
</template>

// ✅ ПРАВИЛЬНО: watchers в onMounted
onMounted(() => {
  const storeInstance = initStore();
  watch(() => storeInstance.count, ...);
});
```

## Анализ возможных Vue островков для страницы Creatives

### 1. Фильтрация и поиск (высокий приоритет)

#### `CreativesFilterComponent`

- **Местоположение**: блок `.filter`
- **Функциональность**: поиск, фильтры по стране/дате/категориям, сохранение настроек
- **Store**: `useCreativesFilterStore` для синхронизации всех фильтров

#### `FilterTabsComponent`

- **Местоположение**: `.filter-push` (Push/In Page/Facebook/TikTok)
- **Функциональность**: переключение типов креативов с счетчиками
- **Shared state**: с основным фильтром

### 2. Список креативов (высокий приоритет)

#### `CreativesListComponent`

- **Местоположение**: `.creatives-list`
- **Функциональность**: отображение, пагинация, lazy loading
- **Store**: `useCreativesStore` для данных креативов

#### `CreativeItemComponent`

- **Местоположение**: каждый `.creative-item`
- **Функциональность**: воспроизведение видео, копирование, избранное
- **Props**: данные креатива из API

### 3. Детали креатива (средний приоритет)

#### `CreativeDetailsComponent`

- **Местоположение**: `.creative-details`
- **Функциональность**: модальное окно с деталями, похожие креативы
- **Store**: синхронизация с выбранным креативом

### 4. Пользовательские действия (средний приоритет)

#### `FavoritesManagerComponent`

- **Местоположение**: кнопка "Favorites" и все `.btn-favorite`
- **Функциональность**: управление избранным с локальным состоянием
- **Store**: `useFavoritesStore` для синхронизации между островками

#### `UserSettingsComponent`

- **Местоположение**: селектор "On page — 12", языковое меню
- **Функциональность**: настройки отображения, язык, тариф
- **Store**: `useUserPreferencesStore`

### 5. Интерактивные элементы (низкий приоритет)

#### `VideoPlayerComponent`

- **Местоположение**: все `.creative-video`
- **Функциональность**: воспроизведение с контролами, preview
- **Standalone**: может работать независимо

#### `CopyButtonComponent`

- **Местоположение**: все `.js-copy`
- **Функциональность**: копирование с уведомлениями
- **Lightweight**: простой компонент без store

## Рекомендуемая последовательность внедрения:

1. **Фаза 1**: `CreativesFilterComponent` + `FilterTabsComponent`
2. **Фаза 2**: `CreativesListComponent` + `FavoritesManagerComponent`
3. **Фаза 3**: `CreativeDetailsComponent` + `VideoPlayerComponent`
4. **Фаза 4**: остальные вспомогательные компоненты

## Архитектура Stores:

```typescript
// resources/js/stores/creatives.ts
export const useCreativesStore = defineStore('creatives', () => {
  const items = ref([]);
  const filters = ref({});
  const pagination = ref({});
  const selectedCreative = ref(null);

  // API методы
  const loadCreatives = async filters => {
    /* */
  };
  const toggleFavorite = async id => {
    /* */
  };

  return { items, filters, pagination, selectedCreative, loadCreatives, toggleFavorite };
});
```

Этот подход позволит постепенно мигрировать статичный HTML к интерактивным Vue компонентам, сохраняя существующий дизайн и функциональность.
