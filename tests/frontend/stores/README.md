# Тесты Blog Store

## Описание

Тесты для базовой функциональности `blog-store.js`, обеспечивающей реактивность системы управления состоянием блога.

## Файлы тестов

### `blog-store-simple.test.js` ✅ Готов

Упрощенные тесты базовой функциональности без сложного мокинга URL и браузерных API.

**Покрытие (23 теста):**

#### 1. Базовое состояние и реактивность (5 тестов)

- ✅ Корректное начальное состояние
- ✅ Установка состояния загрузки (`setLoading`)
- ✅ Установка массива статей (`setArticles`)
- ✅ Установка категорий (`setCategories`)
- ✅ Обновление данных пагинации (`setPagination`)

#### 2. Фильтры и их реактивность (3 теста)

- ✅ Обновление фильтров (`setFilters`)
- ✅ Синхронизация `pagination.currentPage` с `filters.page`
- ✅ Сброс фильтров к начальным значениям (`resetFilters`)

#### 3. Computed Properties (4 теста)

- ✅ Определение первой страницы (`isFirstPage`)
- ✅ Определение последней страницы (`isLastPage`)
- ✅ Определение наличия результатов (`hasResults`)
- ✅ Определение активных фильтров (`isFiltered`)

#### 4. Статистика и UI состояние (2 теста)

- ✅ Обновление статистики (`setStats`)
- ✅ Обновление UI состояния (`setUIState`)

#### 5. Состояние комментариев (3 теста)

- ✅ Установка списка комментариев (`setComments`, `hasComments`)
- ✅ Управление состоянием загрузки (`setCommentsLoading`, `isCommentsLoading`)
- ✅ Управление режимом ответа (`setReplyMode`, `clearReplyMode`, `isReplyMode`)

#### 6. Состояние рейтинга (3 теста)

- ✅ Установка рейтинга (`setRating`, `canRate`)
- ✅ Управление состоянием отправки (`setRatingSubmitting`, `isRatingSubmitting`)
- ✅ Обновление рейтинга (`updateRating`)

#### 7. Методы сброса состояния (3 теста)

- ✅ Полный сброс состояния (`resetState`)
- ✅ Сброс состояния комментариев (`resetCommentsState`)
- ✅ Сброс состояния рейтинга (`resetRatingState`)

### `blog-store.test.js` ⚠️ Требует доработки

Полный набор тестов с мокингом браузерных API (URL, History, SessionStorage).

**Статус:** Частично рабочий, требуется улучшение URL мокинга для стабильной работы.

## Запуск тестов

```bash
# Запуск упрощенных тестов (рекомендуется)
npx vitest run tests/frontend/stores/blog-store-simple.test.js

# Запуск всех тестов stores
npx vitest run tests/frontend/stores/

# Watch режим для разработки
npx vitest tests/frontend/stores/blog-store-simple.test.js
```

## Исправленные проблемы

### ✅ Computed Property `isFiltered`

**Проблема:** Возвращал первое truthy значение вместо boolean  
**Исправление:** Добавлено приведение к boolean с помощью `!!`

```javascript
// Было:
get isFiltered() {
  return this.filters.category || this.filters.search || this.filters.sort !== 'latest';
}

// Стало:
get isFiltered() {
  return !!(this.filters.category || this.filters.search || this.filters.sort !== 'latest');
}
```

## Следующие итерации

В следующих итерациях планируется добавить тесты для:

1. **Навигационные методы** - `goToPage`, `setCategory`, `setSearch`, `setSort`
2. **URL синхронизация** - `updateURL`, `initFromURL`, `handlePopState`
3. **Персистентность** - работа с `sessionStorage`
4. **Валидация** - `validateFilters`
5. **Интеграционные тесты** - взаимодействие с `blogAjaxManager`

## Команда тестирования

Используется **Vitest** с настройкой `environment: 'jsdom'` для эмуляции браузерной среды.

Конфигурация: `vitest.config.js` в корне проекта.
