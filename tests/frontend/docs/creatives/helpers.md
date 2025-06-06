# Test Cases for Creative Utilities

Описание тест-кейсов для каждой функции-утилиты:

## 1. formatFileSize(bytes)

### Test Case 1.1: Входные данные 0 байт

- **Input:** `0`
- **Expected Output:** `'0 B'`

### Test Case 1.2: Входные данные меньше 1 KB

- **Input:** `500`
- **Expected Output:** `'500.0 B'`

### Test Case 1.3: Точно 1 KB

- **Input:** `1024`
- **Expected Output:** `'1.0 KB'`

### Test Case 1.4: Несколько KB

- **Input:** `3500`
- **Expected Output:** `'3.4 KB'`

### Test Case 1.5: Точно 1 MB

- **Input:** `1024 * 1024`
- **Expected Output:** `'1.0 MB'`

### Test Case 1.6: Несколько MB

- **Input:** `2.5 * 1024 * 1024`
- **Expected Output:** `'2.5 MB'`

### Test Case 1.7: Тестирование GB и TB

- **Expected Output:** Корректно отформатированная строка с суффиксом GB или TB

### Test Case 1.8: Большое число с десятичной частью

- **Input:** `1500 * 1024 * 1024 * 1024`
- **Expected Output:** `'1.5 TB'`

## 2. formatDate(date, format)

### Test Case 2.1: Null или undefined

- **Input:** `null` или `undefined`
- **Expected Output:** `''`

### Test Case 2.2: Относительный формат - сегодня

- **Input:** `new Date(), 'relative'`
- **Expected Output:** `'Сегодня'`

### Test Case 2.3: Относительный формат - вчера

- **Input:** `yesterday date, 'relative'`
- **Expected Output:** `'Вчера'`

### Test Case 2.4: Относительный формат - 3 дня назад

- **Input:** `3 days ago date, 'relative'`
- **Expected Output:** `'3 дней назад'`

### Test Case 2.5: Относительный формат - 2 недели назад

- **Input:** `14 days ago date, 'relative'`
- **Expected Output:** `'2 недель назад'`

### Test Case 2.6: Относительный формат - 2 месяца назад

- **Input:** `60 days ago date, 'relative'`
- **Expected Output:** `'2 месяцев назад'`

### Test Case 2.7: Относительный формат - 2 года назад

- **Input:** `730 days ago date, 'relative'`
- **Expected Output:** `'2 лет назад'`

### Test Case 2.8: Короткий формат

- **Input:** `'2023-10-26T10:00:00Z', 'short'`
- **Expected Output:** `'26.10.2023'`

### Test Case 2.9: Полный формат

- **Input:** `new Date('2023-10-26T14:35:00'), 'full'`
- **Expected Output:** `'26.10.2023, 14:35'`

### Test Case 2.10: Формат по умолчанию

- **Input:** `5 days ago date`
- **Expected Output:** `'5 дней назад'`

### Test Case 2.11: Неверный формат

- **Input:** `new Date(), 'invalidFormat'`
- **Expected Output:** `'26.10.2023'` (toLocaleDateString('ru-RU'))

## 3. isImageFile(fileType)

### Test Case 3.1: Обычный тип изображения

- **Input:** `'jpg'`
- **Expected Output:** `true`

### Test Case 3.2: Тип изображения в разном регистре

- **Input:** `'PNG'`
- **Expected Output:** `true`

### Test Case 3.3: Не изображение

- **Input:** `'pdf'`
- **Expected Output:** `false`

### Test Case 3.4: Null или undefined

- **Input:** `null` или `undefined`
- **Expected Output:** `false`

### Test Case 3.5: Пустая строка

- **Input:** `''`
- **Expected Output:** `false`

### Test Case 3.6: Все поддерживаемые типы

- **Input:** `'jpeg', 'gif', 'webp', 'svg', 'bmp'`
- **Expected Output:** `true` для каждого

## 4. isVideoFile(fileType)

### Test Case 4.1: Обычный тип видео

- **Input:** `'mp4'`
- **Expected Output:** `true`

### Test Case 4.2: Тип видео в разном регистре

- **Input:** `'MOV'`
- **Expected Output:** `true`

