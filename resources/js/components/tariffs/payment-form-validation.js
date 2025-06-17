import { createAndShowToast } from '../../utils/uiHelpers';

/**
 * Синхронно-асинхронная валидация формы платежа
 * Сначала выполняется AJAX-валидация на сервере, затем стандартная отправка формы
 */
class PaymentFormValidator {
  constructor(formSelector = '#subscription-payment-form') {
    this.form = document.querySelector(formSelector);
    this.isValidating = false;

    console.log('PaymentFormValidator: Initializing...', this.form);
    console.log('PaymentFormValidator: createAndShowToast function:', createAndShowToast);

    if (this.form) {
      // Помечаем форму как использующую новый валидатор
      this.form.setAttribute('data-has-new-validator', 'true');
      this.init();
    }
  }

  init() {
    this.form.addEventListener('submit', this.handleSubmit.bind(this));
    console.log('PaymentFormValidator: Event listener added to form');
  }

  async handleSubmit(event) {
    event.preventDefault();
    console.log('PaymentFormValidator: Form submit intercepted');

    // Предотвращаем двойную отправку
    if (this.isValidating) {
      console.log('PaymentFormValidator: Already validating, skipping...');
      return;
    }

    const submitBtn = this.form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;

    try {
      this.isValidating = true;

      // Показываем состояние загрузки
      this.setLoadingState(submitBtn, true);

      // Выполняем асинхронную валидацию
      const isValid = await this.validateOnServer();
      console.log('PaymentFormValidator: Validation result:', isValid);

      if (isValid) {
        // Если валидация прошла успешно - продолжаем с обычной логикой обработки платежа
        await this.processPayment();
      }
    } catch (error) {
      console.error('Ошибка валидации:', error);
      // Проверяем наличие контейнера тостов
      const toastContainer = document.querySelector('.toast-container');
      console.log('Toast container found:', toastContainer);

      // Тестируем вызов createAndShowToast
      try {
        createAndShowToast('Произошла ошибка при проверке данных', 'error');
        console.log('createAndShowToast called successfully');
      } catch (toastError) {
        console.error('Error calling createAndShowToast:', toastError);
      }
    } finally {
      this.isValidating = false;
      this.setLoadingState(submitBtn, false, originalText);
    }
  }

  async validateOnServer() {
    console.log('PaymentFormValidator: Starting server validation...');

    const formData = new FormData(this.form);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    console.log('PaymentFormValidator: Form data:', Object.fromEntries(formData));
    console.log('PaymentFormValidator: CSRF token:', csrfToken);

    const response = await fetch('/tariffs/validate-payment', {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    console.log('PaymentFormValidator: Response status:', response.status, response.ok);

    const result = await response.json();
    console.log('PaymentFormValidator: Response data:', result);

    if (!response.ok) {
      // Отображаем ошибки валидации в тостах
      this.displayValidationErrors(result.errors || {});
      return false;
    }

    return result.success;
  }

  async processPayment() {
    const submitBtn = this.form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;

    try {
      // Показываем состояние обработки платежа
      submitBtn.textContent = 'Обработка платежа...';

      const formData = new FormData(this.form);
      // Добавляем маркер что валидация была пройдена
      formData.append('_validation_passed', '1');

      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

      const response = await fetch(this.form.action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          Accept: 'application/json',
        },
      });

      const result = await response.json();

      if (response.ok && result.success) {
        // Success - handle different payment methods
        console.log('Payment created successfully:', result);

        // For USER_BALANCE payments, redirect to success page
        if (result.redirect_url) {
          window.location.href = result.redirect_url;
        }
        // For external payments, redirect to payment gateway
        else if (result.payment_url) {
          window.location.href = result.payment_url;
        } else {
          createAndShowToast('Неожиданный ответ сервера', 'error');
        }
      } else {
        // Error - show message
        console.error('Payment creation failed:', result);
        createAndShowToast(result.error || 'Произошла ошибка при создании платежа', 'error');
      }
    } catch (error) {
      console.error('Network error:', error);
      createAndShowToast('Ошибка соединения. Попробуйте еще раз.', 'error');
    }
  }

