# Система валидации форм профиля

## Обзор

Единая система валидации форм профиля обеспечивает консистентность UX/UI и функциональности всех форм в разделе профиля пользователя. Система базируется на централизованных константах, унифицированных методах валидации и стандартизированной структуре компонентов.

## Архитектура системы

### 1. Централизованные константы (`/resources/js/validation/validation-constants.js`)

Основной файл содержит все конфигурации валидации и унифицированные методы:

```javascript
// Конфигурации валидации
export const EMAIL_CONFIG = {
  minLength: 3,
  maxLength: 254,
  pattern: VALIDATION_PATTERNS.email,
  errorMessage: 'Please enter a valid email address',
};

export const PASSWORD_CONFIG = {
  minLength: 8,
  errorMessage: 'Password must be at least 8 characters long',
};

export const VERIFICATION_CODE_CONFIG = {
  length: 6,
  pattern: VALIDATION_PATTERNS.verificationCode,
  errorMessage: 'Please enter a valid 6-digit verification code',
};

// Унифицированные методы валидации
export const ValidationMethods = {
  validateEmail(value) { /* ... */ },
  validatePassword(value, minLength) { /* ... */ },
  validateVerificationCode(value) { /* ... */ },
  // ... другие методы
};
```

### 2. Структура компонентов форм

Каждая форма профиля состоит из двух файлов:

#### Основной файл формы (`change-{form-name}.js`)
```javascript
import { logger, loggerError } from '@/helpers/logger';
import { checkNotifications } from '@/helpers/notification-checker';
import { createAndShowToast } from '@/utils/uiHelpers';
import { config } from '../../config';
import { ajaxFetcher } from '../fetcher/ajax-fetcher';
import { hideInElement, showInElement } from '../loader';
import {
  handleFormValidationErrors,
  initFormValidation,
} from './change-{form-name}-validation';

// Основные функции
const cancelFormUpdate = async () => { /* ... */ };
const confirmFormUpdate = async (formData) => { /* ... */ };
const initiateFormUpdate = async (form, formData) => { /* ... */ };
const initCancelButton = () => { /* ... */ };
const mainFormHandler = () => { /* ... */ };
const initForm = () => { /* ... */ };

export { mainFormHandler, initForm };
```

#### Файл валидации (`change-{form-name}-validation.js`)
```javascript
import $ from 'jquery';
import 'jquery-validation';
import {
  FORM_CONFIG,
  VERIFICATION_CODE_CONFIG,
  ValidationMethods,
} from '../../validation/validation-constants.js';

function initFormValidation($form, isConfirmationStep) { /* ... */ }
function addCustomValidationMethods() { /* ... */ }
function handleFormValidationErrors(response, $form) { /* ... */ }

export { handleFormValidationErrors, initFormValidation };
```

### 3. Стандартные паттерны

#### Контейнерный подход
```javascript
const $formContainer = $('#form-name-form-container');
const $form = $formContainer.find('#form-name-form');
```

#### Унифицированная обработка ошибок
```javascript
if (response.success) {
  createAndShowToast(response.message, 'success');
  // Обработка успеха
} else {
  handleFormValidationErrors(response, $form);
  createAndShowToast(response.message || 'Error message', 'error');
}
```

#### Стандартизированное логирование
```javascript
logger('[DEBUG] Form Name - Action started', { debug: true });
loggerError('[ERROR] Form Name - Error occurred:', error);
```

## Реализованные формы

### ✅ Эталонные формы (готовые)
1. **Email Change** (`change-email.js` + `change-email-validation.js`)
2. **Personal Greeting** (`change-personal-greeting.js` + `change-personal-greeting-validation.js`)
3. **Settings Update** (`update-profile-settings.js` + `profile-settings-form-validation.js`)

### ✅ Обновленные формы
4. **Password Change** (`change-password.js` + `change-password-validation.js`)
5. **IP Restriction** (`update-ip-restriction.js` + `update-ip-restriction-validation.js`)

