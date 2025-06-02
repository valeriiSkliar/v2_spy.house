# Архитектура системы асинхронной загрузки контента

## Обзор системы

Система предназначена для асинхронной загрузки и фильтрации контента без перезагрузки страницы. Реализована на основе AJAX запросов с поддержкой фильтрации, сортировки, пагинации и управления состоянием URL браузера.

## Основные компоненты

### 1. Backend компоненты

#### 1.1 Контроллер с AJAX endpoint

**Файл:** `app/Http/Controllers/Frontend/Service/ServicesController.php`

**Ключевые методы:**

- `index()` - основной метод страницы, обрабатывает все параметры
- `ajaxList()` - AJAX endpoint для асинхронной загрузки данных

```php
public function ajaxList(Request $request)
{
    // Переиспользует логику основного метода index()
    $view = $this->index($request);

    if ($request->ajax()) {
        $viewData = $view->getData();
        $services = $viewData['services'];

        // Рендерит только контент списка
        $servicesHtml = view('components.services.index.list.services-list', [
            'services' => $services,
        ])->render();

        // Рендерит пагинацию отдельно
        $paginationHtml = $hasPagination ?
            view('components.pagination', [...])->render() : '';

        return response()->json([
            'html' => $servicesHtml,
            'pagination' => $paginationHtml,
            'hasPagination' => $hasPagination,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'count' => $services->count(),
        ]);
    }

    return $view;
}
```

**Принципы архитектуры Backend:**

1. AJAX endpoint переиспользует логику основной страницы
2. Возвращает JSON с готовой HTML разметкой
3. Разделение контента на основной блок и пагинацию
4. Метаданные для frontend управления состоянием

#### 1.2 Маршрутизация

**Файл:** `routes/services.php`

```php
// Основная страница
Route::get('/', [ServicesController::class, 'index'])->name('services.index');

// AJAX endpoint
Route::get('/services/list', [ServicesController::class, 'ajaxList'])
    ->name('api.services.list');
```

### 2. Frontend компоненты

#### 2.1 Основная структура HTML

**Файл:** `resources/views/pages/services/index.blade.php`

```html
<!-- Контейнер для асинхронного контента -->
<div id="services-container" data-services-ajax-url="{{ route('api.services.list') }}">
  @if ($services->isEmpty())
  <x-services.index.list.empty-services />
  @else
  <x-services.index.list.services-list :services="$services" />
  @endif
</div>

<!-- Контейнер для пагинации -->
<div id="services-pagination-container" data-pagination-container>
  @if ($services->hasPages())
  <x-pagination :currentPage="$currentPage" :totalPages="$totalPages" />
  @endif
</div>
```

**Принципы разметки:**

1. Разделение контента и пагинации в разные контейнеры
2. Хранение AJAX URL в data-атрибуте контейнера
3. Поддержка серверного рендеринга по умолчанию

#### 2.2 JavaScript система управления

**Файл:** `resources/js/pages/services.js`

**Основные функции:**

##### 2.2.1 Определение режима работы

```javascript
const servicesContainer = document.getElementById('services-container');
const ajaxUrl = servicesContainer?.getAttribute('data-services-ajax-url');
const useAjax = !!ajaxUrl;
```

##### 2.2.2 Центральная функция перезагрузки контента

```javascript
function reloadServicesContent(container, url, scrollToTop = true) {
  // Показать лоадер
  const loader = showInElement(container);

  // Построить URL с текущими параметрами
  const requestUrl = new URL(window.location.href);

  // AJAX запрос
  fetch(`${url}?${requestUrl.searchParams.toString()}`, {
    headers: {
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    },
  })
    .then(response => response.json())
    .then(data => {
      // Обновить основной контент
      container.innerHTML = data.html;

      // Обновить пагинацию
      const paginationContainer = document.getElementById('services-pagination-container');
      if (paginationContainer) {
        if (data.hasPagination && data.pagination) {
          paginationContainer.innerHTML = data.pagination;
          paginationContainer.style.display = 'block';
        } else {
          paginationContainer.innerHTML = '';
          paginationContainer.style.display = 'none';
        }
      }
    })
    .finally(() => {
      hideInElement(loader);
    });
}
```

##### 2.2.3 Система фильтрации

```javascript
// Инициализация компонентов фильтрации
initializeSelectComponent('#sort-by', {
  selectors: {
    select: '.base-select__dropdown',
    options: '.base-select__option',
    trigger: '.base-select__trigger',
    valueElement: '[data-value]',
    orderElement: '[data-order]',
  },
  params: {
    valueParam: 'sortBy',
    orderParam: 'sortOrder',
  },
  resetPage: true,
  preventReload: useAjax, // Предотвращает перезагрузку формы при AJAX
});

// Обработчик изменений фильтров
function handleFilterChange(event, data) {
  if (!useAjax) return;

  // Сброс страницы при изменении фильтров
  updateBrowserUrl({ page: null });

  // Перезагрузка контента
  reloadServicesContent(servicesContainer, ajaxUrl, true);
}

// Подключение обработчиков
if (useAjax) {
  filterSelectors.forEach(selector => {
    $(selector).on('change', handleFilterChange);
  });
}
```

##### 2.2.4 Система поиска с debounce

