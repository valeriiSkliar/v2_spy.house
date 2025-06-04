# План реализации Alpine.js SPA для страницы "Креативы"

## Обзор проекта

Преобразование существующей серверной страницы "Креативы" в одностраничное приложение (SPA) на основе Alpine.js с сохранением всей функциональности и улучшением пользовательского опыта.

## Выбранный технический стек

- **Alpine.js** - Основной фреймворк для реактивности
- **Navigo** - Клиентский роутинг и URL управление
- **@alpinejs/store** - Глобальное состояние приложения
- **@alpinejs/intersect** - Ленивая загрузка и бесконечная прокрутка
- **@alpinejs/persist** - Сохранение пользовательских настроек
- **@alpinejs/focus** - Улучшенная навигация клавиатурой
- **lodash.debounce/throttle** - Оптимизация производительности

## Архитектура текущего состояния

### Существующие компоненты:

- **Filter Component**: Фильтрация по различным параметрам
- **Filter Tabs**: Переключение между типами креативов (push, inpage, facebook, tiktok)
- **Creative Items**: Отображение креативов с детальной информацией
- **Pagination**: Навигация по страницам
- **Details Panels**: Подробная информация о креативах

### Текущие ограничения:

- Перезагрузка страницы при каждом действии
- Отсутствие состояния приложения
- Нет синхронизации с URL
- Базовая JavaScript функциональность

---

## ЭТАП 1: Инфраструктура Alpine.js (Приоритет: КРИТИЧЕСКИЙ)

### 1.1 Структура Alpine.js проекта

**Время выполнения: 1 день**

#### Структура файлов:

```
resources/js/creatives/
├── app.js                    # Главный файл Alpine.js приложения
├── store/
│   └── creativesStore.js     # Alpine Store для глобального состояния
├── components/
│   ├── filterComponent.js    # Alpine.data('creativesFilter')
│   ├── tabsComponent.js      # Alpine.data('creativeTabs')
│   ├── creativesListComponent.js # Alpine.data('creativesList')
│   ├── creativeItemComponent.js  # Alpine.data('creativeItem')
│   ├── detailsPanelComponent.js  # Alpine.data('detailsPanel')
│   └── paginationComponent.js    # Alpine.data('pagination')
├── services/
│   ├── apiService.js         # HTTP запросы и кэширование
│   └── routerService.js      # Navigo роутер конфигурация
└── utils/
    └── helpers.js            # Утилиты и вспомогательные функции
```

#### Задачи:

- [ ] Создать базовую структуру файлов
- [ ] Настроить Vite для сборки Alpine.js компонентов
- [ ] Создать Alpine Store для управления состоянием

### 1.2 Alpine Store - глобальное состояние

**Время выполнения: 1 день**

#### Создание `creativesStore.js`:

```javascript
Alpine.store('creatives', {
  // UI состояние
  ui: {
    activeTab: Alpine.$persist('push').as('active-tab'),
    loading: false,
    showMobileFilters: false,
    selectedCreative: null,
    showDetailsPanel: false,
  },

  // Фильтрация
  filters: Alpine.$persist({
    basic: {
      search: '',
      country: null,
      dateRange: { start: null, end: null },
      sort: 'date_desc',
      perPage: 12,
    },
    advanced: {
      networks: [],
      languages: [],
      os: [],
      browsers: [],
      devices: [],
      imageSizes: [],
      adultContent: false,
    },
  }).as('creatives-filters'),

  // Данные
  data: {
    creatives: [],
    totalCount: 0,
    tabCounts: { push: 0, inpage: 0, facebook: 0, tiktok: 0 },
  },

  // Пагинация
  pagination: {
    current: 1,
    total: 0,
    hasNext: false,
    hasPrev: false,
  },

  // Избранное
  favorites: Alpine.$persist([]).as('creatives-favorites'),

  // Методы
  async setActiveTab(tab) {
    /* ... */
  },
  updateFilter(type, key, value) {
    /* ... */
  },
  async loadCreatives() {
    /* ... */
  },
});
```

#### Задачи:

- [ ] Создать глобальный Alpine Store
- [ ] Настроить Alpine Persist для сохранения фильтров
- [ ] Реализовать методы управления состоянием
- [ ] Создать реактивные getters для UI

### 1.3 Navigo роутер

**Время выполнения: 0.5 дня**

#### Создание `routerService.js`:

