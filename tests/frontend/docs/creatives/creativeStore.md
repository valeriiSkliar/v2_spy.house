# Test Cases for CreativesStore

Тест-кейсы для проверки функциональности хранилища креативов.

## Test Case 1: Initialization

### Description

Проверяет правильность инициализации хранилища с корректными значениями по умолчанию.

### Steps

1. Создать новый экземпляр `creativesStore`
2. Проверить начальные значения состояния: `loading`, `error`, `currentTab`, `currentPage`, `filters` и другие

### Expected Result

- `loading` равно `false`
- `error` равно `null`
- `currentTab` равно `'facebook'`
- `currentPage` равно `1`
- Остальные свойства (`filters`, `totalPages`, `selectedCreative`) установлены в значения по умолчанию (пустые или `null`)

## Test Case 2: Tab Switching

### Description

Проверяет правильность переключения вкладок, сброса пагинации и запуска загрузки креативов.

### Steps

1. Вызвать метод переключения вкладки на `'tiktok'`
2. Проверить состояние `currentTab`, `currentPage` и вызов `loadCreatives`

### Expected Result

- `currentTab` равно `'tiktok'`
- `currentPage` сброшено на `1`
- `loadCreatives` вызван с параметрами, отражающими новую вкладку

## Test Case 3: Filter Updates

### Description

Тестирует обновление фильтров, сброс пагинации и обновление URL.

### Steps

1. Обновить фильтры новыми значениями:
   ```javascript
   { search: 'test', category: 'video' }
   ```
2. Проверить состояние `currentPage` и подтвердить обновление URL

### Expected Result

- `currentPage` сброшено на `1`
- URL отражает обновленные фильтры: `?search=test&category=video`
- Внутреннее состояние `filters` соответствует новым значениям

## Test Case 4: Pagination

### Description

Проверяет установку новой страницы, обновление текущей страницы и загрузку креативов.

### Steps

1. Вызвать метод установки страницы на `2`
2. Проверить состояние `currentPage` и подтвердить запуск `loadCreatives`

### Expected Result

- `currentPage` равно `2`
- `loadCreatives` вызван с параметрами, включающими новый номер страницы

## Test Case 5: Details Panel

### Description

Проверяет правильность открытия и закрытия панели деталей для креатива.

### Steps

1. Открыть панель деталей с мок-объектом креатива:
   ```javascript
   { id: 1, title: 'Test Creative' }
   ```
2. Проверить состояние `selectedCreative` и `detailsPanelOpen`
3. Закрыть панель деталей и повторно проверить состояние

### Expected Result

**После открытия:**

- `selectedCreative` соответствует мок-креативу
- `detailsPanelOpen` равно `true`

**После закрытия:**

- `selectedCreative` равно `null`
- `detailsPanelOpen` равно `false`

## Test Case 6: Caching

### Description

Проверяет работу кэширования и предотвращение избыточных API-вызовов при загрузке креативов с одинаковыми параметрами.

### Steps

1. Загрузить креативы с определенными параметрами:
   ```javascript
   { tab: 'facebook', page: 1, search: 'test' }
   ```
2. Повторить загрузку с теми же параметрами
3. Проверить, что данные извлечены из кэша вместо нового API-вызова

### Expected Result

- Первая загрузка делает API-вызов и кэширует результат
- Вторая загрузка извлекает данные из кэша
- Дополнительный API-вызов не выполняется

## Test Case 7: URL Handling

### Description

Тестирует правильную загрузку фильтров и состояния из URL при инициализации.

### Steps

1. Симулировать URL с параметрами запроса:
   ```
   ?tab=tiktok&page=2&search=test
   ```
2. Инициализировать `creativesStore` и проверить его состояние

### Expected Result

- `currentTab` равно `'tiktok'`
- `currentPage` равно `2`
- `filters.search` равно `'test'`
- Состояние хранилища соответствует параметрам URL

## Test Case 8: API Interaction

### Description

Проверяет правильную обработку ответов API и ошибок при получении креативов.

### Steps

1. **Успешный ответ:**

   - Замокать успешный API-ответ с примерными данными (список креативов, общий счетчик и т.д.)
   - Запустить `loadCreatives` и проверить состояние хранилища

2. **Ошибка API:**
   - Замокать ошибочный API-ответ
   - Повторить процесс

### Expected Result

**При успехе:**

- `loading` равно `false`
- `error` равно `null`
- Данные хранилища (`creatives`, `totalCount`) соответствуют API-ответу

**При ошибке:**

- `loading` равно `false`
- `error` содержит детали ошибки
- Данные не обновляются

## Test Case 9: Invalid Tab Switching (Edge Case)

### Description

Проверяет обработку попытки переключения на несуществующую вкладку.

### Steps

1. Вызвать метод переключения вкладки на несуществующее значение: `'invalid_tab'`
2. Проверить, что состояние остается стабильным

### Expected Result

- `currentTab` остается на предыдущем валидном значении
- Ошибка логируется или обрабатывается корректно
- `loadCreatives` не вызывается с некорректными параметрами

## Test Case 10: Filter Updates with Invalid Data (Edge Case)

### Description

