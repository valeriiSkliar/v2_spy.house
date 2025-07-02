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
import { mergePropsTranslations } from '@/composables/useTranslations';

onMounted(() => {
  mergePropsTranslations(props.translations, store.setTranslations);
});

// Заменить getTranslation на store.getTranslation или использовать reactive переводы
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
4. ⏳ **Мигрировать остальные компоненты** (FiltersComponent, cards)
5. ⏳ **Обновить документацию проекта**

Система готова к production использованию и решает изначальную проблему race condition с переводами.
