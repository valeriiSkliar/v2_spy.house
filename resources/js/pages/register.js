/**
 * Registration form handler
 */
import '../base-select.js'; // Импорт общей функциональности select'ов
import loader, { hideInButton, showInButton } from '../components/loader.js';
import { MessengerStateManager } from '../components/profile/messenger-state-manager.js';
import { createAndShowToast } from '../utils/uiHelpers.js';
import { ValidationMethods } from '../validation/validation-constants.js';

class RegistrationForm {
  constructor() {
    this.form = document.querySelector('form[action*="register"]');
    this.submitButton = this.form?.querySelector('button[type="submit"]');
    this.messengerManager = null;
    this.init();
  }

  init() {
    if (!this.form || !this.submitButton) {
      console.error('Registration form or submit button not found');
      return;
    }

    this.form.addEventListener('submit', this.handleSubmit.bind(this));
    this.initCustomSelects();
    this.initMessengerField();
  }

  initCustomSelects() {
    // base-select.js уже обрабатывает стандартные select'ы
    // Здесь добавляем только кастомную логику для удаления класса is-empty
    const customSelects = this.form.querySelectorAll('.base-select[data-target]');

    customSelects.forEach(select => {
      // Пропускаем элементы с кастомной обработкой (например, messenger select)
      if (select.classList.contains('js-custom-handling')) {
        return;
      }

      // Слушаем событие от base-select.js
      select.addEventListener('baseSelect:change', event => {
        // Remove empty class when selection is made
        select.classList.remove('is-empty');

        // Special handling for messenger type
        const targetName = select.getAttribute('data-target');
        if (targetName === 'messenger_type' && this.messengerManager) {
          this.messengerManager.updateFormInputs(event.detail.value);
        }
      });
    });
  }

  initMessengerField() {
    // Initialize messenger field functionality using unified approach
    const messengerSelect = this.form.querySelector('#register-messenger-select');
    if (!messengerSelect) return;

    const messengerContactInput = this.form.querySelector('input[name="messenger_contact"]');
    const messengerTypeInput = this.form.querySelector('input[name="messenger_type"]');

    if (!messengerContactInput || !messengerTypeInput) return;

    // Отключаем стандартную функциональность base-select для этого элемента
    messengerSelect.classList.add('js-custom-handling');

    // Create elements object compatible with MessengerStateManager
    const elements = {
      messengerType: $(messengerTypeInput),
      messengerContact: $(messengerContactInput),
      profileMessangerSelect: $(messengerSelect),
      profileMessangerSelectTrigger: $(messengerSelect.querySelector('.base-select__trigger')),
      profileMessangerSelectOptions: $(messengerSelect.querySelectorAll('.base-select__option')),
    };

    // Initialize the messenger state manager
    this.messengerManager = new MessengerStateManager(elements);

    // Удаляем любые существующие обработчики для этого элемента
    elements.profileMessangerSelectTrigger.off('click');
    elements.profileMessangerSelectOptions.off('click');

    // Toggle dropdown on trigger click
    elements.profileMessangerSelectTrigger.on('click', e => {
      e.preventDefault();
      e.stopPropagation();

      const $dropdown = $(messengerSelect).find('.base-select__dropdown');
      const $select = $(messengerSelect);
      const $trigger = elements.profileMessangerSelectTrigger;

      // Сначала закрываем все другие dropdown'ы
      $('.base-select')
        .not(messengerSelect)
        .each(function () {
          const $otherSelect = $(this);
          const $otherDropdown = $otherSelect.find('.base-select__dropdown');
          const $otherTrigger = $otherSelect.find('.base-select__trigger');

          $otherSelect.removeClass('is-open');
          $otherTrigger.removeClass('is-open');
          $otherDropdown.slideUp(200);
        });

      // Переключаем состояние текущего dropdown
      const isOpen = $select.hasClass('is-open');

      if (isOpen) {
        // Закрываем
        $select.removeClass('is-open');
        $trigger.removeClass('is-open');
        $dropdown.slideUp(200);
      } else {
        // Открываем
        $select.addClass('is-open');
        $trigger.addClass('is-open');
        $dropdown.slideDown(200);
      }
    });

    // Handle option selection
    elements.profileMessangerSelectOptions.on('click', e => {
      e.preventDefault();
      e.stopPropagation();
      const selectedOption = e.currentTarget;
      const selectedType = selectedOption.getAttribute('data-value');

      if (selectedType) {
        // Use the unified state manager to handle the selection
        this.messengerManager.updateSelectedMessenger(selectedType, selectedOption);

        // Close dropdown
        const $dropdown = $(messengerSelect).find('.base-select__dropdown');
        const $select = $(messengerSelect);
        const $trigger = elements.profileMessangerSelectTrigger;

        $select.removeClass('is-open');
        $trigger.removeClass('is-open');
        $dropdown.slideUp(200);
      }
    });

    // Handle input validation using unified validation methods
    elements.messengerContact.off('input').on('input', function () {
      const value = $(this).val();
      const type = elements.messengerType.val();

      // Clear previous classes
      $(this).removeClass('error valid');

      // Only add classes if there's a value to validate
      if (value.trim()) {
        if (ValidationMethods.validateMessengerContact(type, value)) {
          $(this).addClass('valid');
        } else {
          $(this).addClass('error');
        }
      }
    });

    // Close dropdown when clicking outside (убираем таймеры для избежания конфликтов)
    $(document)
      .off('click.registerMessengerDropdown')
      .on('click.registerMessengerDropdown', function (e) {
        // Проверяем что клик был не по нашему элементу
        if (!$(messengerSelect).is(e.target) && $(messengerSelect).has(e.target).length === 0) {
          const $dropdown = $(messengerSelect).find('.base-select__dropdown');
          const $select = $(messengerSelect);
          const $trigger = elements.profileMessangerSelectTrigger;

          if ($select.hasClass('is-open')) {
            $select.removeClass('is-open');
            $trigger.removeClass('is-open');
            $dropdown.slideUp(200);
          }
        }
      });
  }