### Test Case 4.3: Не видео

- **Input:** `'txt'`
- **Expected Output:** `false`

### Test Case 4.4: Null или undefined

- **Input:** `null` или `undefined`
- **Expected Output:** `false`

### Test Case 4.5: Пустая строка

- **Input:** `''`
- **Expected Output:** `false`

### Test Case 4.6: Все поддерживаемые типы

- **Input:** `'webm', 'avi', 'mkv', 'wmv', 'flv'`
- **Expected Output:** `true` для каждого

## 5. getFileIcon(fileType)

### Test Case 5.1: Тип изображения

- **Input:** `'png'`
- **Expected Output:** `'fas fa-image'`

### Test Case 5.2: Тип видео

- **Input:** `'mp4'`
- **Expected Output:** `'fas fa-video'`

### Test Case 5.3: PDF файл

- **Input:** `'pdf'`
- **Expected Output:** `'fas fa-file-pdf'`

### Test Case 5.4: Word документ

- **Input:** `'doc'`
- **Expected Output:** `'fas fa-file-word'`

### Test Case 5.5: Word документ (docx)

- **Input:** `'docx'`
- **Expected Output:** `'fas fa-file-word'`

### Test Case 5.6: Архив

- **Input:** `'zip'`
- **Expected Output:** `'fas fa-file-archive'`

### Test Case 5.7: Неизвестный тип

- **Input:** `'unknownType'`
- **Expected Output:** `'fas fa-file'`

### Test Case 5.8: Null или undefined

- **Input:** `null` или `undefined`
- **Expected Output:** `'fas fa-file'`

### Test Case 5.9: Пустая строка

- **Input:** `''`
- **Expected Output:** `'fas fa-file'`

## 6. truncateText(text, maxLength, suffix)

### Test Case 6.1: Текст короче maxLength

- **Input:** `'Hello', 10`
- **Expected Output:** `'Hello'`

### Test Case 6.2: Текст длиннее maxLength

- **Input:** `'This is a long text', 10`
- **Expected Output:** `'This is a ...'`

### Test Case 6.3: Текст равен maxLength

- **Input:** `'ExactlyTen', 10`
- **Expected Output:** `'ExactlyTen'`

### Test Case 6.4: Null или undefined

- **Input:** `null` или `undefined`
- **Expected Output:** `''`

### Test Case 6.5: Пустая строка

- **Input:** `''`
- **Expected Output:** `''`

### Test Case 6.6: Значения по умолчанию

- **Input:** `'This is a very long text that definitely exceeds fifty characters by a lot.'`
- **Expected Output:** `'This is a very long text that definitely exceeds fif...'`

### Test Case 6.7: Пользовательский суффикс

- **Input:** `'Another long text', 10, '---'`
- **Expected Output:** `'Another lo---'`

### Test Case 6.8: maxLength равен 0

- **Input:** `'Some text', 0`
- **Expected Output:** `'...'`

### Test Case 6.9: Текст с пробелами

- **Input:** `'Text with space ', 16`
- **Expected Output:** `'Text with space...'`

## 7. debounce(func, wait)

### Test Case 7.1: Функция вызывается один раз после нескольких вызовов

- **Action:** Вызвать debounced функцию 3 раза подряд
- **Expected Behavior:** Оригинальная функция выполняется только один раз, после wait мс от последнего вызова

### Test Case 7.2: Функция вызывается с последними аргументами

- **Action:** Вызвать с arg1, затем сразу с arg2
- **Expected Behavior:** Оригинальная функция выполняется один раз с arg2

### Test Case 7.3: Последующие вызовы после периода ожидания

- **Action:** Вызвать функцию, подождать выполнения, вызвать снова
- **Expected Behavior:** Функция выполняется для первого и второго вызова

### Test Case 7.4: Контекст this

- **Action:** Привязать функцию к объекту
- **Expected Behavior:** `this` внутри функции ссылается на правильный объект

## 8. throttle(func, limit)

### Test Case 8.1: Немедленное выполнение при первом вызове

- **Action:** Вызвать throttled функцию
- **Expected Behavior:** Функция выполняется немедленно

### Test Case 8.2: Игнорирование вызовов в пределах лимита

