# Архитектура AJAX системы загрузки контента

Этот документ описывает техническую архитектуру асинхронной системы загрузки контента на примере раздела сервисов. Система разработана для переиспользования в других разделах сайта.

## Обзор системы

Система состоит из трех основных компонентов:
- **Backend Controller** - обработчик запросов
- **Frontend JavaScript** - управление интерфейсом
- **View Template** - структура HTML

## 1. Backend Architecture

### Controller Structure

```php
class ServicesController extends FrontendController
{
    // Основной метод для отображения страницы
    public function index(Request $request) {
        // Обработка параметров фильтрации и сортировки
        // Формирование запросов к базе данных
        // Возврат view с данными
    }

    // AJAX endpoint для асинхронной загрузки
    public function ajaxList(Request $request) {
        // Переиспользование логики index()
        $view = $this->index($request);
        
        if ($request->ajax()) {
            // Возврат JSON с HTML-фрагментами
            return response()->json([
                'html' => $servicesHtml,
                'pagination' => $paginationHtml,
                'hasPagination' => $hasPagination,
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'count' => $services->count(),
            ]);
        }
    }
}
```

### Ключевые принципы Backend:

1. **Переиспользование логики** - AJAX endpoint вызывает основной метод index()
2. **Условное рендеринг** - проверка `$request->ajax()` для выбора формата ответа
3. **Компонентный подход** - отдельные Blade компоненты для списка и пагинации
4. **Структурированный JSON** - стандартизированный формат ответа

### Обязательные поля JSON ответа:

- `html` - HTML контент списка элементов
- `pagination` - HTML пагинации (если есть)  
- `hasPagination` - флаг наличия пагинации
- `currentPage` - текущая страница
- `totalPages` - общее количество страниц
- `count` - количество элементов на странице

## 2. Frontend Architecture

### Core JavaScript Structure

```javascript
document.addEventListener('DOMContentLoaded', function () {
    // 1. Инициализация переменных
    const servicesContainer = document.getElementById('services-container');
    const ajaxUrl = servicesContainer?.getAttribute('data-services-ajax-url');
    const useAjax = !!ajaxUrl;

    // 2. Обработчики событий браузера
    window.addEventListener('popstate', function (event) {
        if (servicesContainer && ajaxUrl) {
            reloadServicesContent(servicesContainer, ajaxUrl);
        }
    });

    // 3. Инициализация компонентов фильтрации
    initializeFilterComponents();

    // 4. Центральная функция перезагрузки контента
    function reloadServicesContent(container, url, scrollToTop = true) {
        // Показать лоадер
        const loader = showInElement(container);
        
        // Построить URL с параметрами
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
            // Обновить контент
            container.innerHTML = data.html;
            
            // Обновить пагинацию
            updatePagination(data);
        })
        .finally(() => {
            hideInElement(loader);
        });
    }
});
```

### Основные компоненты Frontend:

#### 1. Система фильтрации
- Инициализация select компонентов
- Обработка изменений фильтров
- Автоматическое обновление URL

#### 2. Поисковая система
- Debounced поиск (300ms задержка)
- Валидация минимальной длины запроса
- Визуальная индикация состояния

#### 3. Управление URL
- Синхронизация параметров с URL
- Поддержка браузерной навигации (popstate)
- Автоматическая очистка ненужных параметров

#### 4. Система лоадеров
- Inline лоадеры для контейнеров
- Визуальная обратная связь пользователю

## 3. View Template Architecture

### HTML Structure

```blade
{{-- Основная страница --}}
@extends('layouts.main')

@section('page-content')
<div class="row align-items-center">
    <x-services.index.header.page-h1 title="{{ __('services.title') }}" />
    <x-services.index.header.sort-selects />
</div>

<x-services.index.filters.filter-section />

{{-- Контейнер для AJAX контента --}}
<div id="services-container" data-services-ajax-url="{{ route('api.services.list') }}">
    @if ($services->isEmpty())
        <x-services.index.list.empty-services />
    @else
        <x-services.index.list.services-list :services="$services" />
    @endif
</div>

{{-- Контейнер для пагинации --}}
<div id="services-pagination-container" data-pagination-container>
    @if ($services->hasPages())
        <x-pagination :currentPage="$currentPage" :totalPages="$totalPages" />
    @endif
</div>
@endsection

@push('scripts')
@vite(['resources/js/services.js'])
@endpush
```

### Ключевые элементы View:

1. **Data attributes** - передача AJAX URL в JavaScript
2. **Контейнеры** - четкое разделение динамического контента
3. **Компонентный подход** - переиспользуемые Blade компоненты
4. **Условное отображение** - обработка пустых состояний

## 4. Routing Configuration

### Web Routes
```php
Route::middleware('web')
    ->prefix('services')
    ->group(function () {
        Route::get('/', [ServicesController::class, 'index'])->name('services.index');
        Route::get('/{id}', [ServicesController::class, 'show'])->name('services.show');
    });
```

### API Routes
```php
Route::prefix('api')
    ->group(function () {
        Route::get('/services/list', [ServicesController::class, 'ajaxList'])->name('api.services.list');
    });
```

## 5. Checklist для реализации в новом разделе

### Backend Tasks:
- [ ] Создать основной Controller с методом `index()`
- [ ] Добавить метод `ajaxList()` с переиспользованием логики
- [ ] Настроить фильтрацию и сортировку
- [ ] Создать Blade компоненты для списка и пагинации
- [ ] Настроить routes (web + api)

### Frontend Tasks:
- [ ] Создать JavaScript файл для страницы
- [ ] Инициализировать систему фильтрации
- [ ] Реализовать функцию `reloadContent()`
- [ ] Настроить обработку поиска (если нужно)
- [ ] Добавить обработчик `popstate`
- [ ] Интегрировать систему лоадеров

### View Tasks:
- [ ] Создать основной template с data attributes
- [ ] Добавить контейнеры для динамического контента
- [ ] Создать компоненты списка и пагинации
- [ ] Подключить JavaScript через `@vite`

## 6. Стандартные параметры системы

### JavaScript Configuration:
```javascript
const config = {
    containers: {
        main: '#content-container',           // Основной контент
        pagination: '#pagination-container'   // Пагинация
    },
    attributes: {
        ajaxUrl: 'data-ajax-url'             // AJAX endpoint
    },
    debounce: {
        search: 300                          // Задержка поиска в ms
    }
};
```

### Filter Components:
- Каждый фильтр инициализируется через `initializeSelectComponent()`
- Параметры: `valueParam`, `orderParam`, `resetPage`, `preventReload`
- События: автоматическая привязка к `change` событию

### URL Management:
- Использование `updateBrowserUrl()` helper функции
- Автоматическая очистка пустых параметров
- Удаление `page=1` для чистоты URL

## 7. Возможные расширения

1. **Infinite Scroll** - замена пагинации на подгрузку
2. **Real-time updates** - WebSocket интеграция
3. **Caching** - клиентское кэширование результатов
4. **Advanced filters** - многоуровневые фильтры
5. **Export functionality** - экспорт результатов

Эта система обеспечивает единообразный подход к асинхронной загрузке контента во всех разделах сайта и может быть легко адаптирована под различные типы данных.