  displayValidationErrors(errors) {
    console.log('displayValidationErrors called with:', errors);

    // Проверяем Bootstrap
    console.log('Bootstrap available:', typeof bootstrap !== 'undefined');
    console.log('Toast container:', document.querySelector('.toast-container'));

    // Очищаем предыдущие тосты
    // this.clearToasts();

    // Показываем каждую ошибку в отдельном тосте
    Object.keys(errors).forEach(field => {
      const fieldErrors = Array.isArray(errors[field]) ? errors[field] : [errors[field]];
      console.log(`Field ${field} errors:`, fieldErrors);

      fieldErrors.forEach(errorMessage => {
        console.log('Showing toast for error:', errorMessage);

        try {
          // Сначала пытаемся использовать тосты
          createAndShowToast(errorMessage, 'error', 7000, false);
          console.log('Toast created successfully');
        } catch (error) {
          console.error('Error creating toast:', error);

          // Fallback: создаем простое уведомление
          this.showSimpleError(errorMessage);
        }
      });
    });

    // Подсвечиваем поля с ошибками
    this.highlightErrorFields(errors);
  }

  /**
   * Простое отображение ошибки если тосты не работают
   */
  showSimpleError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'simple-error-message';
    errorDiv.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: #dc3545;
      color: white;
      padding: 15px 20px;
      border-radius: 5px;
      z-index: 9999;
      max-width: 400px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    `;
    errorDiv.textContent = message;

    document.body.appendChild(errorDiv);

    // Удаляем через 7 секунд
    setTimeout(() => {
      if (errorDiv.parentNode) {
        errorDiv.parentNode.removeChild(errorDiv);
      }
    }, 7000);
  }

  highlightErrorFields(errors) {
    // Сначала убираем все предыдущие ошибки
    this.form.querySelectorAll('.error').forEach(field => {
      field.classList.remove('error');
    });

    // Добавляем класс error к полям с ошибками
    Object.keys(errors).forEach(fieldName => {
      const field = this.form.querySelector(`[name="${fieldName}"]`);
      if (field) {
        field.classList.add('error');

        // Убираем класс error при следующем изменении поля
        const removeError = () => {
          field.classList.remove('error');
          field.removeEventListener('input', removeError);
          field.removeEventListener('change', removeError);
        };

        field.addEventListener('input', removeError);
        field.addEventListener('change', removeError);
      }
    });
  }

  clearToasts() {
    const toastContainer = document.querySelector('.toast-container');
    if (toastContainer) {
      const activeToasts = toastContainer.querySelectorAll('.toast');
      activeToasts.forEach(toast => {
        const toastInstance = bootstrap.Toast.getInstance(toast);
        if (toastInstance) {
          toastInstance.hide();
        }
        setTimeout(() => toast.remove(), 150);
      });
    }
  }

  setLoadingState(button, isLoading, originalText = 'Proceed to payment') {
    if (isLoading) {
      button.disabled = true;
      button.textContent = 'Проверка данных...';
      button.classList.add('loading');
    } else {
      button.disabled = false;
      button.textContent = originalText;
      button.classList.remove('loading');
    }
  }

  submitFormNormally() {
    // Этот метод больше не нужен, так как мы обрабатываем платеж напрямую
    // Оставлю его для обратной совместимости
    const bypassInput = document.createElement('input');
    bypassInput.type = 'hidden';
    bypassInput.name = '_validation_passed';
    bypassInput.value = '1';
    this.form.appendChild(bypassInput);

    this.form.removeEventListener('submit', this.handleSubmit.bind(this));
    this.form.submit();
  }
}

// Инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', () => {
  new PaymentFormValidator();
});

export default PaymentFormValidator;
