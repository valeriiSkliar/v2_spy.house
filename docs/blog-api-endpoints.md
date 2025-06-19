# Унифицированные API Endpoints блога

## Обзор

После оптимизации роуты блога разделены на две четкие категории:

1. **Web Routes** - только для отображения страниц
2. **API Routes** - только для AJAX запросов

## Web Routes (отображение)

```php
// Просмотр страниц - требуют валидации параметров
Route::prefix('blog')->middleware('blog.validate.params')->group(function () {
    Route::get('/', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/category/{slug}', [BlogController::class, 'byCategory'])->name('blog.category');
    Route::get('/{slug}', [BlogController::class, 'show'])->name('blog.show');
});

// Действия требующие авторизации
Route::prefix('blog')->middleware(['auth', 'throttle:10,1'])->group(function () {
    Route::post('/{slug}/rate', [BlogController::class, 'rateArticle'])->name('blog.rate');
});
```

## API Routes (AJAX)

### Базовый префикс: `/api/blog`

### Middleware: `['web', 'blog.validate.params']`

### Публичные endpoints

| Method | URL                         | Контроллер                      | Описание                   |
| ------ | --------------------------- | ------------------------------- | -------------------------- |
| GET    | `/api/blog/list`            | `ApiBlogController@ajaxList`    | Список статей с пагинацией |
| GET    | `/api/blog/search`          | `ApiBlogController@search`      | Поиск по статьям           |
| GET    | `/api/blog/{slug}/comments` | `ApiBlogController@getComments` | Получение комментариев     |

### Авторизованные endpoints

**Middleware:** `['auth:sanctum', 'throttle:10,1']`

| Method | URL                                   | Контроллер                       | Описание             |
| ------ | ------------------------------------- | -------------------------------- | -------------------- |
| POST   | `/api/blog/{slug}/comment`            | `ApiBlogController@storeComment` | Создание комментария |
| POST   | `/api/blog/{slug}/reply`              | `ApiBlogController@storeReply`   | Ответ на комментарий |
| GET    | `/api/blog/{slug}/reply/{comment_id}` | `ApiBlogController@getReplyForm` | Форма ответа         |

### Deprecated endpoints (будут удалены)

**Middleware:** `['throttle:20,1']`

| Method | URL                                  | Контроллер                           | Описание                |
| ------ | ------------------------------------ | ------------------------------------ | ----------------------- |
| GET    | `/api/blog/{slug}/comments/paginate` | `ApiBlogController@paginateComments` | Старый формат пагинации |

## Параметры запросов

### `/api/blog/list`

- `page` (int) - номер страницы (1-1000)
- `category` (string) - slug категории
- `search` (string) - поисковый запрос
- `sort` (string) - тип сортировки: `latest`, `popular`, `views`
- `direction` (string) - направление: `asc`, `desc`

### `/api/blog/search`

- `q` (string) - поисковый запрос
- `limit` (int) - количество результатов

### `/api/blog/{slug}/comments`

- `page` (int) - номер страницы
- `sort` (string) - сортировка комментариев

## Ответы API

### Успешный ответ

```json
{
  "success": true,
  "data": {...},
  "pagination": {...}
}
```

### Ошибка валидации

```json
{
  "success": false,
  "error": "Validation error message",
  "redirect": true,
  "url": "/corrected-url"
}
```

### Редирект

```json
{
  "redirect": true,
  "url": "/new-url"
}
```

## Middleware

### `blog.validate.params`

- Валидация параметров page, category, search
- Автоматические редиректы на корректные URL

### `auth:sanctum`

- Проверка авторизации через Sanctum
- Поддержка как web сессий, так и API токенов

### `throttle:10,1` / `throttle:20,1`

- Ограничение запросов: 10/20 в минуту

## Кеширование

API endpoints используют кеширование:

- Ключ: `blog_query_{hash}`
- TTL: 300 секунд (5 минут)
- Кешируются: поиск и фильтрация по категориям

## Миграция

Старые endpoints автоматически перенаправляются:

- `/blog/{slug}/comments` → `/api/blog/{slug}/comments`
- `/blog/{slug}/comment` → `/api/blog/{slug}/comment`

JavaScript уже обновлен для использования новых endpoints.