Тестирует обработку некорректных данных в фильтрах.

### Steps

1. Обновить фильтры с невалидными значениями:
   ```javascript
   { search: null, category: 123, dateRange: '' }
   ```
2. Обновить фильтры с пустыми значениями:
   ```javascript
   { search: '', category: undefined }
   ```

### Expected Result

- Невалидные значения игнорируются или нормализуются
- Пустые строки обрабатываются корректно
- URL не содержит некорректных параметров
- Приложение остается в стабильном состоянии

## Test Case 11: Pagination Beyond Boundaries (Edge Case)

### Description

Проверяет поведение пагинации при выходе за допустимые границы.

### Steps

1. Попытаться установить отрицательную страницу: `-1`
2. Попытаться установить страницу равную `0`
3. Попытаться установить страницу больше `totalPages + 10`
4. Попытаться установить страницу с некорректным типом: `'abc'`

### Expected Result

- Отрицательные значения и ноль нормализуются до `1`
- Значения больше `totalPages` нормализуются до максимальной страницы
- Некорректные типы данных игнорируются
- `loadCreatives` вызывается только с валидными значениями

## Test Case 12: Details Panel with Invalid Data (Edge Case)

### Description

Проверяет поведение панели деталей при передаче некорректных данных.

### Steps

1. Попытаться открыть панель с `null`:
   ```javascript
   openDetailsPanel(null);
   ```
2. Попытаться открыть панель с объектом без обязательных полей:
   ```javascript
   openDetailsPanel({});
   ```
3. Попытаться открыть панель с некорректным типом данных:
   ```javascript
   openDetailsPanel('string_instead_of_object');
   ```

### Expected Result

- `null` и некорректные данные не приводят к открытию панели
- `selectedCreative` остается `null`
- `detailsPanelOpen` остается `false`
- Ошибки обрабатываются без сбоев приложения

## Test Case 13: Cache Overflow Management (Edge Case)

### Description

Проверяет поведение кэша при превышении лимита записей.

### Steps

1. Загрузить креативы для 60 различных комбинаций параметров (превышение лимита в 50)
2. Проверить, что старые записи удаляются из кэша
3. Убедиться, что наиболее используемые записи сохраняются

### Expected Result

- Кэш не превышает установленный лимит в 50 записей
- Используется стратегия LRU (Least Recently Used) для удаления записей
- Производительность приложения не деградирует

## Test Case 14: URL with Invalid Parameters (Edge Case)

### Description

Тестирует обработку некорректных параметров URL при инициализации.

### Steps

1. Симулировать URL с некорректными параметрами:
   ```
   ?tab=invalid&page=abc&search=&invalidParam=test
   ```
2. Симулировать URL с отсутствующими параметрами:
   ```
   ?page=2
   ```
3. Инициализировать `creativesStore` и проверить состояние

### Expected Result

- Некорректные значения `tab` игнорируются, используется дефолтное значение
- Некорректные значения `page` нормализуются до `1`
- Пустые параметры фильтров игнорируются
- Неизвестные параметры не влияют на состояние
- Отсутствующие параметры заполняются дефолтными значениями

## Test Case 15: API Empty and Malformed Responses (Edge Case)

### Description

Проверяет обработку нестандартных ответов API.

### Steps

1. **Пустой список креативов:**

   - Замокать API-ответ с пустым массивом `creatives: []`
   - Запустить `loadCreatives`

2. **Отсутствие обязательных полей:**

   - Замокать ответ без поля `totalCount`
   - Замокать ответ с некорректной структурой данных

3. **Timeout и network errors:**
   - Симулировать таймаут запроса
   - Симулировать потерю сетевого соединения

### Expected Result

**При пустом списке:**

- Отображается сообщение "Нет данных"
- `totalPages` равно `0`
- Интерфейс остается стабильным

**При некорректной структуре:**

- Устанавливается соответствующая ошибка
- Дефолтные значения используются для отсутствующих полей
- Приложение не падает

**При сетевых ошибках:**

- Показывается понятное сообщение об ошибке
- Предоставляется возможность повторить запрос
- Состояние `loading` корректно сбрасывается

## Test Case 16: Concurrent Operations (Edge Case)

### Description

Проверяет поведение при одновременных операциях (например, быстрые переключения вкладок).

### Steps

1. Быстро переключить вкладки: `facebook` → `tiktok` → `facebook`
2. Быстро изменить несколько фильтров подряд
3. Запустить загрузку и сразу переключить вкладку

### Expected Result

- Только последняя операция применяется
- Предыдущие запросы отменяются (debounce/throttle)
- Нет гонки состояний (race conditions)
- UI остается отзывчивым

## Test Case 17: Memory Leak Prevention (Edge Case)

### Description

Проверяет отсутствие утечек памяти при длительном использовании.

### Steps

1. Выполнить 100+ операций загрузки креативов
2. Переключить вкладки 50+ раз
3. Открыть и закрыть панель деталей 30+ раз
4. Мониторить использование памяти

### Expected Result

- Память не растет неконтролируемо
- Неиспользуемые данные очищаются
- Event listeners корректно удаляются
- Нет накопления устаревших ссылок
