# Локализация Фронтенда

Система локализации фронтенда позволяет легко переводить сообщения пользователю в JavaScript коде.

## Архитектура

- **Языковые файлы**: `lang/{locale}/frontend.php` - содержат переводы
- **JavaScript утилита**: `resources/js/utils/localization.js` - основная логика
- **Blade компонент**: `resources/views/components/frontend-translations.blade.php` - инициализация
- **Валидация форм**: `resources/js/utils/form-validation.js` - локализованная валидация

## Использование

### Базовое использование

```javascript
// Простой перевод
const message = trans('frontend.errors.comment_save_failed');

// Перевод с параметрами
const message = trans('frontend.errors.field_required', { field: 'Email' });
```

### В Alpine.js компонентах

```html
<div x-data="{ message: '' }" x-init="message = alpineTrans('frontend.success.data_saved')">
  <span x-text="message"></span>
</div>
```

### Валидация форм

```javascript
// Определение правил валидации
const validationRules = {
  email: [{ type: 'required' }, { type: 'email' }],
  password: [{ type: 'required' }, { type: 'minLength', value: 8 }],
};

// Инициализация валидации
const form = document.querySelector('#my-form');
window.FormValidator.initRealTimeValidation(form, validationRules);

// Валидация при отправке
form.addEventListener('submit', function (e) {
  e.preventDefault();
  const isValid = window.FormValidator.validateForm(form, validationRules);
  if (isValid) {
    // Отправить форму
  }
});
```

### Подтверждения действий

```javascript
// Локализованное подтверждение
if (confirm(trans('frontend.confirmations.delete_comment'))) {
  deleteComment();
}
```

## Структура языковых файлов

```php
<?php
// lang/ru/frontend.php

return [
    'errors' => [
        'comment_save_failed' => 'Ошибка при сохранении комментария. Попробуйте снова.',
        'network_error' => 'Ошибка сети. Проверьте подключение к интернету.',
        'server_error' => 'Ошибка сервера. Попробуйте позже.',
    ],

    'success' => [
        'comment_added' => 'Комментарий успешно добавлен.',
        'data_saved' => 'Данные успешно сохранены.',
    ],

    'form' => [
        'required_field' => 'Обязательное поле',
        'invalid_email' => 'Некорректный email адрес',
    ],
];
```

## Добавление новых переводов

1. Добавьте ключ в `lang/ru/frontend.php` и `lang/en/frontend.php`
2. Используйте в JavaScript коде: `trans('frontend.your.new.key')`

## Валидация форм

### Доступные правила

- `required` - обязательное поле
- `email` - валидация email
- `minLength` - минимальная длина
- `maxLength` - максимальная длина
- `match` - совпадение с другим полем

### Пример комплексной валидации

```javascript
const validationRules = {
  email: [{ type: 'required' }, { type: 'email' }],
  password: [{ type: 'required' }, { type: 'minLength', value: 8 }],
  password_confirmation: [{ type: 'required' }, { type: 'match', field: 'input[name="password"]' }],
};
```

## Интеграция с существующим кодом

### Замена жестко прописанных сообщений

**Было:**

```javascript
alert('Error saving comment. Please try again.');
```

**Стало:**

```javascript
alert(trans('frontend.errors.comment_save_failed'));
```

### Обработка AJAX ошибок

```javascript
.catch(error => {
    console.error('Error:', error);
    if (error.message.includes('422')) {
        showToast(trans('frontend.errors.validation_failed'), 'error');
    } else {
        showToast(trans('frontend.errors.server_error'), 'error');
    }
});
```

## Автоматическое определение языка

Система автоматически определяет текущий язык из:

1. Атрибута `lang` HTML элемента
2. Настройки Laravel `app()->getLocale()`

## Расширение функциональности

### Добавление новых правил валидации

```javascript
// В utils/form-validation.js
this.rules.customRule = (value, params) => {
  // Ваша логика валидации
  return isValid;
};
```

### Создание пользовательских функций

```javascript
// Создание функции для конкретного типа форм
function validateContactForm(form) {
  const rules = {
    name: [{ type: 'required' }],
    email: [{ type: 'required' }, { type: 'email' }],
    message: [{ type: 'required' }, { type: 'minLength', value: 10 }],
  };

  return window.FormValidator.validateForm(form, rules);
}
```

## Производительность

- Переводы загружаются один раз при инициализации страницы
- Система использует кэширование браузера
- Минимальное влияние на размер bundle

## Отладка

Включите консольные сообщения для отладки:

```javascript
// В utils/localization.js установите DEBUG = true
const DEBUG = true;

if (DEBUG && !translation) {
  console.warn(`Translation not found for key: ${key}`);
}
```
