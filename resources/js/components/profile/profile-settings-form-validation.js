/**
 * Валидация формы настроек профиля
 */
document.addEventListener('DOMContentLoaded', function () {
  const personalSettingsForm = document.getElementById('personal-settings-form');

  if (personalSettingsForm) {
    personalSettingsForm.addEventListener('submit', function (event) {
      // Удаляем существующие сообщения об ошибках
      document.querySelectorAll('.validation-error').forEach(el => el.remove());

      // Проверяем форму
      const isValid = validatePersonalSettingsForm();

      if (!isValid) {
        event.preventDefault();
      }
    });
  }
});

/**
 * Валидирует форму настроек профиля
 * @returns {boolean} - возвращает true если форма валидна
 */
function validatePersonalSettingsForm() {
  let isValid = true;

  // Валидация логина
  const loginInput = document.querySelector('input[name="login"]');
  if (loginInput) {
    const login = loginInput.value.trim();

    if (!login) {
      showError(loginInput, 'Логин обязателен');
      isValid = false;
    } else if (login.length > 255) {
      showError(loginInput, 'Логин не должен превышать 255 символов');
      isValid = false;
    } else if (!/^[a-zA-Z0-9_]+$/.test(login)) {
      showError(
        loginInput,
        'Логин должен содержать только латинские буквы, цифры и символ подчеркивания'
      );
      isValid = false;
    }
  }

  // Валидация типа мессенджера
  const messengerTypeInput = document.querySelector('select[name="messenger_type"]');
  if (messengerTypeInput) {
    const messengerType = messengerTypeInput.value;

    if (!messengerType) {
      showError(messengerTypeInput, 'Тип мессенджера обязателен');
      isValid = false;
    } else if (!['whatsapp', 'viber', 'telegram'].includes(messengerType)) {
      showError(messengerTypeInput, 'Выбран недопустимый тип мессенджера');
      isValid = false;
    }
  }

  // Валидация контакта мессенджера
  const messengerContactInput = document.querySelector('input[name="messenger_contact"]');
  if (messengerContactInput) {
    const messengerContact = messengerContactInput.value.trim();
    const messengerType = messengerTypeInput ? messengerTypeInput.value : '';

    if (!messengerContact) {
      showError(messengerContactInput, 'Контакт мессенджера обязателен');
      isValid = false;
    } else {
      switch (messengerType) {
        case 'telegram':
          if (!validationTelegramLogin(messengerContact)) {
            showError(
              messengerContactInput,
              'Неверный формат имени пользователя Telegram. Должен начинаться с @ и содержать 5-32 символа (буквы, цифры, подчеркивание).'
            );
            isValid = false;
          }
          break;
        case 'viber':
          if (!validationViberIdentifier(messengerContact)) {
            showError(
              messengerContactInput,
              'Неверный формат номера телефона Viber. Должен содержать 10-15 цифр.'
            );
            isValid = false;
          }
          break;
        case 'whatsapp':
          if (!validationWhatsappIdentifier(messengerContact)) {
            showError(
              messengerContactInput,
              'Неверный формат номера телефона WhatsApp. Должен содержать 10-15 цифр.'
            );
            isValid = false;
          }
          break;
      }
    }
  }

  return isValid;
}

/**
 * Проверка формата логина Telegram
 */
function validationTelegramLogin(value) {
  return /^@[a-zA-Z0-9_]{4,31}$/.test(value);
}

/**
 * Проверка формата идентификатора Viber
 */
function validationViberIdentifier(value) {
  return /^\d{10,15}$/.test(value);
}

/**
 * Проверка формата идентификатора WhatsApp
 */
function validationWhatsappIdentifier(value) {
  return /^\d{10,15}$/.test(value);
}

/**
 * Показывает сообщение об ошибке под элементом формы
 */
function showError(inputElement, message) {
  const errorElement = document.createElement('div');
  errorElement.className = 'validation-error text-danger mt-1';
  errorElement.textContent = message;

  const parentElement = inputElement.parentElement;
  parentElement.appendChild(errorElement);

  // Добавляем класс к полю ввода для визуального отображения ошибки
  inputElement.classList.add('error');
}
