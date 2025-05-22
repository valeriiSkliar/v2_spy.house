import { createAndShowToast } from '../../utils';

/**
 * Enhanced validation for profile settings form
 */
document.addEventListener('DOMContentLoaded', function () {
  const personalSettingsForm = document.getElementById('personal-settings-form');
  const submitButton = personalSettingsForm?.querySelector('button[type="submit"]');
  let debounceTimeout = null;

  if (personalSettingsForm) {
    // Add validation to messenger select
    const messengerTypeSelect = document.getElementById('profile-messanger-select');
    const messengerContactInput = document.querySelector('input[name="messenger_contact"]');
    const messengerTypeInput = document.querySelector('input[name="messenger_type"]');

    // Debounced input validation
    const validateInputWithDebounce = input => {
      clearTimeout(debounceTimeout);
      debounceTimeout = setTimeout(() => {
        validateInput(input);
        updateFormValidity();
      }, 300);
    };

    // Validate a specific input
    const validateInput = input => {
      // Clear previous errors
      clearError(input);

      const name = input.getAttribute('name');

      switch (name) {
        case 'login':
          return validateLogin(input);
        case 'messenger_contact':
          return validateMessengerContact(input, messengerTypeInput?.value);
        case 'experience':
        case 'scope_of_activity':
          // These fields are selects and don't need client-side validation
          return true;
        default:
          return true;
      }
    };

    // Login validation
    const validateLogin = input => {
      const value = input.value.trim();

      if (!value) {
        createAndShowToast('Логин обязателен', 'error');
        return false;
      } else if (value.length > 255) {
        createAndShowToast('Логин не должен превышать 255 символов', 'error');
        return false;
      } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
        createAndShowToast(
          'Логин должен содержать только латинские буквы, цифры и символ подчеркивания',
          'error'
        );
        return false;
      }

      return true;
    };

    // Messenger contact validation
    const validateMessengerContact = (input, messengerType) => {
      const value = input.value.trim();

      if (!value) {
        createAndShowToast('Контакт мессенджера обязателен', 'error');
        return false;
      }

      // Update placeholder based on messenger type
      const placeholders = {
        telegram: '@username',
        viber: '+1 (999) 999-99-99',
        whatsapp: '+1 (999) 999-99-99',
      };

      input.placeholder = placeholders[messengerType] || placeholders['telegram'];

      switch (messengerType) {
        case 'telegram':
          if (!validationTelegramLogin(value)) {
            createAndShowToast(
              'Неверный формат имени пользователя Telegram. Должен начинаться с @ и содержать 5-32 символа (буквы, цифры, подчеркивание).',
              'error'
            );
            return false;
          }
          break;
        case 'viber':
          if (!validationViberIdentifier(value)) {
            createAndShowToast(
              'Неверный формат номера телефона Viber. Должен содержать 10-15 цифр.',
              'error'
            );
            return false;
          }
          break;
        case 'whatsapp':
          if (!validationWhatsappIdentifier(value)) {
            createAndShowToast(
              'Неверный формат номера телефона WhatsApp. Должен содержать 10-15 цифр.',
              'error'
            );
            return false;
          }
          break;
      }

      return true;
    };

    // Check if form is valid and update button state
    const updateFormValidity = () => {
      const formInputs = personalSettingsForm.querySelectorAll(
        'input:not([type="hidden"]), select'
      );
      let isValid = true;

      formInputs.forEach(input => {
        const errorElement = input.parentElement.querySelector('.validation-error');
        if (errorElement) {
          isValid = false;
        }
      });

      if (submitButton) {
        submitButton.disabled = !isValid;
      }

      return isValid;
    };

    // Add event listeners for input validation
    const formInputs = personalSettingsForm.querySelectorAll('input:not([type="hidden"]), select');
    formInputs.forEach(input => {
      input.addEventListener('input', function () {
        validateInputWithDebounce(this);
      });

      input.addEventListener('blur', function () {
        validateInput(this);
        updateFormValidity();
      });
    });

    // Add event listener for messenger type change
    if (messengerTypeSelect) {
      messengerTypeSelect.addEventListener('baseSelect:change', function (e) {
        const messengerType = e.detail.value;
        if (messengerContactInput) {
          // Get the current value after it's been updated by the messenger field component
          const currentValue = messengerContactInput.value.trim();
          
          // Only validate if there's a value (not required to have a value immediately after type change)
          if (currentValue) {
            validateMessengerContact(messengerContactInput, messengerType);
          } else {
            // Clear any previous errors if field is empty
            clearError(messengerContactInput);
          }
          
          updateFormValidity();
        }
      });
    }

    // Submit event handling
    personalSettingsForm.addEventListener('submit', function (event) {
      // Clear all errors
      document.querySelectorAll('.validation-error').forEach(el => el.remove());
      const formLevelError = document.querySelector('.form-level-error');
      if (formLevelError) {
        formLevelError.remove();
      }

      // Validate all inputs
      const formInputs = this.querySelectorAll('input:not([type="hidden"]), select');
      let isValid = true;

      formInputs.forEach(input => {
        if (!validateInput(input)) {
          isValid = false;
        }
      });

      if (!isValid) {
        event.preventDefault();

        // Scroll to first error
        const firstError = document.querySelector('.validation-error');
        if (firstError) {
          firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Show form-level error message
        const formError = document.createElement('div');
        formError.className = 'alert alert-danger mb-3 form-level-error';
        formError.textContent = 'Пожалуйста, исправьте ошибки в форме';

        // Insert at the top of the form
        this.insertBefore(formError, this.firstChild);

        // Auto-remove after 5 seconds
        setTimeout(() => {
          formError.remove();
        }, 5000);
      } else if (submitButton) {
        // Disable button and show loading state
        submitButton.disabled = true;
        const originalButtonText = submitButton.innerHTML;
        submitButton.innerHTML =
          '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Сохранение...';

        // Re-enable button after timeout (in case of network issues)
        setTimeout(() => {
          if (submitButton.disabled) {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
          }
        }, 10000);
      }
    });
  }
});

