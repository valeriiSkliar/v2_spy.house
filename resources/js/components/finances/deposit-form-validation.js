import { createAndShowToast } from '../../utils/uiHelpers';
import { hideInElement, showInElement } from '../loader';

/**
 * Синхронно-асинхронная валидация формы депозита
 * Сначала выполняется AJAX-валидация на сервере, затем стандартная отправка формы
 */
class DepositFormValidator {
  constructor(formSelector = null) {
    // Ищем форму по разным критериям
    this.form = formSelector ? document.querySelector(formSelector) : this.findDepositForm();
    this.container = document.getElementById('deposit-form-container');
    this.loader = null;
    this.isValidating = false;

    if (this.form) {
      // Помечаем форму как использующую новый валидатор
      this.form.setAttribute('data-has-new-validator', 'true');
      this.init();
    } else {
      console.warn('DepositFormValidator: Форма депозита не найдена');
    }
  }

  /**
   * Поиск формы депозита по различным критериям
   */
  findDepositForm() {
    // Пробуем разные селекторы
    const selectors = [
      '#deposit-form',
      'form[action*="/finances/deposit"]',
      'form[action*="finances.deposit"]',
      'form input[name="payment_method"] + form',
      'form:has(input[name="payment_method"])',
      '.max-w-400 form',
      'form.max-w-400',
    ];

    for (const selector of selectors) {
      try {
        const form = document.querySelector(selector);
        if (form) {
          return form;
        }
      } catch (e) {
        // Игнорируем ошибки селекторов (например, :has может не поддерживаться)
        continue;
      }
    }

    // Если не нашли через селекторы, ищем вручную
    const forms = document.querySelectorAll('form');
    for (const form of forms) {
      const action = form.getAttribute('action');
      if (action && action.includes('deposit')) {
        return form;
      }

      // Или проверяем наличие поля payment_method
      const paymentMethodInput = form.querySelector('input[name="payment_method"]');
      if (paymentMethodInput) {
        return form;
      }
    }

    return null;
  }

  init() {
    this.form.addEventListener('submit', this.handleSubmit.bind(this));
  }

  async handleSubmit(event) {
    event.preventDefault();

    // Предотвращаем двойную отправку
    if (this.isValidating) {
      return;
    }
    const container = this.container;

    try {
      this.isValidating = true;

      // Показываем состояние загрузки
      this.setLoadingState(container, true);

      // Выполняем асинхронную валидацию
      const isValid = await this.validateOnServer();

      if (isValid) {
        // Если валидация прошла успешно - продолжаем с обычной логикой обработки депозита
        await this.processDeposit();
      }
    } catch (error) {
      console.error('Ошибка валидации:', error);
      // Проверяем наличие контейнера тостов
      const toastContainer = document.querySelector('.toast-container');

      // Тестируем вызов createAndShowToast
      try {
        createAndShowToast('Произошла ошибка при проверке данных', 'error');
      } catch (toastError) {
        console.error('Error calling createAndShowToast:', toastError);
      }
    } finally {
      this.isValidating = false;
      const loader = this.setLoadingState(container, false);
      if (loader) {
        this.loader = loader;
      }
    }
  }

  async validateOnServer() {
    const formData = new FormData(this.form);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const response = await fetch('/finances/validate-deposit', {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    const result = await response.json();

    if (!response.ok) {
      // Отображаем ошибки валидации в тостах
      this.displayValidationErrors(result.errors || {});
      return false;
    }

    return result.success;
  }

  async processDeposit() {
    try {
      // Показываем состояние обработки депозита
      this.loader = this.setLoadingState(this.container, true);

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

      if (response.ok) {
        // Для депозитов обычно происходит редирект на платежную систему
        // Поэтому если получили OK ответ, но не JSON - скорее всего редирект
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
          const result = await response.json();

          if (result.success) {
            // Обработка успешного ответа
            if (result.redirect_url) {
              window.location.href = result.redirect_url;
            } else if (result.payment_url) {
              window.location.href = result.payment_url;
            } else {
              createAndShowToast('Депозит создан успешно', 'success');
            }
          } else {
            createAndShowToast(result.error || 'Произошла ошибка при создании депозита', 'error');
            if (this.loader) {
              hideInElement(this.loader);
            }
          }
        } else {
          // Если это не JSON, значит произошел редирект - отправляем форму нормально
          this.submitFormNormally();
        }
      } else {
        if (this.loader) {
          hideInElement(this.loader);
        }
        try {
          const result = await response.json();
          createAndShowToast(result.error || 'Произошла ошибка при создании депозита', 'error');
        } catch (e) {
          createAndShowToast('Произошла ошибка при создании депозита', 'error');
        }
      }
    } catch (error) {
      console.error('Network error:', error);
      // Для депозитов при ошибке сети все же отправляем форму обычным способом
      // так как возможно это проблема с AJAX, но форма сработает
      this.submitFormNormally();
    } finally {
      if (this.loader) {
        hideInElement(this.loader);
      }
    }
  }

  displayValidationErrors(errors) {
    // Показываем каждую ошибку в отдельном тосте
    Object.keys(errors).forEach(field => {
      const fieldErrors = Array.isArray(errors[field]) ? errors[field] : [errors[field]];

      fieldErrors.forEach(errorMessage => {
        try {
          // Сначала пытаемся использовать тосты
          createAndShowToast(errorMessage, 'error', 7000, false);
          if (this.loader) {
            hideInElement(this.loader);
          }
        } catch (error) {
          // Fallback: создаем простое уведомление
          this.showSimpleError(errorMessage);
          if (this.loader) {
            hideInElement(this.loader);
          }
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

  setLoadingState(element, isLoading) {
    if (isLoading) {
      showInElement(element, 'Проверка данных...');
    } else {
      if (this.loader) {
        hideInElement(this.loader);
      }
    }
  }

  submitFormNormally() {
    // Отправляем форму обычным способом
    const bypassInput = document.createElement('input');
    bypassInput.type = 'hidden';
    bypassInput.name = '_validation_passed';
    bypassInput.value = '1';
    this.form.appendChild(bypassInput);

    this.form.removeEventListener('submit', this.handleSubmit.bind(this));
    this.form.submit();
  }
}

export default DepositFormValidator;
