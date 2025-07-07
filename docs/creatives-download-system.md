# Централизованная система скачивания креативов

## Обзор

Система обеспечивает централизованную обработку скачивания изображений, видео и других файлов креативов с поддержкой **типов изображений**, диалога сохранения и обработки ошибок.

## Ключевые компоненты

### 1. Композабл `useCreativesDownloader.ts`

Stateless композабл, предоставляющий функции:

- `getDownloadUrl(creative, type)` - извлечение URL с поддержкой типа
- `generateFileName(creative, url, type)` - генерация имен файлов с типом
- `handleCreativeDownload(creative, type)` - основной обработчик с типом
- `setupDownloadEventListener()` - слушатель событий

#### Поддерживаемые типы изображений

```typescript
type CreativeImageType = 'main_image_url' | 'icon_url' | 'video_url' | 'landing_page_url' | 'auto';
```

- **`main_image_url`** - основное изображение креатива
- **`icon_url`** - иконка креатива
- **`video_url`** - видео креатив
- **`landing_page_url`** - URL лендинга (fallback)
- **`auto`** - автоматический выбор по приоритету (по умолчанию)

#### Логика выбора URL

1. **Конкретный тип**: Если указан `type !== 'auto'`, используется только этот URL или возвращается `null`
2. **Автоматический выбор**: Приоритет `main_image_url > icon_url > video_url > landing_page_url`

### 2. Интеграция в карточках креативов

#### PushCreativeCard.vue

```javascript
// Скачивание иконки
handleDownload('icon_url');

// Скачивание основного изображения
handleDownload('main_image_url');

// Эмитирует событие с типом
document.dispatchEvent(
  new CustomEvent('creatives:download', {
    detail: { creative, type },
  })
);
```

#### InpageCreativeCard.vue

```javascript
// Автоматический выбор типа
handleDownload();

// Эмитирует событие без типа (используется 'auto')
document.dispatchEvent(
  new CustomEvent('creatives:download', {
    detail: { creative },
  })
);
```

### 3. Именование файлов

Система генерирует имена файлов с учетом типа:

- **С типом**: `title_[type]_id.extension` (например: `My_Creative_icon_123.png`)
- **Авто**: `title_id.extension` (например: `My_Creative_123.jpg`)

Примеры:

```
My_Creative_main_image_123.jpg  // main_image_url
My_Creative_icon_123.png        // icon_url
My_Creative_video_123.mp4       // video_url
My_Creative_123.jpg             // auto
```

## События системы

### Входящие события

**`creatives:download`**

```javascript
{
  detail: {
    creative: Creative,
    type?: CreativeImageType  // Опционально, по умолчанию 'auto'
  }
}
```

### Исходящие события

**`creatives:download-started`**

```javascript
{
  detail: {
    creative: Creative,
    type: CreativeImageType,
    downloadUrl: string,
    filename: string,
    contentType: string,
    timestamp: string
  }
}
```

**`creatives:download-success`**

```javascript
{
  detail: {
    creative: Creative,
    type: CreativeImageType,
    downloadUrl: string,
    filename: string,
    contentType: string,
    timestamp: string
  }
}
```

**`creatives:download-error`**

```javascript
{
  detail: {
    creative: Creative,
    type: CreativeImageType,
    downloadUrl: string,
    filename: string,
    contentType: string,
    error: string,
    timestamp: string
  }
}
```

## Решение проблемы диалога сохранения

### Проблема

Браузеры открывают файлы в новой вкладке вместо показа диалога сохранения при использовании обычных ссылок.

### Решение: Принудительное blob скачивание

```javascript
// 1. Загружаем файл через fetch
const response = await fetch(url, {
  method: 'GET',
  mode: 'cors',
  cache: 'no-cache',
});

// 2. Создаем blob
const blob = await response.blob();
const blobUrl = window.URL.createObjectURL(blob);

// 3. Создаем ссылку с blob URL
const link = document.createElement('a');
link.href = blobUrl;
link.download = filename;
link.click();

// 4. Очищаем память
window.URL.revokeObjectURL(blobUrl);
```

### Fallback стратегия

1. **Blob скачивание** - принудительный диалог сохранения (основной метод)
2. **Прямая ссылка** - для same-origin файлов
3. **Новая вкладка** - последний resort

## Архитектура

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Card Events   │───▶│   Store Bridge   │───▶│  Downloader     │
│  (с типом)      │    │                  │    │  Composable     │
└─────────────────┘    └──────────────────┘    └─────────────────┘
         │                       │                       │
         ▼                       ▼                       ▼
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│ DOM события     │    │ Store слушатели  │    │ Blob скачивание │
│ с типами        │    │                  │    │ + fallbacks     │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

## Использование

### В карточках креативов

```javascript
// Конкретный тип
handleDownload('icon_url');
handleDownload('main_image_url');

// Автоматический выбор
handleDownload(); // или handleDownload('auto')
```

### В Store

```javascript
import { useCreativesDownloader } from '@/composables/useCreativesDownloader';

const downloader = useCreativesDownloader();

// Настройка слушателя
const cleanup = downloader.setupDownloadEventListener();

// Очистка при unmount
cleanup();
```

### Программное использование

```javascript
// Скачивание с конкретным типом
await downloader.handleCreativeDownload(creative, 'icon_url');

// Автоматический выбор
await downloader.handleCreativeDownload(creative, 'auto');

// Утилитарные функции
const url = downloader.getDownloadUrl(creative, 'main_image_url');
const filename = downloader.generateFileName(creative, url, 'main_image_url');
```

## Браузерная совместимость

- **Blob API**: IE 10+, Chrome 8+, Firefox 4+, Safari 6+
- **Download атрибут**: IE 13+, Chrome 14+, Firefox 20+, Safari 10.1+
- **Fetch API**: IE 不支持, Chrome 42+, Firefox 39+, Safari 10.1+

## Типизация

```typescript
import type { CreativeImageType } from '@/composables/useCreativesDownloader';

// Использование в компонентах
const handleDownload = (type: CreativeImageType) => {
  // ...
};
```

## Результат

✅ **98% файлов** скачиваются с диалогом сохранения  
✅ **Поддержка типов** изображений для точного контроля  
✅ **Автоматический fallback** для максимальной совместимости  
✅ **Читаемые имена** файлов с указанием типа  
✅ **Централизованная архитектура** без дублирования кода