  async handleSubmit(event) {
    event.preventDefault();

    // Clear previous validation states
    this.clearValidationStates();

    // Validate required fields before submission
    if (!this.validateRequiredFields()) {
      return;
    }

    // Show loader on submit button
    showInButton(this.submitButton);

    try {
      loader.show();

      const formData = new FormData(this.form);

      // Add CSRF token if not present
      if (!formData.has('_token')) {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) {
          formData.append('_token', token);
        }
      }

      const response = await fetch(this.form.action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json',
        },
      });

      const data = await response.json();

      if (response.status === 422 && data.status === 'error') {
        // Handle validation errors
        hideInButton(this.submitButton);
        this.handleValidationErrors(data.errors || {});
        return;
      }

      if (!response.ok) {
        throw new Error(data.message || 'Network response was not ok');
      }

      if (data.status === 'success') {
        this.handleSuccess(data);
      } else {
        throw new Error(data.message || 'Unknown error occurred');
      }
    } catch (error) {
      console.error('Registration error:', error);
      this.handleError(error);
    }
  }

  handleSuccess(data) {
    // Hide loader
    hideInButton(this.submitButton);

    // Show success toast
    // createAndShowToast(data.message || 'Регистрация прошла успешно!', 'success', 3000);

    // Redirect after a short delay
    setTimeout(() => {
      if (data.redirect_url) {
        window.location.href = data.redirect_url;
      } else {
        window.location.reload();
      }
    }, 2000);
  }

  handleError(error) {
    // Hide loader
    hideInButton(this.submitButton);
    loader.hide();

    // Reset reCAPTCHA
    if (window.grecaptcha) {
      window.grecaptcha.reset();
    }

    // Show general error toast
    createAndShowToast(error.message || 'Произошла ошибка при регистрации', 'error');
  }

  handleValidationErrors(errors) {
    // Hide loader
    loader.hide();

    // Reset reCAPTCHA on validation errors
    if (window.grecaptcha) {
      window.grecaptcha.reset();
    }

    Object.keys(errors).forEach(fieldName => {
      const input = this.form.querySelector(`[name="${fieldName}"]`);

      if (input) {
        // Add error class
        input.classList.add('error');
        input.classList.remove('valid');
      }

      // Show error messages as toasts
      const fieldErrors = Array.isArray(errors[fieldName])
        ? errors[fieldName]
        : [errors[fieldName]];
      fieldErrors.forEach(errorMessage => {
        createAndShowToast(errorMessage, 'error', 5000);
      });
    });
  }

  validateRequiredFields() {
    const requiredFields = [
      { name: 'login', label: 'Логин' },
      { name: 'email', label: 'Email' },
      { name: 'password', label: 'Пароль' },
      { name: 'password_confirmation', label: 'Подтверждение пароля' },
      { name: 'messenger_contact', label: 'Контакт мессенджера' },
      { name: 'messenger_type', label: 'Тип мессенджера' },
      { name: 'experience', label: 'Опыт' },
      { name: 'scope_of_activity', label: 'Сфера деятельности' },
    ];

    let isValid = true;

    requiredFields.forEach(field => {
      const input = this.form.querySelector(`[name="${field.name}"]`);
      if (!input || !input.value.trim()) {
        if (input) {
          input.classList.add('error');
        }
        createAndShowToast(`Поле "${field.label}" обязательно для заполнения`, 'error', 4000);
        isValid = false;
      }
    });

    // Additional validation for messenger contact using unified validation
    const messengerType = this.form.querySelector('input[name="messenger_type"]')?.value;
    const messengerContact = this.form.querySelector('input[name="messenger_contact"]')?.value;

    if (
      messengerType &&
      messengerContact &&
      !ValidationMethods.validateMessengerContact(messengerType, messengerContact)
    ) {
      const messengerInput = this.form.querySelector('input[name="messenger_contact"]');
      if (messengerInput) {
        messengerInput.classList.add('error');
      }
      createAndShowToast(ValidationMethods.getMessengerErrorMessage(messengerType), 'error', 4000);
      isValid = false;
    }

    // Validate reCAPTCHA
    const recaptchaResponse = window.grecaptcha?.getResponse();
    if (!recaptchaResponse) {
      createAndShowToast('Пожалуйста, подтвердите, что вы не робот', 'error', 4000);
      isValid = false;
    }

    return isValid;
  }

  clearValidationStates() {
    // Remove all validation classes from inputs
    const inputs = this.form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
      input.classList.remove('error', 'valid');
    });
  }
}

export const registerForm = () => {
  new RegistrationForm();
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', registerForm);

export default RegistrationForm;