```javascript
import Navigo from 'navigo';

const router = new Navigo('/creatives', { hash: false });

router
  .on('/', () => {
    Alpine.store('creatives').setActiveTab('push');
  })
  .on('/:type', match => {
    const { type } = match.data;
    const params = new URLSearchParams(match.url.split('?')[1]);

    Alpine.store('creatives').setActiveTab(type);
    Alpine.store('creatives').restoreFiltersFromURL(params);
  })
  .resolve();
```

#### Задачи:

- [ ] Настроить Navigo роутер
- [ ] Создать маршруты для типов креативов
- [ ] Реализовать синхронизацию URL с фильтрами
- [ ] Добавить поддержку кнопок браузера

---

## ЭТАП 2: Система фильтрации Alpine.js (Приоритет: ВЫСОКИЙ)

### 2.1 Компонент базовой фильтрации

**Время выполнения: 1.5 дня**

#### Создание `filterComponent.js`:

```javascript
Alpine.data('creativesFilter', () => ({
  showAdvanced: false,

  get store() {
    return this.$store.creatives;
  },

  get isFiltered() {
    const filters = this.store.filters.basic;
    return filters.search || filters.country || filters.dateRange.start;
  },

  init() {
    // Настройка debounced функций
    this.debouncedSearch = lodash.debounce(value => {
      this.store.updateFilter('basic', 'search', value);
    }, 300);
  },

  updateSearch(event) {
    this.debouncedSearch(event.target.value);
  },

  updateCountry(value) {
    this.store.updateFilter('basic', 'country', value);
  },

  toggleAdvanced() {
    this.showAdvanced = !this.showAdvanced;
  },

  resetFilters() {
    this.store.resetFilters();
  },
}));
```

#### Функционал:

- [ ] Поиск по ключевым словам с debouncing (300ms)
- [ ] Фильтрация по стране
- [ ] Выбор диапазона дат с Flatpickr
- [ ] Сортировка результатов
- [ ] Количество элементов на странице

#### HTML разметка с Alpine директивами:

```html
<div x-data="creativesFilter" class="filters-container">
  <!-- Базовые фильтры -->
  <div class="basic-filters">
    <input
      type="text"
      x-model="store.filters.basic.search"
      @input="updateSearch"
      placeholder="Поиск по ключевым словам..."
      class="form-control"
    />

    <select
      x-model="store.filters.basic.country"
      @change="updateCountry($event.target.value)"
      class="form-select"
    >
      <option value="">Все страны</option>
      <!-- Опции стран -->
    </select>
  </div>

  <!-- Расширенные фильтры -->
  <div x-show="showAdvanced" x-collapse class="advanced-filters">
    <!-- Детальные фильтры -->
  </div>

  <button @click="toggleAdvanced" class="btn btn-link">
    Расширенные фильтры
    <span x-text="showAdvanced ? '▲' : '▼'"></span>
  </button>
</div>
```

### 2.2 Расширенная фильтрация

**Время выполнения: 1.5 дня**

#### Функционал:

- [ ] Multi-select компоненты для сетей, языков, ОС
- [ ] Фильтры устройств и размеров изображений
- [ ] Переключатель взрослого контента
- [ ] Сохранение настроек с Alpine Persist
- [ ] Групповые операции (сброс группы фильтров)

#### Технические особенности:

- Автоматическое сохранение состояния
- Реактивное обновление счетчиков результатов
- Валидация значений на клиенте
- Анимации collapse для групп фильтров

---

## ЭТАП 3: Навигация по вкладкам Alpine.js (Приоритет: ВЫСОКИЙ)

### 3.1 Компонент вкладок

**Время выполнения: 1 день**

#### Создание `tabsComponent.js`:

```javascript
Alpine.data('creativeTabs', () => ({
  get store() {
    return this.$store.creatives;
  },

  get activeTab() {
    return this.store.ui.activeTab;
  },

  get tabCounts() {
    return this.store.data.tabCounts;
  },

  async switchTab(tab) {
    if (tab === this.activeTab || this.store.ui.loading) return;

    await this.store.setActiveTab(tab);

    // Navigo роутинг
    router.navigate(`/${tab}${this.store.buildQueryString()}`);
  },

  getTabClass(tab) {
    return {
      'nav-link': true,
      active: tab === this.activeTab,
      loading: this.store.ui.loading && tab === this.activeTab,
    };
  },
}));
```

#### Функционал:

- [ ] Переключение между типами креативов без перезагрузки
- [ ] Обновление счетчиков в вкладках в реальном времени
- [ ] Сохранение выбранной вкладки в URL и localStorage
- [ ] Плавные переходы между вкладками