```javascript
function setupSearchForm() {
  const searchInput = document.getElementById('services-search-input');
  const minChars = parseInt(searchInput.getAttribute('data-min-chars') || '3', 10);

  // Debounced поиск (300ms задержка)
  const debouncedSearch = debounce(function (value) {
    if (value.length >= minChars || value.length === 0) {
      performSearch(value);
    }
  }, 300);

  searchInput.addEventListener('input', function () {
    const searchValue = this.value.trim();

    // Визуальная индикация минимального количества символов
    if (searchValue.length > 0 && searchValue.length < minChars) {
      searchInput.classList.add('min-chars-warning');
    } else {
      searchInput.classList.remove('min-chars-warning');
      debouncedSearch(searchValue);
    }
  });
}
```

##### 2.2.5 Управление историей браузера

```javascript
// Обработка кнопок "назад/вперед" браузера
window.addEventListener('popstate', function (event) {
  if (servicesContainer && ajaxUrl) {
    reloadServicesContent(servicesContainer, ajaxUrl);
  }
});
```

### 3. Вспомогательные компоненты

#### 3.1 Управление URL браузера

**Файл:** `resources/js/helpers/update-browser-url.js`

```javascript
function updateBrowserUrl(queryParams) {
  const currentUrl = new URL(window.location.href);
  Object.keys(queryParams).forEach(key => {
    if (queryParams[key] === null || queryParams[key] === undefined || queryParams[key] === '') {
      currentUrl.searchParams.delete(key);
    } else {
      currentUrl.searchParams.set(key, queryParams[key]);
    }
  });

  // Удаление 'page=1' для чистоты URL
  if (currentUrl.searchParams.get('page') === '1') {
    currentUrl.searchParams.delete('page');
  }

  history.pushState(queryParams, '', currentUrl.toString());
}
```

#### 3.2 Система лоадеров

**Файл:** `resources/js/components/loader.js`

```javascript
// Показать лоадер в элементе
export function showInElement(targetElement) {
  const loaderDiv = document.createElement('div');
  loaderDiv.className = 'loader-inline';
  loaderDiv.innerHTML = `
        <div class="loader__logo">
            <div class="loader__animation"></div>
        </div>
    `;
  targetElement.appendChild(loaderDiv);
  loaderDiv.classList.add('active');
  return loaderDiv;
}

// Скрыть лоадер
export function hideInElement(loaderElement) {
  loaderElement.classList.remove('active');
  setTimeout(() => {
    if (loaderElement.parentNode) {
      loaderElement.parentNode.removeChild(loaderElement);
    }
  }, 100);
}
```

#### 3.3 Компонент селектов

**Файл:** `resources/js/helpers/initiolaze-select-component.js`

Универсальный компонент для инициализации кастомных селектов с поддержкой:

- AJAX режима (`preventReload: true`)
- Параметров значения и порядка сортировки
- Обновления URL браузера
- Событийной системы

## Архитектурные принципы

### 1. Прогрессивное улучшение

- Страница работает без JavaScript (серверный рендеринг)
- JavaScript добавляет асинхронность как улучшение

### 2. Модульность

- Каждый компонент (поиск, фильтры, пагинация) работает независимо
- Центральная функция перезагрузки переиспользуется всеми компонентами

### 3. Управление состоянием

- URL браузера как единственный источник истины
- Синхронизация всех параметров через URL
- Поддержка истории браузера

### 4. UX принципы

- Debounce для поиска (300ms)
- Визуальная обратная связь (лоадеры)
- Сброс страницы при изменении фильтров
- Минимальное количество символов для поиска

## Шаблон реализации для новых разделов

### Шаг 1: Backend

1. Создать контроллер с методами `index()` и `ajaxList()`
2. Добавить маршруты для основной страницы и AJAX endpoint
3. В `ajaxList()` переиспользовать логику `index()` и возвращать JSON

### Шаг 2: Frontend разметка

1. Создать контейнер с `data-ajax-url` атрибутом
2. Разделить контент и пагинацию в разные контейнеры
3. Добавить формы фильтрации и поиска

### Шаг 3: JavaScript

1. Определить режим работы (`useAjax`)
2. Инициализировать компоненты фильтрации с `preventReload: useAjax`
3. Создать функцию перезагрузки контента
4. Настроить обработчики событий и управление историей

### Шаг 4: Интеграция

1. Подключить вспомогательные компоненты (лоадеры, URL helpers)
2. Настроить обработку ошибок и состояний загрузки
3. Добавить debounce для поиска при необходимости

## Пример файлов для копирования

**Контроллер:** `app/Http/Controllers/Frontend/Service/ServicesController.php`
**JavaScript:** `resources/js/pages/services.js`  
**Разметка:** `resources/views/pages/services/index.blade.php`
**Маршруты:** `routes/services.php`

## Конфигурационные параметры

### Обязательные:

- `data-services-ajax-url` - URL для AJAX запросов
- Уникальные ID контейнеров для контента и пагинации
- CSRF токен в meta теге

### Опциональные:

- `data-min-chars` для поиска (по умолчанию 3)
- Параметры debounce (по умолчанию 300ms)
- Настройки лоадеров

Эта архитектура обеспечивает:

- ✅ Быструю загрузку контента без перезагрузки страницы
- ✅ Синхронизацию состояния с URL браузера
- ✅ Модульность и переиспользование кода
- ✅ Хороший UX с прогрессивным улучшением
- ✅ Легкую адаптацию для новых разделов