- **Action:** Вызвать функцию несколько раз в пределах limit мс
- **Expected Behavior:** Функция выполняется только один раз

### Test Case 8.3: Повторный вызов после лимита

- **Action:** Вызвать функцию, подождать limit мс, вызвать снова
- **Expected Behavior:** Функция выполняется дважды

### Test Case 8.4: Аргументы первого вызова

- **Action:** Вызвать с arg1, затем с arg2 в период throttle
- **Expected Behavior:** Функция выполняется с arg1

### Test Case 8.5: Контекст this

- **Action:** Привязать функцию к объекту
- **Expected Behavior:** `this` внутри функции ссылается на правильный объект

## 9. copyToClipboard(text)

### Test Case 9.1: Современные браузеры - успешное копирование

- **Setup:** Mock `navigator.clipboard.writeText` для успешного выполнения
- **Action:** `copyToClipboard('some text')`
- **Expected Output:** Promise возвращает `true`

### Test Case 9.2: Fallback - успешное копирование

- **Setup:** Mock `navigator.clipboard` как `undefined`, `document.execCommand('copy')` возвращает `true`
- **Action:** `copyToClipboard('fallback text')`
- **Expected Output:** Promise возвращает `true`

### Test Case 9.3: Современные браузеры - ошибка

- **Setup:** Mock `navigator.clipboard.writeText` для отклонения с ошибкой
- **Action:** `copyToClipboard('error text')`
- **Expected Output:** Promise возвращает `false`, вызывается `console.error`

### Test Case 9.4: Fallback - ошибка

- **Setup:** Mock `document.execCommand('copy')` возвращает `false`
- **Action:** `copyToClipboard('fallback error')`
- **Expected Output:** Promise возвращает `false`

## 10. downloadFile(url, filename)

### Test Case 10.1: Успешная загрузка

- **Setup:** Mock fetch для успешного ответа с blob()
- **Action:** `downloadFile('some-url.txt', 'myFile.txt')`
- **Expected Behavior:** fetch вызывается, создается ссылка с правильными атрибутами

### Test Case 10.2: Загрузка с именем по умолчанию

- **Action:** `downloadFile('some-url.dat')`
- **Expected Behavior:** download атрибут устанавливается как 'download'

### Test Case 10.3: Ошибка fetch

- **Setup:** Mock fetch для отклонения с ошибкой
- **Action:** `downloadFile('bad-url')`
- **Expected Behavior:** Promise отклоняется с ошибкой 'Ошибка при скачивании файла'

## 11. generateId()

### Test Case 11.1: Возвращает непустую строку

- **Expected Output:** `typeof generateId() === 'string' && generateId().length > 0`

### Test Case 11.2: Генерирует разные ID

- **Expected Output:** `generateId() !== generateId()`

### Test Case 11.3: Проверка формата ID

- **Expected Output:** ID состоит из двух частей: Date.now() и Math.random(), обе в base36

## 12. isEmpty(value)

### Test Case 12.1: Input null

- **Expected Output:** `true`

### Test Case 12.2: Input undefined

- **Expected Output:** `true`

### Test Case 12.3: Пустая строка

- **Input:** `''`
- **Expected Output:** `true`

### Test Case 12.4: Строка с пробелами

- **Input:** `' '`
- **Expected Output:** `true`

### Test Case 12.5: Непустая строка

- **Input:** `'text'`
- **Expected Output:** `false`

### Test Case 12.6: Пустой массив

- **Input:** `[]`
- **Expected Output:** `true`

### Test Case 12.7: Непустой массив

- **Input:** `[1, 2]`
- **Expected Output:** `false`

### Test Case 12.8: Пустой объект

- **Input:** `{}`
- **Expected Output:** `true`

### Test Case 12.9: Непустой объект

- **Input:** `{ a: 1 }`
- **Expected Output:** `false`

### Test Case 12.10: Число 0

- **Input:** `0`
- **Expected Output:** `false`

### Test Case 12.11: Число 123

- **Input:** `123`
- **Expected Output:** `false`

### Test Case 12.12: Boolean false

- **Input:** `false`
- **Expected Output:** `false`

### Test Case 12.13: Boolean true

- **Input:** `true`
- **Expected Output:** `false`

