/**
 * Registration form handler
 */
import { hideInButton, showInButton } from '../components/loader.js';
import { createAndShowToast } from '../utils/uiHelpers.js';

class RegistrationForm {
  constructor() {
    this.form = document.querySelector('form[action*="register"]');
    this.submitButton = this.form?.querySelector('button[type="submit"]');
    this.init();
  }

  init() {
    if (!this.form || !this.submitButton) {
      console.error('Registration form or submit button not found');
      return;
    }

    this.form.addEventListener('submit', this.handleSubmit.bind(this));
    this.initCustomSelects();
  }

  initCustomSelects() {
    // Initialize custom selects with data-target attribute
    const customSelects = this.form.querySelectorAll('.base-select[data-target]');

    customSelects.forEach(select => {
      const targetName = select.getAttribute('data-target');
      const targetInput = this.form.querySelector(`input[name="${targetName}"]`);
      const options = select.querySelectorAll('.base-select__option[data-value]');

      if (!targetInput) {
        console.warn(`Target input not found for select: ${targetName}`);
        return;
      }

      options.forEach(option => {
        option.addEventListener('click', () => {
          const value = option.getAttribute('data-value');
          targetInput.value = value;

          // Remove empty class when selection is made
          select.classList.remove('is-empty');
        });
      });
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
    showInButton(this.submitButton, '_green');

    try {
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
    createAndShowToast(data.message || 'Регистрация прошла успешно!', 'success', 3000);

    // Redirect after a short delay
    setTimeout(() => {
      if (data.redirect_url) {
        window.location.href = data.redirect_url;
      } else {
        window.location.reload();
      }
    }, 1500);
  }

  handleError(error) {
    // Hide loader
    hideInButton(this.submitButton);

    // Show general error toast
    createAndShowToast(error.message || 'Произошла ошибка при регистрации', 'error');
  }

  handleValidationErrors(errors) {
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
      //   { name: 'email', label: 'Email' },
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
