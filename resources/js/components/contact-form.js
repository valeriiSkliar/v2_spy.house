import { createAndShowToast } from '../utils/uiHelpers';
import { hideInElement, showInElement } from './loader';

/**
 * Компонент для обработки контактной формы с reCAPTCHA валидацией
 */
class ContactFormHandler {
  constructor(formSelector = '#modal-contacts form') {
    this.form = document.querySelector(formSelector);
    this.modal = document.getElementById('modal-contacts');
    this.isSubmitting = false;

    if (this.form) {
      this.init();
    } else {
      console.warn('Contact form not found:', formSelector);
    }
  }

  init() {
    // Сохраняем оригинальный текст кнопки
    this.storeOriginalButtonText();

    this.form.addEventListener('submit', this.handleSubmit.bind(this));

    // Очищаем форму и восстанавливаем состояние при открытии модального окна
    if (this.modal) {
      this.modal.addEventListener('show.bs.modal', this.onModalShow.bind(this));
    }
  }

  onModalShow() {
    // Сбрасываем состояние формы и кнопки
    this.resetForm();
    this.resetSubmitButton();
    this.isSubmitting = false;
  }

  async handleSubmit(event) {
    event.preventDefault();

    // Предотвращаем повторную отправку
    if (this.isSubmitting) {
      console.log('Contact form already submitting, ignoring duplicate submission');
      return;
    }

    const submitBtn = this.form.querySelector('button[type="submit"]');

    try {
      this.isSubmitting = true;

      // Валидация полей
      if (!this.validateForm()) {
        return;
      }

      // Валидация reCAPTCHA
      if (!this.validateRecaptcha()) {
        return;
      }

      // Блокируем кнопку и показываем состояние загрузки
      submitBtn.disabled = true;
      submitBtn.textContent = 'Отправка...';
      showInElement(submitBtn);

      // Отправляем форму
      await this.submitForm();
    } catch (error) {
      console.error('Contact form submission error:', error);
      createAndShowToast('Произошла ошибка при отправке сообщения', 'error');

      // Сбрасываем reCAPTCHA при ошибке
      this.resetRecaptcha();
    } finally {
      // Восстанавливаем кнопку с задержкой
      setTimeout(() => {
        this.isSubmitting = false;
        this.resetSubmitButton();
      }, 2000);
    }
  }

  validateForm() {
    const name = this.form.querySelector('input[name="name"]');
    const email = this.form.querySelector('input[name="email"]');
    const message = this.form.querySelector('textarea[name="message"]');

    // Валидация имени
    if (!name.value.trim() || name.value.trim().length < 2) {
      createAndShowToast('Пожалуйста, введите ваше имя (минимум 2 символа)', 'error');
      name.focus();
      return false;
    }

    // Валидация email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email.value.trim() || !emailRegex.test(email.value.trim())) {
      createAndShowToast('Пожалуйста, введите корректный email адрес', 'error');
      email.focus();
      return false;
    }

    // Валидация сообщения
    if (!message.value.trim() || message.value.trim().length < 10) {
      createAndShowToast('Пожалуйста, введите сообщение (минимум 10 символов)', 'error');
      message.focus();
      return false;
    }

    if (message.value.trim().length > 2000) {
      createAndShowToast('Сообщение слишком длинное (максимум 2000 символов)', 'error');
      message.focus();

      return false;
    }

    return true;
  }

  validateRecaptcha() {
    // Проверяем, включена ли reCAPTCHA в конфигурации
    const recaptchaEnabled = window.recaptchaConfig?.enabled !== false;

    if (!recaptchaEnabled) {
      return true; // Если reCAPTCHA отключена, пропускаем валидацию
    }

    // Проверяем наличие grecaptcha на странице
    if (!window.grecaptcha) {
      console.warn('reCAPTCHA not loaded, but validation required');
      createAndShowToast('Ошибка загрузки reCAPTCHA. Попробуйте обновить страницу.', 'error');
      return false;
    }

    const recaptchaResponse = window.grecaptcha.getResponse();
    if (!recaptchaResponse) {
      createAndShowToast('Пожалуйста, подтвердите, что вы не робот', 'error');
      return false;
    }

    return true;
  }

  async submitForm() {
    const formData = new FormData(this.form);
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (csrfToken) {
      formData.append('_token', csrfToken);
    }

    // Добавляем reCAPTCHA токен, если он есть
    if (window.grecaptcha) {
      const recaptchaResponse = window.grecaptcha.getResponse();
      if (recaptchaResponse) {
        formData.append('g-recaptcha-response', recaptchaResponse);
      }
    }

    const response = await fetch('/contact', {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    const data = await response.json();

    if (data.success) {
      createAndShowToast(data.message || 'Сообщение успешно отправлено!', 'success');
      this.resetForm();

      // Закрываем модальное окно через 2 секунды
      setTimeout(() => {
        if (this.modal) {
          const bsModal = bootstrap.Modal.getInstance(this.modal);
          if (bsModal) {
            bsModal.hide();
          }
        }
      }, 2000);
    } else {
      // Обрабатываем ошибки валидации
      if (data.errors) {
        const firstError = Object.values(data.errors)[0];
        if (Array.isArray(firstError)) {
          createAndShowToast(firstError[0], 'error');
        } else {
          createAndShowToast(firstError, 'error');
        }
      } else {
        createAndShowToast(data.message || 'Произошла ошибка при отправке сообщения', 'error');
      }
      this.resetRecaptcha();
    }
  }

  resetForm() {
    this.form.reset();
    this.resetRecaptcha();

    // Убираем визуальные индикаторы ошибок
    this.form.querySelectorAll('.error').forEach(el => {
      el.classList.remove('error');
    });
  }

  resetRecaptcha() {
    if (window.grecaptcha) {
      try {
        window.grecaptcha.reset();
      } catch (error) {
        console.warn('Failed to reset reCAPTCHA:', error);
      }
    }
  }

  resetSubmitButton() {
    const submitBtn = this.form.querySelector('button[type="submit"]');
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = submitBtn.getAttribute('data-original-text') || 'Отправить';

      // Убираем loader состояние
      hideInElement(submitBtn);

      // Восстанавливаем оригинальные классы
      submitBtn.classList.remove('loading');
    }
  }

  storeOriginalButtonText() {
    const submitBtn = this.form.querySelector('button[type="submit"]');
    if (submitBtn && !submitBtn.getAttribute('data-original-text')) {
      submitBtn.setAttribute('data-original-text', submitBtn.textContent);
    }
  }
}

// Автоматическая инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', function () {
  new ContactFormHandler();
});

export default ContactFormHandler;