## 13. deepClone(obj)

### Test Case 13.1: Input null

- **Expected Output:** `null`

### Test Case 13.2: Примитив

- **Input:** `123`
- **Expected Output:** `123`

### Test Case 13.3: Date объект

- **Action:** `const date = new Date(); const cloned = deepClone(date);`
- **Expected Output:** `cloned` - новый Date объект, `cloned.getTime() === date.getTime()`, `cloned !== date`

### Test Case 13.4: Плоский объект

- **Input:** `{ a: 1, b: 'text' }`
- **Expected Output:** Новый объект с идентичными ключ-значение парами

### Test Case 13.5: Вложенные объекты

- **Input:** `{ a: 1, b: { c: 2, d: 'nested' } }`
- **Expected Output:** Новый объект с глубоко клонированными вложенными структурами

### Test Case 13.6: Массив примитивов

- **Input:** `[1, 2, 3]`
- **Expected Output:** Новый массив с теми же элементами

### Test Case 13.7: Массив объектов

- **Input:** `[{ id: 1 }, { id: 2 }]`
- **Expected Output:** Новый массив с новыми клонированными объектами

### Test Case 13.8: Объект с массивами и вложенными объектами

- **Input:** `{ data: [ { value: 10 }, { value: 20 } ], config: { active: true } }`
- **Expected Output:** Полностью клонированная структура

## 14. isEqual(a, b)

### Test Case 14.1: Идентичные примитивы

- **Input:** `5, 5`
- **Expected Output:** `true`

### Test Case 14.2: Разные примитивы

- **Input:** `5, 10` или `5, '5'`
- **Expected Output:** `false`

### Test Case 14.3: Один null, другой объект

- **Input:** `null, {}`
- **Expected Output:** `false`

### Test Case 14.4: Оба null

- **Expected Output:** `true`

### Test Case 14.5: Идентичные плоские объекты

- **Input:** `{ a: 1, b: 2 }, { a: 1, b: 2 }`
- **Expected Output:** `true`

### Test Case 14.6: Плоские объекты с разными значениями

- **Input:** `{ a: 1, b: 2 }, { a: 1, b: 3 }`
- **Expected Output:** `false`

### Test Case 14.7: Плоские объекты с разными ключами

- **Input:** `{ a: 1, b: 2 }, { a: 1, c: 2 }`
- **Expected Output:** `false`

### Test Case 14.8: Объекты с разным порядком ключей

- **Input:** `{ a: 1, b: 2 }, { b: 2, a: 1 }`
- **Expected Output:** `true`

### Test Case 14.9: Идентичные вложенные объекты

- **Input:** `{ a: { b: 1 } }, { a: { b: 1 } }`
- **Expected Output:** `true`

### Test Case 14.10: Разные вложенные объекты

- **Input:** `{ a: { b: 1 } }, { a: { b: 2 } }`
- **Expected Output:** `false`

### Test Case 14.11: Идентичные массивы примитивов

- **Input:** `[1, 2, 3], [1, 2, 3]`
- **Expected Output:** `true`

### Test Case 14.12: Массивы с разным порядком

- **Input:** `[1, 2, 3], [1, 3, 2]`
- **Expected Output:** `false`

### Test Case 14.13: Идентичные массивы объектов

- **Input:** `[{id:1},{id:2}], [{id:1},{id:2}]`
- **Expected Output:** `true`

### Test Case 14.14: Сравнение объекта и массива

- **Input:** `{}, []`
- **Expected Output:** `false`

### Test Case 14.15: Объекты с разным количеством ключей

- **Input:** `{a:1}, {a:1, b:2}`
- **Expected Output:** `false`

## 15. formatNumber(number)

### Test Case 15.1: Целое число меньше 1000

- **Input:** `123`
- **Expected Output:** `'123'`

### Test Case 15.2: Целое число с разделителями тысяч

- **Input:** `1234567`
- **Expected Output:** `'1 234 567'` (для локали 'ru-RU')

### Test Case 15.3: Число 0

- **Input:** `0`
- **Expected Output:** `'0'`

### Test Case 15.4: Число с десятичными знаками

- **Input:** `12345.678`
- **Expected Output:** `'12 345,678'` (для локали 'ru-RU')

