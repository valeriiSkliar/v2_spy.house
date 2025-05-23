// 3. UI HELPERS - Вспомогательные функции для UI
// ============================================================================
import { hideInElement, showInElement } from '../../components/loader.js';
import { createAndShowToast } from '../../utils/uiHelpers.js';

const UIHelpers = {
  showFieldError(element, errors) {
    this.clearFieldError(element);

    if (errors.length === 0) return;

    // Используем класс 'error' вместо 'is-invalid'
    element.classList.add('error');
    element.classList.remove('valid');

    const formGroup = element.closest('.form-item, .form-group');
    if (formGroup) {
      const errorDiv = document.createElement('div');
      errorDiv.className = 'validation-error text-danger mt-1';
      errorDiv.textContent = errors[0].message;
      formGroup.appendChild(errorDiv);
    }
  },

  clearFieldError(element) {
    element.classList.remove('error');

    const formGroup = element.closest('.form-item, .form-group');
    if (formGroup) {
      const existingError = formGroup.querySelector('.validation-error');
      if (existingError) {
        existingError.remove();
      }
    }
  },

  markFieldAsValid(element) {
    element.classList.remove('error');
    element.classList.add('valid');
    this.clearFieldError(element);
  },

  markFieldAsInvalid(element, message) {
    element.classList.remove('valid');
    element.classList.add('error');

    if (message) {
      this.showFieldError(element, [{ message }]);
    }
  },

  showLoader(container) {
    // Используем loader из components/loader.js
    const loader = showInElement(container);
    return loader;
  },

  hideLoader(loader) {
    // Используем функцию из components/loader.js
    if (loader) {
      hideInElement(loader);
    }
  },

  showToast(message, type = 'info') {
    // Используем createAndShowToast из utils/uiHelpers.js
    createAndShowToast(message, type);
  },

  // Дополнительные методы для работы с формами
  disableForm(form) {
    const elements = form.querySelectorAll('input, select, textarea, button');
    elements.forEach(el => {
      el.disabled = true;
    });
  },

  enableForm(form) {
    const elements = form.querySelectorAll('input, select, textarea, button');
    elements.forEach(el => {
      el.disabled = false;
    });
  },

  clearAllErrors(form) {
    const errorElements = form.querySelectorAll('.error');
    errorElements.forEach(el => {
      this.clearFieldError(el);
    });
  },

  showFormErrors(form, errors) {
    // Очищаем предыдущие ошибки
    this.clearAllErrors(form);

    // Показываем новые ошибки
    Object.keys(errors).forEach(fieldName => {
      const field = form.querySelector(`[name="${fieldName}"]`);
      if (field && errors[fieldName]) {
        this.showFieldError(field, errors[fieldName]);
      }
    });
  },
};

export default UIHelpers;