/**
 * Проверка формата логина Telegram
 * @param {string} value - Значение для проверки
 * @returns {boolean} - Валиден ли формат
 */
function validationTelegramLogin(value) {
  return /^@[a-zA-Z0-9_]{4,31}$/.test(value);
}

/**
 * Проверка формата идентификатора Viber
 * @param {string} value - Значение для проверки
 * @returns {boolean} - Валиден ли формат
 */
function validationViberIdentifier(value) {
  // Better phone number validation - matches digits only for now
  // In a production app, consider using libphonenumber-js for proper validation
  return /^\d{10,15}$/.test(value);
}

/**
 * Проверка формата идентификатора WhatsApp
 * @param {string} value - Значение для проверки
 * @returns {boolean} - Валиден ли формат
 */
function validationWhatsappIdentifier(value) {
  // Better phone number validation - matches digits only for now
  // In a production app, consider using libphonenumber-js for proper validation
  return /^\d{10,15}$/.test(value);
}

/**
 * Показывает сообщение об ошибке под элементом формы
 * @param {HTMLElement} inputElement - Элемент формы
 * @param {string} message - Сообщение об ошибке
 */
function showError(inputElement, message) {
  // Remove existing error for this element
  clearError(inputElement);

  const errorElement = document.createElement('div');
  errorElement.className = 'validation-error text-danger mt-1';
  errorElement.setAttribute('role', 'alert'); // For accessibility
  errorElement.textContent = message;

  const parentElement = inputElement.parentElement;
  parentElement.appendChild(errorElement);

  // Add error class to input with animation
  inputElement.classList.add('error');

  // Add shake animation
  inputElement.classList.add('shake-error');
  setTimeout(() => {
    inputElement.classList.remove('shake-error');
  }, 500);
}

/**
 * Удаляет сообщение об ошибке и классы ошибок
 * @param {HTMLElement} inputElement - Элемент формы
 */
function clearError(inputElement) {
  // Remove error class
  inputElement.classList.remove('error');

  // Remove error message
  const errorElement = inputElement.parentElement.querySelector('.validation-error');
  if (errorElement) {
    errorElement.remove();
  }
}