## Инструкция по имплементации новых форм

### Шаг 1: Создание файла валидации

Создайте файл `{form-name}-validation.js`:

```javascript
import $ from 'jquery';
import 'jquery-validation';
import {
  FORM_SPECIFIC_CONFIG, // добавьте в validation-constants.js
  VERIFICATION_CODE_CONFIG,
  ValidationMethods,
} from '../../validation/validation-constants.js';

/**
 * Initialize jQuery Validation for {form name} form
 * @param {jQuery} $form - The form element
 * @param {boolean} isConfirmationStep - Whether the form is in confirmation step
 * @returns {object} - The validator instance
 */
function initFormValidation($form, isConfirmationStep = false) {
  if (!$.validator) {
    console.error('jQuery Validation is not available');
    return null;
  }

  addCustomValidationMethods();

  const rules = {
    field_name: {
      required: true,
      customValidationMethod: true,
    },
    // Добавьте verification_code для двухшагового процесса
    ...(isConfirmationStep && {
      verification_code: {
        required: true,
        digits: true,
        exactLength: VERIFICATION_CODE_CONFIG.length,
      },
    }),
  };

  const validator = $form.validate({
    rules,
    messages: {
      field_name: { required: '', customValidationMethod: '' },
      ...(isConfirmationStep && {
        verification_code: { required: '', digits: '', exactLength: '' },
      }),
    },
    errorElement: 'div',
    errorClass: 'validation-error',
    validClass: 'valid',
    errorPlacement: function (error, element) {
      return false; // Только визуальная подсветка
    },
    highlight: function (element, errorClass, validClass) {
      $(element).addClass('error').removeClass(validClass);
    },
    unhighlight: function (element, errorClass, validClass) {
      $(element).removeClass('error').addClass(validClass);
    },
    invalidHandler: function (event, validator) {
      if (validator.numberOfInvalids() > 0) {
        const $firstError = $(validator.errorList[0].element);
        $firstError.focus();
      }
    },
  });

  return validator;
}

function addCustomValidationMethods() {
  // Добавьте специфичные методы валидации
  $.validator.addMethod(
    'customValidationMethod',
    function (value, element) {
      return ValidationMethods.validateCustomField(value);
    },
    'Custom error message'
  );

  // Стандартный метод для verification code
  $.validator.addMethod(
    'exactLength',
    function (value, element, length) {
      return this.optional(element) || value.length === length;
    },
    function (params, element) {
      return `Please enter exactly ${params} digits.`;
    }
  );
}

function handleFormValidationErrors(response, $form) {
  if (response.errors) {
    $form.find('input, textarea').removeClass('error valid');
    Object.keys(response.errors).forEach(field => {
      const $input = $form.find(`input[name="${field}"], textarea[name="${field}"]`);
      if ($input.length) {
        $input.addClass('error');
      }
    });
  }

  if (response.field_statuses) {
    Object.entries(response.field_statuses).forEach(([field, status]) => {
      const $field = $form.find(`[name="${field}"]`);
      if ($field.length) {
        $field.removeClass('error valid');
        if (status.status === 'error') {
          $field.addClass('error');
        } else if (status.status === 'success') {
          $field.addClass('valid');
        }
      }
    });
  }
}

export { handleFormValidationErrors, initFormValidation };
```

### Шаг 2: Обновление централизованных констант

Добавьте конфигурацию в `validation-constants.js`:

```javascript
export const FORM_SPECIFIC_CONFIG = {
  fieldName: 'value',
  errorMessage: 'Validation error message',
};

// В ValidationMethods добавьте:
validateCustomField(value) {
  // Логика валидации
  return true/false;
},
```

### Шаг 3: Создание основного файла формы

Создайте файл `{form-name}.js` по шаблону:

```javascript
import { logger, loggerError } from '@/helpers/logger';
import { checkNotifications } from '@/helpers/notification-checker';
import { createAndShowToast } from '@/utils/uiHelpers';
import $ from 'jquery';
import { config } from '../../config';
import { ajaxFetcher } from '../fetcher/ajax-fetcher';
import { hideInElement, showInElement } from '../loader';
import {
  handleFormValidationErrors,
  initFormValidation,
} from './{form-name}-validation';

/**
 * Cancel form update process
 */
const cancelFormUpdate = async () => {
  const $formContainer = $('#form-name-form-container');
  const $form = $formContainer.find('#form-name-form');
  let loader = null;

  logger('[DEBUG] Form Name - Cancel update requested');

  try {
    loader = showInElement($formContainer[0]);
    const response = await ajaxFetcher.get(config.apiFormCancelEndpoint, null, {});

    if (response.success) {
      if (response.initialFormHtml) {
        $form.replaceWith(response.initialFormHtml);
        mainFormHandler();
      }
      if (response.message) {
        createAndShowToast(response.message, 'success');
      }
    } else {
      createAndShowToast(response.message || 'Error cancelling update', 'error');
    }
  } catch (error) {
    loggerError('[ERROR] Form Name - Error cancelling update:', error);
    createAndShowToast('Error cancelling update. Please try again.', 'error');
  } finally {
    if (loader) {
      hideInElement(loader);
    }
    checkNotifications();
  }
};

/**
 * Confirm form update with verification code
 */
const confirmFormUpdate = async formData => {
  let loader = null;
  const $formContainer = $('#form-name-form-container');
  const $form = $formContainer.find('#form-name-form');

  logger('[DEBUG] Form Name - Confirm update started');

  try {
    loader = showInElement($formContainer[0]);
    const response = await ajaxFetcher.form(config.apiFormConfirmEndpoint, formData);

    if (response.success) {
      createAndShowToast(response.message || 'Updated successfully', 'success');
      
      if (response.successFormHtml) {
        $form.replaceWith(response.successFormHtml);
      } else if (response.initialFormHtml) {
        $form.replaceWith(response.initialFormHtml);
      }
      
      checkNotifications();
      mainFormHandler();
    } else {
      handleFormValidationErrors(response, $form);
      createAndShowToast(response.message || 'Error confirming update', 'error');

      const $verificationCodeField = $form.find('input[name="verification_code"]');
      if ($verificationCodeField.length) {
        $verificationCodeField.val('').focus();
      }
    }
  } catch (error) {
    loggerError('[ERROR] Form Name - Error confirming update:', error);

    if (error.status === 422) {
      const response = error.responseJSON || {};
      handleFormValidationErrors(response, $form);
      createAndShowToast(response.message || 'Invalid verification code', 'error');
    } else {
      createAndShowToast('Error confirming update. Please try again.', 'error');
    }
  } finally {
    if (loader) {
      hideInElement(loader);
    }
    checkNotifications();
  }
};

/**
 * Handle initial form update request
 */
const initiateFormUpdate = async (form, formData) => {
  let loader = null;
  const $formContainer = $('#form-name-form-container');
  const $form = $formContainer.find('#form-name-form');

  logger('[DEBUG] Form Name - Initiate update started');

  try {
    loader = showInElement($formContainer[0]);
    const response = await ajaxFetcher.form(config.apiFormInitiateEndpoint, formData);

    if (response.success) {
      const confirmationFormHtml = response.confirmation_form_html;

      if (confirmationFormHtml) {
        $form.replaceWith(confirmationFormHtml);
        mainFormHandler();
        initCancelButton();
      } else {
        createAndShowToast('Error loading confirmation form', 'error');
      }
    } else {
      handleFormValidationErrors(response, $form);
      createAndShowToast(response.message || 'Error updating. Please try again.', 'error');
    }
  } catch (error) {
    loggerError('[ERROR] Form Name - Error initiating update:', error);

    if (error.status === 422 && error.responseJSON) {
      handleFormValidationErrors(error.responseJSON, $form);
      createAndShowToast(error.responseJSON.message || 'Validation errors occurred', 'error');
    } else {
      createAndShowToast('Error updating. Please try again.', 'error');
    }
  } finally {
    if (loader) {
      hideInElement(loader);
    }
    checkNotifications();
  }
};

/**
 * Initialize cancel button event listener
 */
const initCancelButton = () => {
  $('[data-action="cancel-form-name"]').off('click').on('click', function (e) {
    e.preventDefault();
    cancelFormUpdate();
  });
};

/**
 * Main form handler
 */
const mainFormHandler = () => {
  let loader = null;
  const $formContainer = $('#form-name-form-container');
  const $form = $formContainer.find('#form-name-form');

  logger('[DEBUG] Form Name - Form found', { debug: true });

  if ($form.length) {
    $form.off('submit');

    const isConfirmationStep = $form.find('input[name="verification_code"]').length > 0;

    let validator = null;
    try {
      validator = initFormValidation($form, isConfirmationStep);
      logger('[DEBUG] Form Name - Validator initialized', {
        validatorExists: !!validator,
        isConfirmationStep,
      }, { debug: true });
    } catch (error) {
      logger('[DEBUG] Form Name - Validator initialization failed', { error }, { debug: true });
      validator = null;
    }

    $form.on('submit', async function (e) {
      logger('[DEBUG] Form Name - Form submit triggered', { debug: true });
      e.preventDefault();

      if (validator) {
        const isValid = validator.form();
        if (!isValid) {
          return false;
        }
      }

      const formData = new FormData(this);

      if (isConfirmationStep) {
        await confirmFormUpdate(formData);
      } else {
        await initiateFormUpdate(this, formData);
      }
    });
  }

  initCancelButton();
};

/**
 * Initialize form handling
 */
const initForm = () => {
  logger('[DEBUG] Form Name - Initializing', { debug: true });
  mainFormHandler();
};

export { mainFormHandler, initForm };
```

