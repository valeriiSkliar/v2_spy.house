# Решение проблемы Race Condition с переводами

## Проблема

В системе креативов была выявлена проблема **race condition** при загрузке переводов, которая приводила к отображению fallback значений вместо актуальных переводов в 50% случаев при тестовых перезагрузках страницы.

### Выявленные причины:

1. **Race condition в Store**: Переводы загружались асинхронно через `initializeFilters()`, но компоненты могли рендериться ДО их установки
2. **Несогласованность API переводов**:
   - `CreativeDetailsComponent` использовал двойную систему: `props.translations[key] || store.getTranslation(key, fallback)`
   - Остальные компоненты использовали только `store.getTranslation()`
3. **Отсутствие защиты от race condition**: Не было проверки готовности переводов перед рендерингом
4. **Отсутствие реактивности**: Переводы не обновлялись автоматически при их изменении

## Решение

Создана **централизованная reactive система переводов с защитой от race condition**.

### 1. Улучшенная система переводов в Store

```typescript
// resources/js/stores/useFiltersStore.ts

// Состояние готовности переводов
const isTranslationsReady = ref(false);
const translationsLoadingPromise = ref<Promise<void> | null>(null);
const translationWaitingQueue = ref<Array<() => void>>([]);

// Базовые переводы (fallback для критических ключей)
const defaultTranslations: Record<string, string> = {
  title: 'Filter',
  copyButton: 'Copy',
  'details.title': 'Details',
  // ... остальные критические переводы
};
```

#### Ключевые улучшения:

- **`isTranslationsReady`** - флаг готовности переводов
- **`waitForTranslations()`** - метод ожидания готовности с Promise API
- **`defaultTranslations`** - базовые переводы как fallback
- **Очередь ожидания** для компонентов, которые ждут переводы

### 2. Новый композабл `useTranslations`

```typescript
// resources/js/composables/useTranslations.ts

export function useTranslations(): TranslationsComposable {
  const store = useCreativesFiltersStore();

  return {
    // Получить перевод с fallback
    t: (key: string, fallback?: string) => store.getTranslation(key, fallback),

    // Reactive перевод (обновляется автоматически)
    tReactive: (key: string, fallback?: string) => store.useTranslation(key, fallback),

    // Готовы ли переводы
    isReady: computed(() => store.isTranslationsReady),

    // Ожидать готовности переводов
    waitForReady: () => store.waitForTranslations(),

    // Базовые переводы (fallback)
    defaults: store.defaultTranslations,
  };
}
```

#### Особенности:

- **`t()`** - простое получение перевода (non-reactive)
- **`tReactive()`** - reactive перевод, обновляется автоматически
- **`waitForReady()`** - защита от race condition
- **Единый API** для всех компонентов

### 3. Хелперы для упрощения использования

```typescript
// Создание множественных reactive переводов
const translations = createReactiveTranslations({
  title: 'details.title',
  addToFavorites: 'details.add-to-favorites',
  copy: 'details.copy',
});

// В template:
{
  {
    translations.title.value;
  }
}

// Объединение переводов из props (обратная совместимость)
mergePropsTranslations(props.translations, store.setTranslations);
```

### 4. Миграция компонента CreativeDetailsComponent

**До:**

```typescript
function getTranslation(key: string, fallback: string = key): string {
  return props.translations[key] || store.getTranslation(key, fallback);
}

// В template:
{
  {
    getTranslation('details.title', 'Details');
  }
}
```

**После:**

```typescript
import {
  useTranslations,
  createReactiveTranslations,
  mergePropsTranslations,
} from '@/composables/useTranslations';

// Обратная совместимость с props
onMounted(() => {
  mergePropsTranslations(props.translations, store.setTranslations);
});

// Reactive переводы
const translations = createReactiveTranslations({
  title: 'details.title',
  addToFavorites: 'details.add-to-favorites',
  // ... остальные переводы
});

// В template:
{
  {
    translations.title.value;
  }
}
```

### 5. Защита от Race Condition

```typescript
// В getTranslation()
function getTranslation(key: string, fallback?: string): string {
  const effectiveFallback = fallback || defaultTranslations[key] || key;

  // Если переводы не готовы, возвращаем fallback
  if (!isTranslationsReady.value) {
    return effectiveFallback;
  }

  // Получаем перевод из загруженных данных
  // ...
}

// Ожидание готовности переводов
async function waitForTranslations(): Promise<void> {
  if (isTranslationsReady.value) {
    return Promise.resolve();
  }

  // Создаем Promise и добавляем в очередь
  translationsLoadingPromise.value = new Promise<void>(resolve => {
    translationWaitingQueue.value.push(resolve);
  });

  return translationsLoadingPromise.value;
}
```

### 6. Comprehensive тестирование

Созданы полные тесты для:

