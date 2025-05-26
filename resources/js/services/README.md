# SweetAlert2 Service Documentation

Унифицированный сервис для работы с модальными окнами на основе SweetAlert2.

## Установка и подключение

Сервис автоматически подключается в `app.js` и доступен глобально.

## Импорт

```javascript
// Импорт всего сервиса
import sweetAlertService from '@/services/sweetAlertService';

// Импорт отдельных методов
import {
  confirm,
  success,
  error,
  input,
  timedWarning,
  select,
  info,
} from '@/services/sweetAlertService';
```

## Основные методы

### 1. Подтверждение действия (confirm)

Используется для запроса подтверждения перед необратимыми действиями.

```javascript
// Базовое использование
const result = await confirm('Удалить запись?', 'Это действие нельзя отменить');

// С callback функцией
await confirm('Удалить запись?', 'Это действие нельзя отменить', async () => {
  // Код выполняется при подтверждении
  console.log('Запись удалена');
});

// С дополнительными опциями
await confirm('Отправить форму?', 'Проверьте правильность данных', null, {
  confirmButtonText: 'Отправить',
  cancelButtonText: 'Проверить еще раз',
});
```

**Параметры:**

- `title` (string) - Заголовок окна
- `message` (string) - Текст сообщения
- `onConfirm` (Function) - Callback при подтверждении
- `options` (Object) - Дополнительные опции SweetAlert2

### 2. Уведомление об успехе (success)

Показывает уведомление об успешном выполнении операции.

```javascript
// Базовое использование
await success('Успех!', 'Данные сохранены');

// С автозакрытием через 2 секунды
await success('Сохранено!', 'Изменения применены', { timer: 2000 });
```

**Параметры:**

- `title` (string) - Заголовок уведомления
- `message` (string) - Текст сообщения
- `options` (Object) - Дополнительные опции

### 3. Уведомление об ошибке (error)

Показывает уведомление об ошибке.

```javascript
await error('Ошибка!', 'Не удалось подключиться к серверу');
```

**Параметры:**

- `title` (string) - Заголовок уведомления
- `message` (string) - Текст ошибки
- `options` (Object) - Дополнительные опции

### 4. Поле ввода (input)

Модальное окно с полем для ввода данных.

```javascript
// Ввод текста
const name = await input('Как вас зовут?', 'Введите имя', 'text');

// Ввод email с валидацией
const email = await input('Email', 'example@domain.com', 'email');

// Кастомная валидация
const age = await input('Возраст', 'Введите ваш возраст', 'number', value => {
  if (value < 18) return 'Возраст должен быть больше 18';
  if (value > 100) return 'Возраст должен быть меньше 100';
  return null;
});
```

**Параметры:**

- `title` (string) - Заголовок окна
- `placeholder` (string) - Placeholder для поля
- `inputType` (string) - Тип поля (text, email, password, number и т.д.)
- `validator` (Function) - Функция валидации
- `options` (Object) - Дополнительные опции

### 5. Предупреждение с таймером (timedWarning)

Показывает предупреждение с автозакрытием по таймеру.

```javascript
const continued = await timedWarning(
  'Сессия истекает!',
  'Нажмите "Продолжить" для продления сессии',
  10000, // 10 секунд
  async () => {
    console.log('Сессия продлена');
  }
);
```

**Параметры:**

- `title` (string) - Заголовок предупреждения
- `message` (string) - Текст предупреждения
- `timer` (number) - Время в миллисекундах
- `onContinue` (Function) - Callback при нажатии "Продолжить"
- `options` (Object) - Дополнительные опции

### 6. Выбор из списка (select)

Модальное окно с выпадающим списком.

```javascript
const roles = [
  { value: 'admin', text: 'Администратор' },
  { value: 'user', text: 'Пользователь' },
  { value: 'guest', text: 'Гость' },
];

const selectedRole = await select(
  'Выберите роль',
  roles,
  'Выберите роль пользователя...',
  async role => {
    console.log('Выбрана роль:', role);
  }
);
```

