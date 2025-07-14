# Система избранного для креативов

## Обзор

Реализована полнофункциональная система избранного для креативов, позволяющая пользователям добавлять и удалять креативы из персонального списка избранного.

## Архитектура

### База данных

**Таблица `favorites`**

- `id` - первичный ключ
- `user_id` - внешний ключ на таблицу users
- `creative_id` - внешний ключ на таблицу creatives
- `created_at`, `updated_at` - временные метки
- Уникальный составной ключ `(user_id, creative_id)` для предотвращения дублирования
- Индексы на `user_id` и `creative_id` для оптимизации запросов

### Модели

**Favorite** (`app/Models/Favorite.php`)

- Основная модель для работы с избранным
- Статические методы: `addToFavorites()`, `removeFromFavorites()`, `isFavorite()`, `getFavoritesCount()`
- Связи с моделями User и Creative

**User** (обновлена)

- Добавлена связь `favoriteCreatives()` - Many-to-Many через таблицу favorites
- Методы: `hasFavoriteCreative()`, `getFavoritesCount()`

**Creative** (обновлена)

- Добавлена связь `favoritedByUsers()` - Many-to-Many через таблицу favorites
- Методы: `isFavoritedBy()`, `getFavoritesCount()`

### Контроллеры

**BaseCreativesController** (обновлен)

- `getFavoritesCount()` - получить количество избранных креативов
- `addToFavorites()` - добавить креатив в избранное
- `removeFromFavorites()` - удалить креатив из избранного

**FavoriteController** (новый, опциональный)

- Полнофункциональный API контроллер с расширенными методами
- `index()` - список избранных с пагинацией
- `store()` - добавление в избранное
- `show()` - показать конкретный избранный креатив
- `destroy()` - удаление из избранного
- `count()` - количество избранных
- `check()` - проверка статуса избранного

## API Endpoints

Существующие маршруты в `routes/creatives.php`:

```php
// Получить количество избранных
GET /api/creatives/favorites/count

// Добавить креатив в избранное
POST /api/creatives/{id}/favorite

// Удалить креатив из избранного
DELETE /api/creatives/{id}/favorite
```

## Использование

### Добавление в избранное

```javascript
// POST /api/creatives/123/favorite
{
  "status": "success",
  "data": {
    "creativeId": 123,
    "isFavorite": true,
    "totalFavorites": 43,
    "addedAt": "2024-01-15T10:30:00.000000Z"
  }
}
```

### Удаление из избранного

```javascript
// DELETE /api/creatives/123/favorite
{
  "status": "success",
  "data": {
    "creativeId": 123,
    "isFavorite": false,
    "totalFavorites": 42,
    "removedAt": "2024-01-15T10:35:00.000000Z"
  }
}
```

### Получение количества

```javascript
// GET /api/creatives/favorites/count
{
  "status": "success",
  "data": {
    "count": 42,
    "lastUpdated": "2024-01-15T10:30:00.000000Z"
  }
}
```

## Безопасность

- Все endpoints требуют аутентификации
- Проверка существования креатива перед добавлением
- Защита от дублирования через уникальный составной ключ
- Проверка принадлежности избранного пользователю

## Обработка ошибок

- **401** - Пользователь не аутентифицирован
- **404** - Креатив не найден / не найден в избранном
- **409** - Креатив уже в избранном
- **422** - Ошибка валидации
- **500** - Внутренняя ошибка сервера

## Обработка ошибок синхронизации

### Проблема рассинхронизации

Иногда может возникать ситуация, когда фронтенд считает, что креатив не в избранном (`isFavorite: false`), но при попытке добавить его получает ошибку 409 "Already in favorites". Это происходит из-за рассинхронизации между локальным состоянием фронтенда и реальным состоянием в БД.

### Улучшенная обработка ошибок

#### Ошибка 409 (Already in Favorites)

```json
{
  "status": "error",
  "message": "Creative already in favorites",
  "code": "ALREADY_IN_FAVORITES",
  "data": {
    "creativeId": 25753,
    "isFavorite": true,
    "totalFavorites": 43,
    "addedAt": "2024-01-15T10:30:00Z",
    "shouldSync": true
  }
}
```

**Рекомендации для фронтенда:**

1. При получении `code: "ALREADY_IN_FAVORITES"` и `shouldSync: true` - обновить локальное состояние
2. Установить `isFavorite: true` для данного креатива
3. Обновить счетчик избранного из `totalFavorites`
4. Показать пользователю, что креатив уже в избранном