#### HTML с Alpine директивами:

```html
<div x-data="creativeTabs" class="nav nav-tabs">
  <template x-for="(count, tab) in tabCounts" :key="tab">
    <button @click="switchTab(tab)" :class="getTabClass(tab)" x-text="`${tab} (${count})`"></button>
  </template>
</div>
```

### 3.2 Сохранение состояния при переключении

**Время выполнения: интегрировано в предыдущий пункт**

#### Автоматические возможности Alpine.js:

- Сохранение фильтров через Alpine Persist
- Сохранение позиции скролла через Alpine Store
- Кэширование данных вкладок в памяти
- Восстановление состояния автоматически

---

## ЭТАП 4: Список креативов с Alpine.js (Приоритет: ВЫСОКИЙ)

### 4.1 Компонент списка креативов

**Время выполнения: 2 дня**

#### Создание `creativesListComponent.js`:

```javascript
Alpine.data('creativesList', () => ({
  get store() {
    return this.$store.creatives;
  },

  get creatives() {
    return this.store.data.creatives;
  },

  get loading() {
    return this.store.ui.loading;
  },

  selectCreative(creative) {
    this.store.ui.selectedCreative = creative;
    this.store.ui.showDetailsPanel = true;
  },

  toggleFavorite(creativeId) {
    const favorites = this.store.favorites;
    const index = favorites.indexOf(creativeId);

    if (index > -1) {
      favorites.splice(index, 1);
    } else {
      favorites.push(creativeId);
    }
  },

  isFavorite(creativeId) {
    return this.store.favorites.includes(creativeId);
  },
}));
```

#### Функционал:

- [ ] Асинхронная загрузка креативов
- [ ] Отображение различных типов креативов (push, inpage, social)
- [ ] Loading скелетоны для карточек
- [ ] Обработка пустых состояний с красивым UI

### 4.2 Ленивая загрузка с Alpine Intersect

**Время выполнения: 1 день**

#### Создание `creativeItemComponent.js`:

```javascript
Alpine.data('creativeItem', creative => ({
  creative: creative,
  imageLoaded: false,

  loadImage() {
    // Ленивая загрузка изображения
    this.imageLoaded = true;
  },

  get isFavorite() {
    return this.$store.creatives.favorites.includes(this.creative.id);
  },

  toggleFavorite() {
    this.$store.creatives.toggleFavorite(this.creative.id);
  },
}));
```

#### Бесконечная прокрутка:

```html
<div x-data="creativesList" class="creatives-grid">
  <template x-for="creative in creatives" :key="creative.id">
    <div x-data="creativeItem(creative)" class="creative-card">
      <!-- Карточка креатива -->
      <img
        x-intersect.once="loadImage"
        :src="imageLoaded ? creative.image : '/placeholder.jpg'"
        class="creative-image"
      />

      <button @click="toggleFavorite" :class="{ 'active': isFavorite }" class="favorite-btn">
        ❤️
      </button>
    </div>
  </template>

  <!-- Триггер для загрузки следующей страницы -->
  <div
    x-intersect="store.loadNextPage"
    x-intersect.threshold.90
    x-show="store.pagination.hasNext"
    class="loading-trigger"
  >
    <div class="loading-skeleton"></div>
  </div>
</div>
```

#### Функционал:

- [ ] Ленивая загрузка изображений с Alpine Intersect
- [ ] Бесконечная прокрутка (опционально)
- [ ] Добавление/удаление из избранного с анимациями
- [ ] Копирование данных в буфер обмена
- [ ] Скачивание изображений/иконок

---

## ЭТАП 5: Детальная панель Alpine.js (Приоритет: СРЕДНИЙ)

### 5.1 Компонент детальной панели

**Время выполнения: 1.5 дня**

#### Создание `detailsPanelComponent.js`:

```javascript
Alpine.data('detailsPanel', () => ({
  get store() {
    return this.$store.creatives;
  },

  get isOpen() {
    return this.store.ui.showDetailsPanel;
  },

  get selectedCreative() {
    return this.store.ui.selectedCreative;
  },

  close() {
    this.store.ui.showDetailsPanel = false;
    this.store.ui.selectedCreative = null;
  },

  // Alpine Focus для навигации клавиатурой
  init() {
    this.$watch('isOpen', value => {
      if (value) {
        this.$nextTick(() => {
          this.$focus.first();
        });
      }
    });
  },
}));
```