**Параметры:**

- `title` (string) - Заголовок окна
- `options` (Array) - Массив опций `[{value, text}]`
- `placeholder` (string) - Placeholder для селекта
- `onSelect` (Function) - Callback при выборе
- `additionalOptions` (Object) - Дополнительные опции

### 7. Информационное сообщение (info)

Показывает информационное сообщение.

```javascript
await info('Информация', 'Новая функция доступна в системе');
```

**Параметры:**

- `title` (string) - Заголовок
- `message` (string) - Текст сообщения
- `options` (Object) - Дополнительные опции

## Дополнительные методы

### close()

Закрывает текущее модальное окно.

```javascript
sweetAlertService.close();
```

### isVisible()

Проверяет, открыто ли модальное окно.

```javascript
if (sweetAlertService.isVisible()) {
  console.log('Модальное окно открыто');
}
```

## Стилизация

Все модальные окна используют кастомные CSS классы для стилизации:

- `.swal-popup` - основной контейнер
- `.swal-title` - заголовок
- `.swal-content` - контент
- `.swal-confirm-btn` - кнопка подтверждения
- `.swal-cancel-btn` - кнопка отмены
- `.swal-input` - поле ввода
- `.swal-select` - селект

### Модификаторы кнопок

- `.btn-primary` - основная кнопка (синяя)
- `.btn-success` - кнопка успеха (зеленая)
- `.btn-danger` - кнопка опасности (красная)
- `.btn-warning` - кнопка предупреждения (оранжевая)
- `.btn-info` - информационная кнопка (синяя)
- `.btn-secondary` - вторичная кнопка (серая)

### Дополнительные классы

- `.swal-wide` - увеличенная ширина модального окна
- `.swal-compact` - компактный размер

```javascript
// Пример использования дополнительных классов
await confirm('Заголовок', 'Сообщение', null, {
  customClass: {
    popup: 'swal-popup swal-wide',
  },
});
```

## Адаптивность

Все модальные окна адаптированы для мобильных устройств:

- Автоматическое изменение размеров на экранах < 768px
- Вертикальное расположение кнопок на мобильных
- Оптимизированные отступы и размеры шрифтов

## Примеры использования

Файл `sweetAlertExamples.js` содержит готовые примеры для всех типов модальных окон. Для тестирования в консоли браузера:

```javascript
// Подтверждение удаления
sweetAlertExamples.deleteConfirmation();

// Ввод имени
sweetAlertExamples.inputUserName();

// Выбор роли
sweetAlertExamples.selectUserRole();

// Цепочка модальных окон
sweetAlertExamples.modalChain();
```

## Обработка ошибок

Все методы сервиса включают обработку ошибок и логирование в консоль. В случае ошибки методы возвращают безопасные значения:

- `confirm()` возвращает `false`
- `input()` и `select()` возвращают `null`
- Остальные методы завершаются без ошибок

## Интеграция с существующим кодом

### Замена стандартных confirm/alert

```javascript
// Вместо
if (confirm('Удалить?')) {
  deleteItem();
}

// Используйте
const result = await confirm('Удалить запись?', 'Это действие нельзя отменить');
if (result) {
  deleteItem();
}
```

### Обработка AJAX запросов

```javascript
try {
  const response = await fetch('/api/data');
  if (response.ok) {
    await success('Успех!', 'Данные загружены');
  } else {
    await error('Ошибка!', 'Не удалось загрузить данные');
  }
} catch (err) {
  await error('Ошибка сети!', 'Проверьте интернет-соединение');
}
```

## Конфигурация по умолчанию

Сервис использует следующие настройки по умолчанию:

- Отключена стилизация кнопок SweetAlert2 (`buttonsStyling: false`)
- Кнопки расположены в обратном порядке (`reverseButtons: true`)
- Для критических действий отключен клик вне модального окна
- Автозакрытие уведомлений об успехе через 3 секунды
- Кастомные CSS классы для интеграции с дизайном проекта