### Test Case 15.5: Нечисловое значение

- **Input:** `'abc'` или `null`
- **Expected Output:** `'0'`

## 16. getContrastColor(backgroundColor)

### Test Case 16.1: Темный фон

- **Input:** `'#000000'`
- **Expected Output:** `'white'`

### Test Case 16.2: Светлый фон

- **Input:** `'#FFFFFF'`
- **Expected Output:** `'black'`

### Test Case 16.3: Средний цвет (яркость > 128)

- **Input:** `'#A0A0A0'`
- **Expected Output:** `'black'`

### Test Case 16.4: Средний цвет (яркость <= 128)

- **Input:** `'#707070'`
- **Expected Output:** `'white'`

### Test Case 16.5: Hex без

- **Input:** `'333333'`
- **Expected Output:** `'white'`

### Test Case 16.6: Null/undefined/пустая строка

- **Expected Output:** `'black'`

## 17. isValidUrl(url)

### Test Case 17.1: Валидный HTTP URL

- **Input:** `'http://example.com'`
- **Expected Output:** `true`

### Test Case 17.2: Валидный HTTPS URL

- **Input:** `'https://www.example.com/path?query=value#fragment'`
- **Expected Output:** `true`

### Test Case 17.3: Невалидная строка URL

- **Input:** `'not_a_valid_url'`
- **Expected Output:** `false`

### Test Case 17.4: Пустая строка

- **Input:** `''`
- **Expected Output:** `false`

### Test Case 17.5: Относительный URL

- **Input:** `'/some/path'`
- **Expected Output:** `false`

### Test Case 17.6: URL только с протоколом

- **Input:** `'http://'`
- **Expected Output:** `false`

## 18. escapeHtml(html)

### Test Case 18.1: Строка без HTML спецсимволов

- **Input:** `'Hello world'`
- **Expected Output:** `'Hello world'`

### Test Case 18.2: Строка с < и >

- **Input:** `'<div>content</div>'`
- **Expected Output:** `'&lt;div&gt;content&lt;/div&gt;'`

### Test Case 18.3: Строка с &

- **Input:** `'Me & You'`
- **Expected Output:** `'Me &amp; You'`

### Test Case 18.4: Строка с двойными кавычками

- **Input:** `'Attribute="value"'`
- **Expected Output:** `'Attribute=&quot;value&quot;'`

### Test Case 18.5: Строка с одинарными кавычками

- **Input:** `"It's a test"`
- **Expected Output:** `"It&#39;s a test"`

### Test Case 18.6: Пустая строка

- **Expected Output:** `''`

### Test Case 18.7: Смешанные спецсимволы

- **Input:** `'<script>alert("XSS & fun!");</script>'`
- **Expected Output:** `'&lt;script&gt;alert(&quot;XSS &amp; fun!&quot;);&lt;/script&gt;'`

## 19. createLoadingState()

### Test Case 19.1: Начальное состояние

- **Action:** `const state = createLoadingState();`
- **Expected State:** `state.loading === false`, `state.error === null`, `state.data === null`

### Test Case 19.2: setLoading(true)

- **Action:** `state.setLoading(true);`
- **Expected State:** `state.loading === true`, `state.error === null`

### Test Case 19.3: setLoading(false)

- **Action:** `state.setLoading(true); state.setLoading(false);`
- **Expected State:** `state.loading === false`

### Test Case 19.4: setError(error)

- **Action:** `const err = new Error('test'); state.setError(err);`
- **Expected State:** `state.loading === false`, `state.error === err`

### Test Case 19.5: setData(data)

- **Action:** `const d = { id: 1 }; state.setData(d);`
- **Expected State:** `state.loading === false`, `state.error === null`, `state.data === d`

### Test Case 19.6: reset()

- **Action:** `state.setLoading(true); state.setData({ val: 1 }); state.reset();`
- **Expected State:** `state.loading === false`, `state.error === null`, `state.data === null`

### Test Case 19.7: setLoading(true) сбрасывает предыдущую ошибку

- **Action:** `state.setError(new Error('prev error')); state.setLoading(true);`
- **Expected State:** `state.loading === true`, `state.error === null`