#### Ошибка 404 (Not in Favorites)

```json
{
  "status": "error",
  "message": "Creative not found in favorites",
  "code": "NOT_IN_FAVORITES",
  "data": {
    "creativeId": 25753,
    "isFavorite": false,
    "totalFavorites": 41,
    "shouldSync": true
  }
}
```

**Рекомендации для фронтенда:**

1. При получении `code: "NOT_IN_FAVORITES"` и `shouldSync: true` - обновить локальное состояние
2. Установить `isFavorite: false` для данного креатива
3. Обновить счетчик избранного из `totalFavorites`

### Новый API endpoint для проверки статуса

#### GET /api/creatives/{id}/favorite/status

Позволяет проверить актуальный статус избранного для конкретного креатива.

**Запрос:**

```http
GET /api/creatives/25753/favorite/status
Authorization: Bearer {token}
```

**Ответ:**

```json
{
  "status": "success",
  "data": {
    "creativeId": 25753,
    "isFavorite": true,
    "totalFavorites": 42,
    "addedAt": "2024-01-15T10:30:00Z",
    "checkedAt": "2024-01-15T12:45:00Z"
  }
}
```

**Использование:**

- При обнаружении рассинхронизации
- Для валидации локального состояния
- При восстановлении после ошибок сети

### Рекомендуемая стратегия обработки ошибок

```javascript
async function handleFavoriteToggle(creativeId, currentState) {
  try {
    if (currentState.isFavorite) {
      await removeFromFavorites(creativeId);
    } else {
      await addToFavorites(creativeId);
    }
  } catch (error) {
    if (error.response?.status === 409 && error.response?.data?.code === 'ALREADY_IN_FAVORITES') {
      // Синхронизируем состояние
      const syncData = error.response.data.data;
      updateLocalState(syncData.creativeId, {
        isFavorite: syncData.isFavorite,
        totalFavorites: syncData.totalFavorites,
      });
      showMessage('Креатив уже в избранном');
    } else if (
      error.response?.status === 404 &&
      error.response?.data?.code === 'NOT_IN_FAVORITES'
    ) {
      // Синхронизируем состояние
      const syncData = error.response.data.data;
      updateLocalState(syncData.creativeId, {
        isFavorite: syncData.isFavorite,
        totalFavorites: syncData.totalFavorites,
      });
      showMessage('Креатив не найден в избранном');
    } else {
      // Проверяем актуальный статус
      await syncFavoriteStatus(creativeId);
      throw error; // Повторно выбрасываем для общей обработки
    }
  }
}

async function syncFavoriteStatus(creativeId) {
  try {
    const response = await api.get(`/api/creatives/${creativeId}/favorite/status`);
    const data = response.data.data;
    updateLocalState(data.creativeId, {
      isFavorite: data.isFavorite,
      totalFavorites: data.totalFavorites,
    });
  } catch (error) {
    console.error('Failed to sync favorite status:', error);
  }
}
```

### Профилактика рассинхронизации

1. **Обновление после операций**: Всегда обновляйте локальное состояние после успешных операций
2. **Обработка ошибок**: Используйте коды ошибок для автоматической синхронизации
3. **Периодическая проверка**: Синхронизируйте критичные данные при фокусе окна
4. **Оптимистичные обновления**: Обновляйте UI сразу, но откатывайте при ошибках

## Производительность

- Индексы на часто используемые поля
- Кеширование количества избранных (можно добавить)
- Оптимизированные запросы с использованием Eloquent отношений
- Пагинация для больших списков избранного

## Интеграция с существующим кодом

### CreativeDTO

- Реализована проверка избранного в `checkIfFavorite()`
- Добавлена батчевая оптимизация в `batchCheckFavorites()` для списков
- Автоматическое вычисление `isFavorite` для аутентифицированных пользователей

### BaseCreativesController

- Добавлен helper метод `checkIsFavorite()` для внутреннего использования
- Обновлены методы избранного для использования реальной логики

### CreativesController

- Обновлен метод `getCreativeDetails()` для показа статуса избранного
- Интеграция с существующей DTO архитектурой

## Тестирование

Система протестирована через:

- Прямые вызовы методов моделей
- DTO преобразования
- Controller endpoints
- Database операции

## Расширения

Система готова для добавления:

- Категоризация избранного
- Публичные списки избранного
- Экспорт избранного
- Уведомления об изменениях в избранных креативах
- Аналитика по избранному
- Push уведомления при добавлении в избранное
- Синхронизация между устройствами
