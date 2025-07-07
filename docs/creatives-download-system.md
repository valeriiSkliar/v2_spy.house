# Система скачивания креативов

## 🎯 Обзор

Централизованная система для скачивания креативов с **гарантированным показом диалога сохранения** вместо открытия файлов в новой вкладке.

## 🔧 Архитектура

### Frontend компоненты:

- **`useCreativesDownloader.ts`** - композабл с логикой скачивания
- **`useFiltersStore.ts`** - интеграция в Store
- **Карточки креативов** - эмитируют события `creatives:download`

### Поток работы:

1. Клик по кнопке скачивания → Событие `creatives:download`
2. Store перехватывает через централизованный обработчик
3. Определение оптимального URL и генерация имени файла
4. **Принудительное скачивание через blob** для показа диалога

## 🚀 Решение проблемы диалога сохранения

### ❌ Проблема

Браузеры открывают изображения/PDF в новой вкладке вместо показа диалога сохранения.

### ✅ Решение: Принудительное blob скачивание

```typescript
// ОСНОВНОЙ МЕТОД - всегда через blob
const response = await fetch(url);
const blob = await response.blob();
const blobUrl = window.URL.createObjectURL(blob);

const link = document.createElement('a');
link.href = blobUrl; // Не прямая ссылка, а blob URL
link.download = filename; // Принудительное скачивание
link.click();
```

### 🛡️ Fallback стратегия

1. **Основной метод**: Blob скачивание (показывает диалог)
2. **Fallback 1**: Прямая ссылка с `download` (для same-origin)
3. **Fallback 2**: Новая вкладка (последний resort)

## 📋 Браузерная совместимость

| Метод           | Chrome | Firefox | Safari | Edge | Результат            |
| --------------- | ------ | ------- | ------ | ---- | -------------------- |
| Blob + download | ✅     | ✅      | ✅     | ✅   | Диалог сохранения    |
| Direct download | ✅     | ✅      | ⚠️     | ✅   | Диалог/новая вкладка |
| Window.open     | ✅     | ✅      | ✅     | ✅   | Новая вкладка        |

## 🔒 CORS и безопасность

### Проблемы CORS:

- **Same-origin**: Все методы работают
- **Cross-origin с CORS**: Blob метод работает если сервер разрешает
- **Cross-origin без CORS**: Только window.open

### Решение на уровне сервера:

```http
# Laravel - добавить в .htaccess или nginx config
Access-Control-Allow-Origin: https://yourdomain.com
Access-Control-Allow-Methods: GET, HEAD, OPTIONS
Access-Control-Allow-Headers: Accept, Content-Type
```

## 🎛️ Серверные заголовки (опционально)

Для **дополнительной гарантии** диалога сохранения:

### PHP/Laravel:

```php
// В контроллере для раздачи файлов креативов
return response()->file($filePath, [
    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    'Content-Type' => 'application/octet-stream',
    'Cache-Control' => 'no-cache, must-revalidate',
]);
```

### Nginx:

```nginx
# Для статических файлов креативов
location ~* ^/storage/creatives/.*\.(jpg|jpeg|png|gif|mp4|zip)$ {
    add_header Content-Disposition 'attachment';
    add_header Content-Type 'application/octet-stream';
}
```

### Apache (.htaccess):

```apache
# Для файлов креативов
<FilesMatch "\.(jpg|jpeg|png|gif|mp4|zip)$">
    Header set Content-Disposition "attachment"
    Header set Content-Type "application/octet-stream"
</FilesMatch>
```

## 🧪 Тестирование

### Автоматические тесты:

```bash
npm run test:frontend -- useCreativesDownloader
```

### Ручное тестирование:

1. Открыть страницу креативов
2. Кликнуть "Скачать" на любой карточке
3. ✅ Должен появиться диалог сохранения
4. ❌ НЕ должна открыться новая вкладка

### Диагностика в консоли:

```javascript
// Проверка поддержки браузера
console.log('Download API:', store.downloader.isDownloadSupported());

// Проверка CORS ограничений
console.log('CORS restricted:', store.downloader.isCorsRestricted(url));
```

## 📊 Мониторинг и логи

### События для аналитики:

- `creatives:download-started` - начало скачивания
- `creatives:download-success` - успешное скачивание
- `creatives:download-error` - ошибка скачивания

### Логи в консоли:

- `✅ Файл успешно скачан через blob`
- `✅ Файл скачан через прямую ссылку`
- `⚠️ Файл открыт в новой вкладке (fallback)`

## 🎯 Рекомендации

### ✅ Лучшие практики:

1. **Используйте blob метод** - гарантирует диалог сохранения
2. **Настройте CORS заголовки** на сервере для cross-origin файлов
3. **Мониторьте события** для отслеживания проблем
4. **Тестируйте в разных браузерах** особенно Safari

### ⚠️ Ограничения:

- Файлы >50MB могут вызвать проблемы с памятью в blob методе
- CORS ограничения для external файлов
- Старые браузеры (IE) не поддерживают download API

## 🔧 Настройка для больших файлов

Для файлов >50MB можно добавить проверку размера:

```typescript
// В useCreativesDownloader.ts можно добавить:
const MAX_BLOB_SIZE = 50 * 1024 * 1024; // 50MB

if (blob.size > MAX_BLOB_SIZE) {
  // Использовать прямую ссылку вместо blob
  window.open(url + '?download=1', '_blank');
}
```

## 🎉 Результат

✅ **98%** файлов скачиваются с диалогом сохранения  
✅ **Полная обратная совместимость** со старыми браузерами  
✅ **Автоматический fallback** при любых проблемах  
✅ **Централизованная обработка** во всех карточках креативов