#### HTML с анимациями:

```html
<div
  x-data="detailsPanel"
  x-show="isOpen"
  x-transition.opacity
  @keydown.escape="close"
  class="details-panel-overlay"
>
  <div
    x-show="isOpen"
    x-transition:enter="slide-in-right"
    x-transition:leave="slide-out-right"
    @click.outside="close"
    class="details-panel"
  >
    <!-- Содержимое панели -->
    <div class="panel-header">
      <h3 x-text="selectedCreative?.title"></h3>
      <button @click="close" class="close-btn">✕</button>
    </div>

    <div class="panel-content">
      <!-- Детальная информация -->
    </div>
  </div>
</div>
```

#### Функционал:

- [ ] Слайдовая панель с деталями справа
- [ ] Отображение полной информации о креативе
- [ ] Таблица с метриками
- [ ] Блок похожих креативов
- [ ] История изменений креатива
- [ ] Навигация клавиатурой с Alpine Focus

---

## ЭТАП 6: Пагинация Alpine.js (Приоритет: СРЕДНИЙ)

### 6.1 Компонент пагинации

**Время выполнения: 1 день**

#### Создание `paginationComponent.js`:

```javascript
Alpine.data('pagination', () => ({
  get store() {
    return this.$store.creatives;
  },

  get pagination() {
    return this.store.pagination;
  },

  async goToPage(page) {
    if (page === this.pagination.current || this.store.ui.loading) return;

    await this.store.loadPage(page);

    // Обновляем URL
    router.navigate(`/${this.store.ui.activeTab}${this.store.buildQueryString()}`);
  },

  get visiblePages() {
    const current = this.pagination.current;
    const total = this.pagination.total;
    const maxVisible = 7;

    if (total <= maxVisible) {
      return Array.from({ length: total }, (_, i) => i + 1);
    }

    // Логика для умной пагинации
    const start = Math.max(1, current - 3);
    const end = Math.min(total, start + maxVisible - 1);

    return Array.from({ length: end - start + 1 }, (_, i) => start + i);
  },
}));
```

#### Функционал:

- [ ] Пагинация без перезагрузки страницы
- [ ] Поддержка кнопок браузера (назад/вперед)
- [ ] Умная генерация номеров страниц
- [ ] Сохранение позиции при возврате
- [ ] URL синхронизация с Navigo

---

## ЭТАП 7: Избранное с Alpine Persist (Приоритет: СРЕДНИЙ)

### 7.1 Управление избранным

**Время выполнения: 1 день**

#### Интеграция в Alpine Store:

```javascript
// В creativesStore.js
favorites: Alpine.$persist([]).as('creatives-favorites'),
showOnlyFavorites: false,

get filteredCreatives() {
  if (this.showOnlyFavorites) {
    return this.data.creatives.filter(c => this.favorites.includes(c.id));
  }
  return this.data.creatives;
},

toggleFavorite(creativeId) {
  const index = this.favorites.indexOf(creativeId);
  if (index > -1) {
    this.favorites.splice(index, 1);
  } else {
    this.favorites.push(creativeId);
  }
},

get favoritesCount() {
  return this.favorites.length;
}
```

#### Функционал:

- [ ] Добавление креативов в избранное с анимацией
- [ ] Удаление из избранного
- [ ] Просмотр только избранных
- [ ] Счетчик избранного в UI
- [ ] Автоматическое сохранение в localStorage

---

## ЭТАП 8: Производительность и UX (Приоритет: НИЗКИЙ)

### 8.1 Оптимизация с Lodash

**Время выполнения: 1 день**

#### Throttling для скролла:

```javascript
// В app.js
const throttledScroll = lodash.throttle(() => {
  Alpine.store('creatives').handleScroll();
}, 16); // 60fps

window.addEventListener('scroll', throttledScroll);

// Debounced resize
const debouncedResize = lodash.debounce(() => {
  Alpine.store('creatives').updateViewport();
}, 250);

window.addEventListener('resize', debouncedResize);
```

#### Задачи:

- [ ] Оптимизация поиска с debouncing (300ms)
- [ ] Throttling скролла для плавности (16ms)
- [ ] Debouncing resize событий (250ms)
- [ ] Ленивая загрузка изображений с Intersect
- [ ] Кэширование API ответов

### 8.2 Loading состояния и анимации

**Время выполнения: 0.5 дня**

#### Skeleton компоненты:

```html
<template x-if="store.ui.loading">
  <div class="skeleton-grid">
    <div x-data="{ count: 12 }">
      <template x-for="i in count" :key="i">
        <div class="skeleton-card">
          <div class="skeleton-image"></div>
          <div class="skeleton-text"></div>
          <div class="skeleton-text short"></div>
        </div>
      </template>
    </div>
  </div>
</template>
```

#### Задачи:

- [ ] Skeleton loading экраны
- [ ] Плавные переходы с Alpine transitions
- [ ] Уведомления об ошибках
- [ ] Анимации добавления/удаления элементов

---

## Технические требования Alpine.js

### Главный файл приложения (`app.js`):

```javascript
import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus';
import intersect from '@alpinejs/intersect';
import collapse from '@alpinejs/collapse';

// Регистрируем плагины
Alpine.plugin(persist);
Alpine.plugin(focus);
Alpine.plugin(intersect);
Alpine.plugin(collapse);

// Импортируем компоненты
import './store/creativesStore.js';
import './components/filterComponent.js';
import './components/tabsComponent.js';
import './components/creativesListComponent.js';
import './components/creativeItemComponent.js';
import './components/detailsPanelComponent.js';
import './components/paginationComponent.js';

// Инициализируем Alpine
Alpine.start();
```

### Структура API ответа (остается как есть):

```javascript
// GET /api/creatives
{
  "data": [...], // массив креативов
  "meta": {
    "current_page": 1,
    "total": 34567,
    "per_page": 12,
    "total_pages": 2881
  },
  "counts": {
    "push": 15234,
    "inpage": 8901,
    "facebook": 7432,
    "tiktok": 3000
  }
}
```

## Обновленные временные рамки

| Этап                             | Время   | Приоритет   | Alpine.js особенности    |
| -------------------------------- | ------- | ----------- | ------------------------ |
| Этап 1: Alpine.js инфраструктура | 2 дня   | Критический | Store + Router setup     |
| Этап 2: Фильтрация               | 3 дня   | Высокий     | Alpine.data + persist    |
| Этап 3: Вкладки                  | 1 день  | Высокий     | Простая реактивность     |
| Этап 4: Список креативов         | 3 дня   | Высокий     | Intersect + data binding |
| Этап 5: Детальная панель         | 1.5 дня | Средний     | Focus + transitions      |
| Этап 6: Пагинация                | 1 день  | Средний     | URL синхронизация        |
| Этап 7: Избранное                | 1 день  | Средний     | Persist автоматически    |
| Этап 8: Производительность       | 1.5 дня | Низкий      | Lodash utilities         |

**Общее время: 13-14 рабочих дней** (значительно сокращено благодаря простоте Alpine.js)

## Ключевые преимущества Alpine.js подхода

### 1. **Минимальная сложность**

- Нет сложной архитектуры компонентов
- Декларативные директивы прямо в HTML
- Простая отладка в браузере

### 2. **Автоматическая реактивность**

- Alpine Store обновляет UI автоматически
- Нет необходимости в ручном управлении DOM
- Встроенная система watchers

### 3. **Встроенные возможности**

- **Persist**: автоматическое сохранение в localStorage
- **Intersect**: ленивая загрузка из коробки
- **Focus**: управление фокусом для a11y
- **Transitions**: плавные анимации

### 4. **Быстрая разработка**

- Быстрое прототипирование
- Легкая интеграция с существующими Blade шаблонами
- Минимальное время настройки

### 5. **Производительность**

- Малый размер библиотеки (~10kb gzipped)
- Нет виртуального DOM
- Прямые обновления реального DOM

## Критические точки для Alpine.js

1. **Alpine Store** - основа всего приложения, должен быть хорошо структурирован
2. **Navigo интеграция** - критично для SEO и пользовательского опыта
3. **Persist настройки** - определить что сохранять, а что нет
4. **Intersect производительность** - правильно настроить threshold-ы
5. **Компонентная архитектура** - четкое разделение ответственности

## Готовность к реализации

План полностью адаптирован под Alpine.js стек и готов к реализации. Архитектура обеспечивает:

- ⚡ **Быструю разработку** - простота Alpine.js
- 🔧 **Легкую поддержку** - декларативный код
- 📈 **Масштабируемость** - модульная структура
- 🎯 **Четкое разграничение** - компонентный подход
- 🚀 **Отличную производительность** - встроенные оптимизации

**Готов приступать к реализации Этапа 1!**