- **Race condition защиты**: `waitForTranslations()`, `isTranslationsReady`
- **Reactive переводов**: `tReactive()`, автоматические обновления
- **Хелперов**: `createReactiveTranslations()`, `mergePropsTranslations()`
- **Edge cases**: множественные вызовы, вложенные ключи, null/undefined значения

```typescript
it('waitForTranslations() ожидает готовности переводов', async () => {
  expect(store.isTranslationsReady).toBe(false);

  const waitPromise = store.waitForTranslations();

  setTimeout(() => {
    store.setTranslations({ 'test.key': 'Test value' });
  }, 10);

  await waitPromise;

  expect(store.isTranslationsReady).toBe(true);
});
```

## Результаты

### ✅ Решенные проблемы:

1. **Race condition устранен** - переводы всегда отображаются корректно
2. **Единообразный API** - все компоненты используют `useTranslations()`
3. **Реактивность** - переводы обновляются автоматически
4. **Обратная совместимость** - старые props продолжают работать
5. **Типобезопасность** - полная TypeScript поддержка

### 🚀 Улучшения производительности:

- **Кэширование** fallback переводов в `defaultTranslations`
- **Очередь ожидания** вместо polling для проверки готовности
- **Reactive computed** только для нужных переводов

### 🔧 Удобство разработки:

- **Единый API** через `useTranslations()`
- **Хелперы** для массового создания reactive переводов
- **Comprehensive тесты** с покрытием edge cases

## Использование

### Новые компоненты (рекомендуется):

```typescript
import { useTranslations, createReactiveTranslations } from '@/composables/useTranslations';

const { t, isReady, waitForReady } = useTranslations();

// Reactive переводы
const translations = createReactiveTranslations({
  title: 'details.title',
  copy: 'details.copy',
});

// В template:
{
  {
    translations.title.value;
  }
}
```

### Миграция существующих компонентов:

```typescript
// Добавить к существующему коду:
import {
  useTranslations,
  createReactiveTranslations,
  mergePropsTranslations,
} from '@/composables/useTranslations';

// В setup() создать reactive переводы
const { t, isReady, waitForReady } = useTranslations();

const translations = createReactiveTranslations(
  {
    title: 'title',
    searchKeyword: 'searchKeyword',
    // ... остальные переводы
  },
  {
    // Fallback значения
    title: 'Filter',
    searchKeyword: 'Search by Keyword',
  }
);

onMounted(async () => {
  // 1. Обратная совместимость с props
  mergePropsTranslations(props.translations, store.setTranslations);

  // 2. Ожидание готовности переводов
  await waitForReady();

  // 3. Остальная инициализация
});

// В template: {{ translations.title.value }}
```

### Защита от race condition:

```typescript
// Если компонент критичен к переводам
await waitForReady();

// Или проверка готовности
if (isReady.value) {
  // Переводы готовы
}
```

## Следующие шаги

1. ✅ **Решена проблема race condition**
2. ✅ **Создана унифицированная система переводов**
3. ✅ **Мигрирован CreativeDetailsComponent**
4. ✅ **Приведены переводы бэкенда к фронтенд формату**
5. ✅ **Мигрирован FiltersComponent** на новую систему переводов
6. ⏳ **Мигрировать креативные карточки** (PushCreativeCard, InpageCreativeCard, SocialCreativeCard)
7. ⏳ **Обновить документацию проекта**

## Обновления бэкенда для совместимости с фронтенд системой переводов

### 🔧 Изменения в контроллерах

Обновлены методы формирования переводов в `BaseCreativesController.php`:

- **`getTabsTranslations()`** - переводы вкладок в формате `tabs.{name}`
- **`getFiltersTranslations()`** - переводы фильтров с плоскими ключами
- **`getDetailsTranslations()`** - переводы деталей в формате `details.{action}`
- **`getStatesTranslations()`** - переводы состояний в формате `states.{state}` и `actions.{action}`
- **`getAllTranslationsForFrontend()`** - объединяет все переводы в единый плоский формат

### 🌐 Передача переводов через Blade компоненты

Переводы передаются напрямую через Blade компоненты в Vue Islands архитектуре:

```php
// В Blade компоненте
<div data-vue-component="CreativesFiltersComponent" data-vue-props='{
    "translations": {{ json_encode($filtersTranslations) }}
}'>
```

**Почему API эндпоинт не нужен:**

- ✅ Переводы передаются при загрузке страницы через `json_encode()`
- ✅ Переключение языка вызывает полную перезагрузку страницы
- ✅ Vue Islands получают все данные при инициализации
- ✅ Нет необходимости в динамической загрузке переводов

### 📝 Обновления файла переводов

В `lang/ru/creatives.php` добавлены:

1. **Плоские ключи для фильтров**:

   - `date_creation`, `sort_by`, `period_display`
   - `only_adult`, `detailed_filter`
   - `advertising_networks`, `languages`, `operating_systems`
   - `browsers`, `devices`, `image_sizes`

2. **Дополнительные ключи деталей**:

   - `copy`, `copied`, `share`, `preview`
   - `information`, `stats`, `close`

3. **Новые группы переводов**:
   ```php
   'states' => [
       'loading' => 'Загрузка...',
       'error' => 'Ошибка',
       'empty' => 'Нет данных',
       // ...
   ],
   'actions' => [
       'retry' => 'Повторить',
       'refresh' => 'Обновить',
       'load_more' => 'Загрузить еще',
   ]
   ```

### 🔄 Обратная совместимость

`CreativesController.php` обновлен для поддержки:

- Нового единого формата переводов через `getAllTranslationsForFrontend()`
- Старых отдельных массивов переводов для плавной миграции

### 🎯 Результат

Фронтенд получает переводы при загрузке страницы в ожидаемом формате:

```typescript
// Фронтенд использует переводы из props:
store.setTranslations(props.translations);

// И может обращаться к ним:
store.getTranslation('tabs.push'); // "Push"
store.getTranslation('details.copy'); // "Копировать"
store.getTranslation('searchKeyword'); // "Поиск"
store.getTranslation('states.loading'); // "Загрузка..."
```

### 📋 Архитектурное решение

**Vue Islands + Server-Side переводы:**

- Каждый компонент получает переводы через `data-vue-props`
- Нет дублирования HTTP запросов
- Переводы доступны сразу при инициализации компонента
- Полная совместимость с Laravel локализацией

## Миграция FiltersComponent ✅

### Выполненные изменения

**1. Импорт новой системы переводов:**

```typescript
import {
  useTranslations,
  createReactiveTranslations,
  mergePropsTranslations,
} from '@/composables/useTranslations';
```

**2. Создание reactive переводов:**

```typescript
// Новая система переводов с защитой от race condition
const { t, isReady, waitForReady } = useTranslations();

// Reactive переводы для всех UI элементов (15+ переводов)
const translations = createReactiveTranslations(
  {
    title: 'title',
    searchKeyword: 'searchKeyword',
    country: 'country',
    dateCreation: 'dateCreation',
    sortBy: 'sortBy',
    resetButton: 'resetButton',
    isDetailedVisible: 'isDetailedVisible',
    customDateLabel: 'customDateLabel',
    periodDisplay: 'periodDisplay',
    advertisingNetworks: 'advertisingNetworks',
    languages: 'languages',
    operatingSystems: 'operatingSystems',
    browsers: 'browsers',
    devices: 'devices',
    imageSizes: 'imageSizes',
    onlyAdult: 'onlyAdult',
    savedSettings: 'savedSettings',
    savePresetButton: 'savePresetButton',
  },
  {
    // Fallback значения для критических переводов
    title: 'Filter',
    searchKeyword: 'Search by Keyword',
    // ... 15+ fallback значений
  }
);
```

**3. Защита от race condition в onMounted:**

```typescript
onMounted(async () => {
  try {
    // 1. Обратная совместимость - устанавливаем переводы из props
    mergePropsTranslations(props.translations, store.setTranslations);

    // 2. Ожидаем готовности переводов для защиты от race condition
    console.log('⏳ Waiting for translations to be ready...');
    await waitForReady();
    console.log('✅ Translations are ready, proceeding with initialization...');

    // 3. Остальная инициализация
    await store.initializeFilters(/* ... */);

    // ...
  } catch (error) {
    console.error('❌ Error initializing FiltersComponent:', error);
  }
});
```

**4. Замена всех переводов в template:**

```html
<!-- До: -->
{{ store.getTranslation('title', 'Filter') }} :placeholder="store.getTranslation('searchKeyword',
'Search by Keyword')"

<!-- После: -->
{{ translations.title.value }} :placeholder="translations.searchKeyword.value"
```

### Результаты миграции

- ✅ **15+ reactive переводов** для всех фильтров, кнопок и placeholder'ов
- ✅ **Защита от race condition** через `await waitForReady()`
- ✅ **Автоматическое обновление** при изменении переводов
- ✅ **Обратная совместимость** с props.translations
- ✅ **Fallback значения** для всех критических переводов
- ✅ **Консистентный API** - все `store.getTranslation()` заменены на `translations.key.value`
- ✅ **TypeScript безопасность** через типизированные композаблы

### Тестирование

```bash
npm test -- tests/frontend/composables/useTranslations.test.ts --run
# ✅ 19 passed tests - новая система переводов работает корректно
```

**Статус:** Компонент FiltersComponent успешно мигрирован на новую систему переводов с полной защитой от race condition и reactive обновлениями.
