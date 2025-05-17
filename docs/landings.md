# Landings System Documentation

## Overview
The landings system is designed to manage and monitor website downloads, providing a user interface for tracking download statuses and managing downloaded content.

## Core Components

### Controllers
- `BaseLandingsPageController`: Base controller providing common functionality
  - Defines view templates and status configurations
  - Manages sort and pagination options
  - Handles download and anti-flood services

- `LandingsPageController`: Main controller implementing landing page functionality
  - Handles index page rendering with sorting and filtering
  - Manages landing deletion
  - Controls download operations
  - Implements pagination logic

### Frontend Components
- `landings.js`: Main entry point for frontend functionality
  - Initializes select components for sorting and pagination
  - Sets up landing status polling
  - Manages dynamic status updates

### Status Management
- Supports multiple statuses: pending, completed, failed
- Implements real-time status polling
- Provides visual status indicators

## Features

### Sorting and Filtering
- Sort options:
  - Date (Newest/Oldest)
  - Status (Asc/Desc)
  - URL (Asc/Desc)
- Pagination options: 12, 24, 48, 96 items per page

### Download Management
- Secure file download handling
- Error management and status updates
- Anti-flood protection
- File cleanup on deletion

### User Interface
- Dynamic status updates
- Pagination with smart page management
- Sort and filter controls
- Status indicators and labels

## Security Features
- User authorization for downloads and deletions
- Anti-flood protection
- Secure file handling
- Ownership verification

## Data Flow
1. User requests landing page
2. System checks anti-flood protection
3. Data is fetched with current filters
4. Frontend initializes with current state
5. Real-time status updates via polling
6. User actions trigger appropriate controller methods 

## Async Optimization Suggestions

### Current Synchronous Operations to Convert
1. **Sorting and Filtering**
   - Convert to AJAX requests
   - Update only table content without page reload
   - Maintain URL parameters for bookmarking

2. **Pagination**
   - Implement infinite scroll or AJAX pagination
   - Load next page content dynamically
   - Cache previous pages for quick navigation

3. **Delete Operation**
   - Convert to AJAX request
   - Show confirmation modal
   - Update table without refresh
   - Handle errors without page reload

4. **Download Status Updates**
   - Replace polling with WebSocket
   - Real-time status updates

### Implementation Benefits
- Reduced server load
- Better user experience
- Faster page interactions
- Lower bandwidth usage
- Smoother transitions

### Technical Requirements
- API endpoints for each operation
- Frontend state management
- Error handling for async operations
- Loading states and indicators
- Optimistic UI updates 

## Conceptual Implementation Approaches

### 1. State Management with Redux + WebSocket
- **Architecture**
  - Redux для централизованного управления состоянием
  - WebSocket для real-time обновлений
  - Middleware для обработки асинхронных операций
  - Селекторы для оптимизации ререндеров

- **Security**
  - JWT токены для аутентификации WebSocket
  - Rate limiting на уровне middleware
  - Валидация данных на клиенте и сервере
  - Защита от CSRF атак

- **Memory Management**
  - Автоматическая очистка неиспользуемых данных
  - Ограничение размера хранилища
  - Пагинация данных в store
  - Garbage collection для WebSocket соединений

### 2. Event-Driven Architecture with Event Bus
- **Core Components**
  - Центральный Event Bus
  - Система подписок на события
  - Очередь событий с приоритетами
  - Механизм отмены операций

- **State Handling**
  - Immutable state updates
  - Оптимистичные обновления UI
  - Кэширование результатов запросов
  - Автоматическая синхронизация состояния

- **Resource Management**
  - Автоматическое переподключение при разрывах
  - Ограничение количества одновременных запросов
  - Очистка неиспользуемых подписок
  - Мониторинг использования памяти

### 3. Microservices Communication Pattern
- **Service Structure**
  - Отдельный сервис для WebSocket
  - API Gateway для маршрутизации
  - Сервис кэширования
  - Сервис аутентификации

- **Communication Flow**
  - Message Queue для асинхронной коммуникации
  - Circuit Breaker для обработки ошибок
  - Retry механизм для failed запросов
  - Bulk операции для оптимизации