### Шаг 4: Селекторы кнопок отмены

Убедитесь, что каждая форма использует уникальный селектор:

```html
<button data-action="cancel-form-name">Cancel</button>
```

### Шаг 5: Backend поддержка

Убедитесь, что ваш контроллер поддерживает:
- `initiateFormUpdate()` - инициация процесса
- `confirmFormUpdate()` - подтверждение с кодом
- `cancelFormUpdate()` - отмена процесса

## Правила консистентности

### 1. Функциональная консистентность
- ✅ Единая логика валидации
- ✅ Консистентная обработка ошибок
- ✅ Стандартизированные состояния загрузки
- ✅ Унифицированные сообщения успеха/ошибки

### 2. Поведенческая консистентность
- ✅ Единая логика отмены операций
- ✅ Одинаковые подтверждения действий
- ✅ Консистентная навигация между формами
- ✅ Специфичные селекторы для избежания конфликтов

### 3. Структурная консистентность
- ✅ Контейнерный подход (`$formContainer` → `$form`)
- ✅ Разделение валидации в отдельные файлы
- ✅ Централизованные константы и методы
- ✅ Унифицированное логирование

## Debugging и тестирование

### Логирование
Все формы используют централизованную систему логирования:
```javascript
logger('[DEBUG] Form Name - Action description', data, { debug: true });
loggerError('[ERROR] Form Name - Error description:', error);
```

### Проверка инициализации
```javascript
// В консоли браузера
console.log('Form validation initialized:', !!validator);
console.log('Form found:', $form.length > 0);
```

### Типичные проблемы
1. **Конфликты селекторов** - используйте уникальные `data-action` атрибуты
2. **Отсутствие контейнера** - убедитесь что форма обернута в контейнер
3. **Валидация не работает** - проверьте что jQuery Validation загружен
4. **AJAX не работает** - убедитесь что `preventDefault()` вызывается

## Заключение

Данная система обеспечивает единообразие всех форм профиля и упрощает добавление новых форм в будущем. Следуйте установленным паттернам для поддержания консистентности.