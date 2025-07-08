# Система Централизованного Открытия Вкладок

## Обзор

Система `useCreativesTabOpener` обеспечивает централизованное управление открытием URL в новых вкладках браузера. Все компоненты приложения могут эмитировать стандартизированные события, которые обрабатываются единым образом.

## Архитектура

### Композабл `useCreativesTabOpener`

```typescript
interface TabOpenEventDetail {
  url: string;
}

export function useCreativesTabOpener() {
  return {
    openInNewTab, // Прямое открытие URL
    handleOpenInNewTab, // Обработчик событий
    initializeTabOpener, // Инициализация слушателя
    isValidUrl, // Валидация URL
  };
}
```

### События Системы

#### Входное событие

```typescript
// creatives:open-in-new-tab
detail: {
  url: string; // URL для открытия
}
```

#### Исходящие события

```typescript
// creatives:open-in-new-tab-success
detail: {
  url: string,
  timestamp: number
}

// creatives:open-in-new-tab-error
detail: {
  url: string,
  error: string,
  timestamp: number
}
```

## Принцип Работы

1. **Компонент эмитирует событие** с URL для открытия
2. **Централизованный обработчик получает событие**
3. **Валидация URL** и проверка безопасности
4. **Открытие в новой вкладке** с параметрами безопасности
5. **Обратная связь** через события успеха/ошибки

## Использование в Компонентах

### Эмитирование события

```typescript
// В любом компоненте
const handleOpenInNewTab = (): void => {
  const url = props.creative.landing_url;

  if (!url) {
    console.warn('URL не доступен для открытия');
    return;
  }

  // Эмитируем DOM событие для централизованной обработки
  document.dispatchEvent(
    new CustomEvent('creatives:open-in-new-tab', {
      detail: { url },
    })
  );

  // Локальный fallback (опционально)
  window.open(url, '_blank');
};
```

### Инициализация в главном модуле

```typescript
// app.js или main.ts
import { useCreativesTabOpener } from '@/composables/useCreativesTabOpener';

const tabOpener = useCreativesTabOpener();
const cleanup = tabOpener.initializeTabOpener();

// При выходе из приложения
window.addEventListener('beforeunload', () => {
  cleanup();
});
```

## Особенности Реализации

### Валидация URL

- Проверка корректности формата URL
- Защита от XSS через валидацию
- Поддержка только HTTP/HTTPS протоколов

### Параметры Безопасности

```typescript
window.open(url, '_blank', 'noopener,noreferrer');
```

- `noopener` - защита от `window.opener`
- `noreferrer` - блокировка передачи referrer

### Обработка Popup Блокировки

```typescript
const newWindow = window.open(url, '_blank', 'noopener,noreferrer');

// Современные браузеры могут вернуть null при блокировке popup,
// но фактически открыть ссылку в новой вкладке
if (!newWindow) {
  console.warn(`Popup may be blocked, but URL should still open: ${url}`);
  // НЕ выбрасываем ошибку, так как операция может быть успешной
}
```

**Важно**: Если `window.open()` возвращает `null`, это НЕ означает ошибку. Современные браузеры (Chrome, Firefox) могут блокировать popup-окна, но все равно открывать ссылку в новой вкладке. Система логирует предупреждение, но считает операцию успешной.

### Обработка Ошибок

- Невалидные URL → исключение
- Недоступность window объекта (SSR) → исключение
- Popup блокировка → предупреждение в консоли, операция считается успешной

## Интеграция с Компонентами

### PushCreativeCard.vue

```typescript
const handleOpenInNewTab = (): void => {
  // Блокировка при загрузке
  if (isCreativesLoading.value) {
    return;
  }

  emit('open-in-new-tab', props.creative);

  // Централизованная обработка
  document.dispatchEvent(
    new CustomEvent('creatives:open-in-new-tab', {
      detail: {
        url: props.creative.landing_url,
      },
    })
  );

  // Fallback реализация
  const url = props.creative.landing_url || props.creative.main_image_url;
  if (url) {
    window.open(url, '_blank');
  }
};
```

## Тестирование

### Основные сценарии

```typescript
describe('useCreativesTabOpener', () => {
  it('должен открывать валидный URL в новой вкладке');
  it('должен обрабатывать ошибки валидации URL');
  it('должен обрабатывать блокировку popup');
  it('должен эмитировать события успеха/ошибки');
  it('должен корректно инициализировать и очищать слушатели');
});
```

### Моки для тестирования

```typescript
const mockWindowOpen = vi.fn();
Object.defineProperty(window, 'open', {
  value: mockWindowOpen,
  writable: true,
});
```

## Преимущества Системы

1. **Централизация** - единая точка обработки открытия вкладок
2. **Безопасность** - встроенная валидация и защита
3. **Мониторинг** - отслеживание успеха/ошибок через события
4. **Консистентность** - единый API для всех компонентов
5. **Тестируемость** - полное покрытие тестами

## Расширение Функциональности

### Добавление аналитики

```typescript
const handleOpenInNewTab = (event: CustomEvent<TabOpenEventDetail>): void => {
  const { url } = event.detail;

  try {
    openInNewTab(url);

    // Аналитика успеха
    analytics.track('tab_open_success', { url });
  } catch (error) {
    // Аналитика ошибок
    analytics.track('tab_open_error', { url, error });
  }
};
```

### Интеграция с роутингом

```typescript
// Проверка внутренних ссылок
const isInternalUrl = (url: string): boolean => {
  return url.startsWith(window.location.origin);
};

// Роутинг для внутренних ссылок
if (isInternalUrl(url)) {
  router.push(url);
} else {
  openInNewTab(url);
}
```

## Отладка и Мониторинг

### Логирование

```typescript
console.log(`Successfully opened URL in new tab: ${url}`);
console.error(`Failed to open URL in new tab: ${url}`, error);
```

### События для мониторинга

```typescript
// Подписка на события в DevTools
document.addEventListener('creatives:open-in-new-tab-success', e => {
  console.log('Tab opened:', e.detail);
});

document.addEventListener('creatives:open-in-new-tab-error', e => {
  console.error('Tab open failed:', e.detail);
});
```