- **Performance Optimization**
  - Кэширование на уровне сервисов
  - Балансировка нагрузки
  - Компрессия данных
  - Оптимизация payload

### Common Best Practices
- Использование TypeScript для типизации
- Unit и интеграционные тесты
- Мониторинг производительности
- Логирование критических операций
- Документация API и событий
- Версионирование API
- Graceful degradation
- Progressive enhancement 

Отличный выбор! "Идея 3 (AJAX + SSE)" действительно является прагматичным подходом для инкрементального улучшения существующей системы на Laravel Blade и JavaScript, минимизируя первоначальные трудозатраты и позволяя постепенно достичь желаемого пользовательского опыта без полной перезагрузки страницы.

Вот подробный план перехода на эту реализацию:

План перехода на AJAX + SSE для страницы лендингов
Цель: Обеспечить работу страницы лендингов без перезагрузки для основных операций (сортировка, фильтрация, пагинация, добавление URL, удаление, обновление статусов) с использованием AJAX-запросов и Server-Sent Events (SSE) для обновлений статусов в реальном времени.

Используемые технологии и подходы:

Бэкенд: Laravel (контроллеры, маршруты, Eloquent, события, очереди).
Фронтенд: JavaScript (модернизация существующего resources/js/pages/landings.js, возможно, с использованием Workspace API или обертки типа Axios, если еще не используется), Blade-шаблоны.
Обмен данными: AJAX-запросы (возвращающие HTML-фрагменты для списков/таблиц и JSON для простых ответов), Server-Sent Events для статусов.
Безопасность: CSRF-токены, аутентификация Laravel, политики авторизации.
UX: Индикаторы загрузки, обратная связь об ошибках, обновление URL через History API.
Фаза 0: Подготовка и Анализ (Срок: ~2-3 дня)
Анализ текущей реализации:
Контроллеры: Изучить app/Http/Controllers/Frontend/Landing/LandingsPageController.php и BaseLandingsPageController.php для понимания текущей логики обработки запросов, фильтрации, сортировки, пагинации.
JavaScript: Детально проанализировать resources/js/pages/landings.js. Определить, как он сейчас взаимодействует с DOM, какие библиотеки используются (например, jQuery, Select2).
Blade-шаблоны: Изучить resources/views/pages/landings/index.blade.php и связанные компоненты (например, components/landings/table.blade.php, components/landings/sort-selects.blade.php, components/pagination.blade.php). Определить основные блоки DOM, которые будут обновляться асинхронно (контейнер таблицы, пагинатор).
Модели и Джобы: Понять взаимодействие WebsiteDownloadMonitor и DownloadWebsiteJob для отслеживания изменений статусов.
Определение API и структур данных:
Для каждой асинхронной операции (список, сортировка, фильтрация, пагинация, добавление, удаление) определить URL API, HTTP-метод, ожидаемые параметры запроса и формат ответа (HTML-фрагмент или JSON).
Для SSE: определить формат событий и данных, передаваемых клиенту.
Настройка окружения и инструментов:
Убедиться, что среда разработки поддерживает отладку AJAX и SSE.
Настроить инструменты для логирования и мониторинга (например, Laravel Telescope).
Планирование рефакторинга landings.js:
Разбить текущий JS-код на модули или функции для каждой операции.
Предусмотреть функции для отправки AJAX-запросов, обновления DOM, управления URL, отображения индикаторов и ошибок.
Создание отдельной ветки в Git для всех изменений.
Фаза 1: Реализация API-эндпоинтов для AJAX-операций (Бэкенд) (Срок: ~5-7 дней)
Маршруты:
В routes/landings.php (или routes/api.php, если предпочтительнее) определить новые GET и POST/DELETE маршруты для асинхронных операций. Например:
GET /landings/ajax/list (для получения списка с фильтрами/сортировкой/пагинацией)
POST /landings/ajax/store (для добавления нового URL)
DELETE /landings/ajax/{landing} (для удаления)
Применить middleware: auth, verified, CSRF (для POST/DELETE), throttle (для защиты от флуда).
Контроллер LandingsPageController (или новый API-контроллер):
Метод для списка (ajaxList):
Принимает параметры запроса (сортировка, фильтры, страница, количество элементов).
Использует существующую логику из BaseLandingsPageController для выборки данных.
Решение: Возвращать отрендеренный Blade partial (например, _landings_table_content.blade.php, включающий строки таблицы и пагинацию) для простоты интеграции. Это минимизирует изменения на фронтенде на начальном этапе.
Обеспечить корректную работу пагинатора Laravel с AJAX.
Метод для добавления (ajaxStore):
Принимает URL из запроса.
Использует LandingDownloadService и AntiFloodService.
Возвращает JSON-ответ (успех/ошибка, возможно, данные нового лендинга или сообщение).
Метод для удаления (ajaxDestroy):
Принимает ID лендинга.
Проверяет авторизацию (WebsiteDownloadMonitorPolicy).
Удаляет запись и связанные файлы.
Возвращает JSON-ответ (успех/ошибка).
Blade Partials:
Создать или выделить части шаблона, которые будут обновляться:
resources/views/pages/landings/_table_content.blade.php (содержит <table> и пагинацию).
resources/views/pages/landings/_row.blade.php (если нужно обновлять отдельные строки).
Тестирование API: Использовать Postman или встроенные тесты Laravel (HTTP-тесты) для проверки корректности работы эндпоинтов.
Фаза 2: Рефакторинг фронтенда для AJAX - Сортировка и Фильтрация (Срок: ~4-6 дней)
Модернизация resources/js/pages/landings.js:
Функция WorkspaceLandings(params):
Принимает объект с параметрами (сортировка, фильтры, страница).
Формирует URL для GET /landings/ajax/list.
Отправляет AJAX-запрос (например, Workspace API с передачей CSRF-токена в заголовках, если он глобально настроен, или добавлять его вручную).
Показывает индикатор загрузки.
В then(): заменяет содержимое контейнера таблицы (#landings-table-container) полученным HTML. Переинициализирует обработчики событий на новых элементах (например, для кнопок удаления, если они часть этого HTML).
В catch(): обрабатывает ошибки, показывает сообщение пользователю.
Скрывает индикатор загрузки.
Обновление URL: После успешной загрузки данных обновить URL в браузере с помощью history.pushState() для отражения текущих параметров сортировки и фильтрации.
Обработчики событий:
Для селектов сортировки (#sort-by, #sort-direction, #items-per-page): при изменении (change) собирать текущие параметры, вызывать WorkspaceLandings(params).
(Если есть другие фильтры, например, текстовый поиск по URL, обработать их аналогично).
Интеграция в Blade:
В resources/views/pages/landings/index.blade.php обернуть таблицу и пагинацию в контейнер, например, <div id="landings-table-container">.
Убедиться, что селекты сортировки и пагинации имеют уникальные ID для легкого доступа из JS.
Начальная загрузка: При первой загрузке страницы либо рендерить начальные данные сервером, либо сразу вызывать WorkspaceLandings() с параметрами по умолчанию (или из URL).
Фаза 3: Рефакторинг фронтенда для AJAX - Пагинация (Срок: ~2-3 дня)
Модернизация resources/js/pages/landings.js:
Обработчики событий для ссылок пагинации:
Поскольку пагинация будет загружаться с HTML-фрагментом, использовать делегирование событий на контейнере #landings-table-container.
При клике на ссылку пагинации (.pagination a):
Отменить стандартное действие (event.preventDefault()).
Извлечь URL или номер страницы из атрибута ссылки.
Собрать текущие параметры сортировки/фильтрации.
Вызвать WorkspaceLandings(params) с новым номером страницы.
Бэкенд: Убедиться, что API-эндпоинт /landings/ajax/list корректно обрабатывает параметр page и возвращает HTML с обновленными ссылками пагинации.
Фаза 4: Рефакторинг фронтенда для AJAX - Добавление URL на загрузку (Срок: ~2-3 дня)
Модернизация resources/js/pages/landings.js:
Обработчик отправки формы (#add-landing-form):
Отменить стандартную отправку (event.preventDefault()).
Собрать данные формы (URL для загрузки).
Отправить AJAX POST запрос на /landings/ajax/store с данными и CSRF-токеном.
Показать индикатор загрузки на кнопке отправки или рядом с формой.
В then():
Обработать JSON-ответ. При успехе: очистить поле ввода, показать сообщение об успехе (например, toast-уведомление), вызвать WorkspaceLandings() для обновления списка (или, если API возвращает HTML новой строки, добавить ее в таблицу напрямую для оптимизации).
При ошибке (валидация, anti-flood): показать сообщения об ошибках.
Скрыть индикатор загрузки.
Бэкенд: LandingsPageController::ajaxStore должен возвращать JSON с четкими кодами успеха/ошибки и сообщениями.
Blade: Форма добавления (components/landings/form.blade.php) должна иметь ID для легкого доступа.
Фаза 5: Рефакторинг фронтенда для AJAX - Удаление лендинга (Срок: ~2-3 дня)
Модернизация resources/js/pages/landings.js:
Обработчики событий для кнопок удаления:
Использовать делегирование событий на #landings-table-container для кнопок удаления (.delete-landing-button).
При клике:
Отменить стандартное действие.
Получить ID лендинга (например, из data-id атрибута кнопки).
Показать модальное окно подтверждения (использовать существующее modals/delete-confirmation.blade.php, но управлять его показом/скрытием через JS и обрабатывать подтверждение).
При подтверждении: отправить AJAX DELETE запрос на /landings/ajax/{landing_id} с CSRF-токеном.
Показать индикатор загрузки.
В then():
При успехе: удалить строку из таблицы DOM (оптимистичное обновление или дождаться ответа сервера), показать сообщение об успехе. Вызвать WorkspaceLandings() для синхронизации пагинации, если удаление влияет на общее количество.
При ошибке: показать сообщение.
Скрыть индикатор загрузки.
Бэкенд: LandingsPageController::ajaxDestroy должен возвращать JSON.
Blade: Кнопки удаления должны содержать data-id или аналогичный атрибут с ID лендинга.
Фаза 6: Реализация Server-Sent Events (SSE) для обновления статусов (Срок: ~4-6 дней)
Бэкенд:
Маршрут для SSE:
В routes/web.php (или api.php, но SSE обычно сессионные) определить GET маршрут, например, /landings/status-stream. Защитить его middleware auth и verified.
Контроллер для SSE (например, LandingStatusController@stream):
Установить заголовки для SSE (Content-Type: text/event-stream, Cache-Control: no-cache, Connection: keep-alive).
Использовать Symfony\Component\HttpFoundation\StreamedResponse.
В цикле (с проверкой connection_aborted()) периодически проверять наличие новых событий (или использовать более продвинутый механизм через Redis Pub/Sub или Laravel Echo Server без самого Echo на клиенте, НУЖНА ТОЛЬКО ОДНОСТОРОННЯЯ СВЯЗЬ SSE). Простой вариант: Laravel Events.
События Laravel:
Создать событие, например, LandingStatusUpdatedEvent(WebsiteDownloadMonitor $landing).
В DownloadWebsiteJob после обновления статуса WebsiteDownloadMonitor и сохранения, диспатчить это событие: LandingStatusUpdatedEvent::dispatch($landing).
Listener для LandingStatusUpdatedEvent: Этот Listener будет отвечать за отправку данных через активные SSE-соединения. Это сложная часть, так как HTTP не сохраняет состояние. Проще всего, если SSE-контроллер сам слушает широковещательные события Laravel (например, через Broadcast::event()) и отправляет данные, если клиент подключен к этому потоку.
Альтернатива (проще для начала, но менее эффективно): В SSE-контроллере можно периодически (раз в несколько секунд) запрашивать недавно обновленные статусы для текущего пользователя и отправлять их. Но это ближе к polling через SSE.
Рекомендуемый подход для SSE с Laravel Events: Использовать широковещание (broadcasting). Laravel может вещать события, а SSE-контроллер может быть клиентом этих событий на сервере, транслируя их клиентам. Однако, если уже используется WebsiteDownloadStatus Notification, можно подумать о его адаптации.
Простой старт для SSE: Отправлять событие SSE с ID лендинга и новым статусом.
PHP

// В SSE контроллере (упрощенно)
echo "event: landing_status_update\n";
echo "data: " . json_encode(['id' => $landing->id, 'status' => $landing->status->value, 'status_label' => $landing->status->getLabel(), 'status_icon' => view('components.landings.status-icon', ['status' => $landing->status])->render()]) . "\n\n";
ob_flush();
flush();
Фронтенд (resources/js/pages/landings.js):
Инициализация SSE-клиента:
JavaScript

const eventSource = new EventSource('/landings/status-stream');

eventSource.addEventListener('landing_status_update', function(event) {
    const data = JSON.parse(event.data);
    // Найти строку таблицы по data.id (например, tr[data-landing-id="..."])
    // Обновить ячейку статуса (иконку и текст)
    const row = document.querySelector(`tr[data-landing-id="${data.id}"]`);
    if (row) {
        const statusCell = row.querySelector('.status-cell-class'); // Заменить на реальный класс/селектор
        if (statusCell) {
            statusCell.innerHTML = data.status_icon + ' ' + data.status_label;
            // Можно добавить анимацию или подсветку для измененной строки
        }
    }
});

eventSource.onerror = function(err) {
    console.error("EventSource failed:", err);
    // Можно добавить логику переподключения или уведомления пользователя
    eventSource.close();
};
Убедиться, что строки таблицы имеют data-landing-id или аналогичный атрибут.
Убедиться, что ячейка статуса имеет класс для легкого поиска.
Фаза 7: Улучшения UX и Оптимизация (Параллельно и после основных фаз)
Индикаторы загрузки:
Обеспечить понятные индикаторы для всех AJAX-операций (например, спиннер на кнопке, затемнение таблицы). Использовать components/common/fullscreen-loader.blade.php или аналогичный.
Обработка ошибок:
Показывать пользователю адекватные сообщения об ошибках AJAX-запросов и SSE-соединения. Использовать components/toast-notifications.blade.php для неблокирующих уведомлений.
Оптимистичные обновления:
Для операций удаления: удалять строку из DOM сразу, до подтверждения от сервера, с возможностью отката.
Кеширование:
Рассмотреть возможность кеширования API-ответов на стороне сервера (Laravel Cache) для часто запрашиваемых данных.
Минимизация и сборка JS/CSS:
Убедиться, что Vite (vite.config.js) корректно настроен для сборки и минимизации ассетов.
Пользовательские настройки:
Сохранять выбранное количество элементов на странице в localStorage и использовать при последующих загрузках.
Фаза 8: Тестирование и Развертывание (После каждой значимой фазы и в конце)
Модульное тестирование (Backend): Написать тесты для новых API-эндпоинтов и SSE-логики.
Функциональное тестирование (Frontend):
Проверить все асинхронные операции в различных браузерах.
Протестировать обновление статусов через SSE.
Проверить корректность обновления URL и работу кнопок "назад/вперед" браузера.
Протестировать обработку ошибок и работу индикаторов.
Пользовательское приемочное тестирование (UAT).
Развертывание:
Сначала на staging-окружение.
Затем на production. Мониторить логи и производительность после развертывания.
Важные замечания:

CSRF-защита: Все AJAX-запросы типа POST, PUT, DELETE должны включать CSRF-токен. Laravel обычно обрабатывает это автоматически для форм, но для Workspace или Axios может потребоваться явная передача токена в заголовках.
Аутентификация SSE: Маршрут SSE должен быть защищен стандартными механизмами аутентификации Laravel (сессии).
AntiFloodService: Интегрировать его вызовы в соответствующие API-методы контроллера (особенно для добавления URL).
Документация: Обновить "Landings System Documentation", если это необходимо, чтобы отразить изменения.
Этот план предоставляет пошаговое руководство. Отдельные задачи внутри фаз можно выполнять параллельно, если позволяет команда. Удачи!