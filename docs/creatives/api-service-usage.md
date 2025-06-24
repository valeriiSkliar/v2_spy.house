# Creatives TypeScript API Service

## Обзор

Базовый TypeScript API сервис для работы с разделом креативов, построенный на основе `axios` и `axios-cache-interceptor`. Предоставляет типизированный интерфейс для HTTP запросов с автоматическим кэшированием.

## Особенности

- ✅ **TypeScript типизация** - полная поддержка типов
- ✅ **Автоматическое кэширование** - GET запросы кэшируются на 5 минут
- ✅ **CSRF защита** - автоматическое добавление CSRF токена
- ✅ **Обработка ошибок** - кастомный класс `ApiError` с типизированными проверками
- ✅ **Interceptor поддержка** - настройка request/response перехватчиков

## Быстрый старт

### Импорт и использование

```typescript
import { creativesApiService, ApiError } from '@/stores/creativesApiService.ts';

// GET запрос с кэшированием
try {
  const response = await creativesApiService.get('/creatives');
  console.log(response.data);
} catch (error) {
  if (error instanceof ApiError) {
    console.error('API Error:', error.message, error.status);
  }
}
```

### Базовые HTTP методы

```typescript
// GET запрос (автоматически кэшируется)
const data = await creativesApiService.get<Creative[]>('/creatives');

// POST запрос
const result = await creativesApiService.post('/creatives', {
  name: 'New Creative',
  category: 'design',
});

// PUT запрос для обновления
const updated = await creativesApiService.put('/creatives/123', {
  name: 'Updated Creative',
});

// DELETE запрос
await creativesApiService.delete('/creatives/123');
```

## Конфигурация кэша

### Настройка кэша для конкретного запроса

```typescript
// Отключить кэш для конкретного запроса
const freshData = await creativesApiService.get('/creatives', {
  cache: false,
});

// Настроить TTL для запроса
const data = await creativesApiService.get('/creatives', {
  cache: {
    ttl: 10 * 60 * 1000, // 10 минут
  },
});

// Задать ID для кэша
const data = await creativesApiService.get('/creatives', {
  id: 'creatives-list',
  cache: {
    ttl: 5 * 60 * 1000,
  },
});
```

### Управление кэшем

```typescript
// Очистить весь кэш
creativesApiService.clearCache();

// Удалить конкретную запись из кэша
await creativesApiService.removeCacheEntry('creatives-list');
```

## Обработка ошибок

### ApiError класс

```typescript
try {
  const response = await creativesApiService.get('/protected-route');
} catch (error) {
  if (error instanceof ApiError) {
    // Проверка типа ошибки
    if (error.isUnauthorized) {
      // Перенаправить на логин
      router.push('/login');
    } else if (error.isValidationError) {
      // Показать ошибки валидации
      console.log('Validation errors:', error.data.errors);
    } else if (error.isServerError) {
      // Показать сообщение о серверной ошибке
      showToast('Серверная ошибка, попробуйте позже');
    }
  }
}
```

### Доступные проверки ошибок

- `error.isNetworkError` - ошибка сети (status: 0)
- `error.isServerError` - серверная ошибка (status: 500+)
- `error.isClientError` - клиентская ошибка (status: 400-499)
- `error.isValidationError` - ошибка валидации (status: 422)
- `error.isUnauthorized` - неавторизован (status: 401)
- `error.isForbidden` - доступ запрещен (status: 403)
- `error.isNotFound` - не найдено (status: 404)

## Типизация

### Интерфейсы

```typescript
interface ApiResponse<T = any> {
  data: T;
  message?: string;
  success: boolean;
}

interface ApiServiceConfig {
  baseUrl?: string;
  timeout?: number;
  debug?: boolean;
}
```

### Использование с типами

```typescript
interface Creative {
  id: number;
  name: string;
  category: string;
  file_url: string;
}

// Типизированный запрос
const creatives = await creativesApiService.get<Creative[]>('/creatives');
// creatives.data будет типа Creative[]

const creative = await creativesApiService.post<Creative>('/creatives', data);
// creative.data будет типа Creative
```

## Создание кастомного сервиса

```typescript
import CreativesApiService from '@/stores/creativesApiService.ts';

// Создание с кастомной конфигурацией
const customApiService = new CreativesApiService({
  baseUrl: '/api/v2',
  timeout: 60000,
  debug: true,
});
```

## Архитектурные особенности

### Автоматические функции

1. **CSRF токен** - автоматически извлекается из `<meta name="csrf-token">` и добавляется к запросам
2. **Кэширование** - GET запросы кэшируются автоматически на 5 минут
3. **Дедупликация** - одинаковые запросы объединяются
4. **Interceptors** - request/response перехватчики настроены автоматически

### Конфигурация по умолчанию

- **Base URL**: `/api`
- **Timeout**: 30 секунд
- **Cache TTL**: 5 минут
- **Кэшируемые методы**: только GET
- **Debug**: включен только в dev режиме

## Следующие этапы

Планируется добавить:

1. **Специфичные методы для креативов** (getCreatives, uploadCreative и т.д.)
2. **Работа с файлами** (upload, download)
3. **Расширенное управление кэшем**
4. **Retry механизм**
5. **Interceptors для авторизации**

---

**Статус**: ✅ Базовая функциональность готова  
**Версия**: 1.0.0  
**Последнее обновление**: Сегодня
