# Blog API Architecture - После рефакторинга

## 🏗️ Архитектура с разделением ответственностей

### **Слои ответственности**

```
┌─────────────────────────────────────────────┐
│                Components                   │ ← UI логика + проксирование
│  (blog-simple-components.js)               │
└─────────────────┬───────────────────────────┘
                  │ calls operations API
┌─────────────────▼───────────────────────────┐
│              Manager                        │ ← AJAX + координация + бизнес-логика
│  (blog-ajax-manager.js)                     │
└─────────────────┬───────────────────────────┘
                  │ updates state
┌─────────────────▼───────────────────────────┐
│               Store                         │ ← Только состояние + computed
│  (blog-store.js)                            │
└─────────────────────────────────────────────┘
```

## 📋 API Интерфейсы

### **1. Store State API**

_Чистое управление состоянием_

```javascript
// Доступ через: store.stateAPI.* или this.$blog.state.*

// Геттеры состояния (read-only)
this.$blog.state.getLoading();
this.$blog.state.getFilters();
this.$blog.state.getArticles();
this.$blog.state.getPagination();

// Сеттеры состояния (контролируемая мутация)
this.$blog.state.setLoading(true);
this.$blog.state.setFilters({ page: 2 });
this.$blog.state.setArticles(articles);

// Computed properties
this.$blog.state.isFirstPage();
this.$blog.state.hasResults();
this.$blog.state.hasActiveSearch();
```

### **2. Store URL API**

_Чистая синхронизация URL и состояния_

```javascript
// Доступ через: store.urlAPI.* или this.$blog.url.*

this.$blog.url.getCurrentUrl(); // получить текущий URL
this.$blog.url.isStateSynced(); // проверить синхронизацию
this.$blog.url.updateUrl(true); // обновить URL
this.$blog.url.restoreFromUrl(); // восстановить из URL
this.$blog.url.forceSync(); // принудительная синхронизация
```

### **3. Manager Operations API**

_Бизнес-операции и координация_

```javascript
// Доступ через: blogAjaxManager.operationsAPI.* или this.$blog.operations().*

// Навигация
this.$blog.operations().goToPage(3);
this.$blog.operations().goToNextPage();
this.$blog.operations().setCategory('tech');
this.$blog.operations().setSearch('query');
this.$blog.operations().clearFilters();

// Контент
this.$blog.operations().refreshContent();
this.$blog.operations().validateAndNavigate({ page: 2, search: 'test' });

// Редиректы (moved from store)
this.$blog.operations().handleRedirect('/blog?page=2');
this.$blog.operations().cleanRedirect();
```

## 🔧 Использование в компонентах

### **Правильный подход:**

```javascript
// ❌ НЕ ДЕЛАТЬ - прямые вызовы store навигационных методов
Alpine.store('blog').goToPage(2);
Alpine.store('blog').setSearch('query');

// ✅ ПРАВИЛЬНО - использовать Operations API
this.$blog.operations().goToPage(2);
this.$blog.operations().setSearch('query');

// ✅ ПРАВИЛЬНО - читать состояние через State API
this.$blog.state.getLoading();
this.$blog.state.hasResults();

// ✅ ПРАВИЛЬНО - для удобства, прямой доступ к read-only состоянию
this.$blog.loading();
this.$blog.filters();
```

### **Миграция компонентов:**

```javascript
// Alpine.js Component - ПОСЛЕ рефакторинга
Alpine.data('blogComponent', () => ({
  // UI логика только
  handleClick() {
    // Делегируем операции в Manager
    this.$blog.operations().goToPage(2);
  },

  // Читаем состояние из Store
  get isLoading() {
    return this.$blog.loading(); // или this.$blog.state.getLoading()
  },

  get articles() {
    return this.$blog.articles(); // или this.$blog.state.getArticles()
  },
}));
```

## 🎯 Преимущества новой архитектуры

### **✅ Четкое разделение ответственностей**

- **Store**: Только состояние + computed properties
- **Manager**: Только AJAX + координация + бизнес-логика
- **Components**: Только UI + проксирование к API

### **✅ Устранены прямые зависимости**

- Components не вызывают напрямую методы Store
- Все операции идут через Manager Operations API
- Store не содержит бизнес-логики навигации

### **✅ Централизованные API**

- `stateAPI` - для состояния
- `urlAPI` - для URL операций
- `operationsAPI` - для всех бизнес-операций

### **✅ Легкость тестирования**

- Каждый слой можно тестировать изолированно
- Четкие интерфейсы для моков
- Нет скрытых зависимостей

## 🔄 Обратная совместимость

Для плавной миграции, старые методы помечены как **DEPRECATED** но продолжают работать:

```javascript
// DEPRECATED но работает
this.$blog.goToPage(2);

// NEW - предпочтительный способ
this.$blog.operations().goToPage(2);
```

## 📈 Дальнейшее развитие

1. **Постепенная миграция** всех компонентов на новые API
2. **Удаление DEPRECATED методов** после полной миграции
3. **Добавление TypeScript** для строгой типизации API
4. **Unit тесты** для каждого API слоя
