# Исправление проблемы синхронизации избранного

## Описание проблемы

**Симптомы:**

- Фронтенд показывает `isFavorite: false` для креатива
- При клике на "добавить в избранное" получаем ошибку 409 (Conflict)
- Консоль показывает: `POST http://localhost:8000/api/creatives/25753/favorite 409 (Conflict)`

**Причина:**
Рассинхронизация между локальным состоянием фронтенда и реальным состоянием в базе данных.

## Улучшения в бэкенде (уже реализованы)

### 1. Улучшенная ошибка 409

Теперь при ошибке 409 сервер возвращает:

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

### 2. Новый endpoint для проверки статуса

```http
GET /api/creatives/{id}/favorite/status
```

Возвращает актуальный статус избранного для креатива.

## Необходимые изменения во фронтенде

### 1. Обновить обработку ошибки 409

В файле `useFiltersStore.ts` (или аналогичном) найти обработку ошибок в функции `addToFavorites`:

```typescript
// СТАРЫЙ КОД:
catch (error) {
  console.error('Ошибка при добавлении в избранное:', error);
}

// НОВЫЙ КОД:
catch (error) {
  if (error.response?.status === 409 && error.response?.data?.code === 'ALREADY_IN_FAVORITES') {
    // Синхронизируем состояние с сервером
    const syncData = error.response.data.data;

    // Обновляем локальное состояние
    this.updateCreativeInList(syncData.creativeId, {
      isFavorite: syncData.isFavorite
    });

    // Обновляем общий счетчик
    this.favoritesCount = syncData.totalFavorites;

    // Показываем пользователю информативное сообщение
    this.showMessage('Креатив уже в избранном', 'info');

    return; // Выходим, не показывая ошибку
  }

  console.error('Ошибка при добавлении в избранное:', error);
  this.showMessage('Ошибка при добавлении в избранное', 'error');
}
```

### 2. Добавить функцию синхронизации статуса

```typescript
// Добавить в store или composable
async syncFavoriteStatus(creativeId: number) {
  try {
    const response = await this.api.get(`/api/creatives/${creativeId}/favorite/status`);
    const data = response.data.data;

    // Обновляем локальное состояние
    this.updateCreativeInList(data.creativeId, {
      isFavorite: data.isFavorite
    });

    // Обновляем общий счетчик
    this.favoritesCount = data.totalFavorites;

    return data;
  } catch (error) {
    console.error('Ошибка синхронизации статуса избранного:', error);
    throw error;
  }
}
```

### 3. Обновить обработку ошибки 404

Аналогично для функции `removeFromFavorites`:

```typescript
catch (error) {
  if (error.response?.status === 404 && error.response?.data?.code === 'NOT_IN_FAVORITES') {
    // Синхронизируем состояние с сервером
    const syncData = error.response.data.data;

    this.updateCreativeInList(syncData.creativeId, {
      isFavorite: syncData.isFavorite
    });

    this.favoritesCount = syncData.totalFavorites;
    this.showMessage('Креатив не найден в избранном', 'info');

    return;
  }

  console.error('Ошибка при удалении из избранного:', error);
  this.showMessage('Ошибка при удалении из избранного', 'error');
}
```

### 4. Добавить функцию обновления креатива в списке

```typescript
updateCreativeInList(creativeId: number, updates: Partial<Creative>) {
  // Обновляем в основном списке креативов
  const creative = this.creatives.find(c => c.id === creativeId);
  if (creative) {
    Object.assign(creative, updates);
  }

  // Обновляем в кэше деталей, если есть
  if (this.creativesCache[creativeId]) {
    Object.assign(this.creativesCache[creativeId], updates);
  }
}
```

## Быстрое исправление для текущей проблемы

Если нужно быстро исправить проблему с креативом 25753:

```typescript
// Временное решение - принудительная синхронизация
async function quickFix() {
  try {
    const response = await api.get('/api/creatives/25753/favorite/status');
    const data = response.data.data;

    // Обновить состояние в store
    store.updateCreativeInList(25753, { isFavorite: data.isFavorite });
    store.favoritesCount = data.totalFavorites;

    console.log('Статус синхронизирован:', data);
  } catch (error) {
    console.error('Ошибка синхронизации:', error);
  }
}
```

## Тестирование

1. Найти креатив, который показывает неправильный статус
2. Кликнуть на кнопку избранного
3. Убедиться, что:
   - Не показывается ошибка пользователю
   - Статус кнопки обновляется корректно
   - Счетчик избранного обновляется
   - В консоли нет ошибок

## Профилактика

1. **Всегда обновлять локальное состояние** после успешных API операций
2. **Обрабатывать коды ошибок** для автоматической синхронизации
3. **Использовать оптимистичные обновления** с откатом при ошибках
4. **Периодически синхронизировать** критичные данные (при фокусе окна, после длительного бездействия)
