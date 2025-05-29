import axios from 'axios';
import loader, { hideInButton, showInButton } from '../components/loader';
import { createAndShowToast } from '../utils/uiHelpers';

document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('reset-password-form');
  if (!form) return;

  const submitButton = form.querySelector('button[type="submit"]');
  const emailInput = form.querySelector('input[name="email"]');
  const passwordInput = form.querySelector('input[name="password"]');
  const passwordConfirmationInput = form.querySelector('input[name="password_confirmation"]');

  // Функция для очистки классов валидации
  function clearValidationClasses() {
    const inputs = [emailInput, passwordInput, passwordConfirmationInput];
    inputs.forEach(input => {
      if (input) {
        input.classList.remove('error', 'valid');
      }
    });
  }

  // Функция для установки класса ошибки на поле
  function setFieldError(fieldName) {
    const field = form.querySelector(`input[name="${fieldName}"]`);
    if (field) {
      field.classList.remove('valid');
      field.classList.add('error');
    }
  }

  // Функция для установки класса успеха на поле
  function setFieldValid(fieldName) {
    const field = form.querySelector(`input[name="${fieldName}"]`);
    if (field) {
      field.classList.remove('error');
      field.classList.add('valid');
    }
  }

  // Обработчик отправки формы
  form.addEventListener('submit', async function (e) {
    e.preventDefault();

    // Очищаем предыдущие классы валидации
    clearValidationClasses();

    // Получаем значение reCAPTCHA
    const recaptchaResponse = grecaptcha.getResponse();
    if (!recaptchaResponse) {
      createAndShowToast('Пожалуйста, подтвердите, что вы не робот', 'error');
      return;
    }
    loader.show();
    // Показываем лоадер в кнопке
    showInButton(submitButton);

    // Подготавливаем данные формы
    const formData = new FormData(form);

    try {
      const response = await axios.post(form.action, formData, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json',
        },
      });

      if (response.data.success) {
        // Устанавливаем все поля как валидные
        setFieldValid('email');
        setFieldValid('password');
        setFieldValid('password_confirmation');

        // Показываем успешное сообщение
        createAndShowToast(response.data.message || 'Пароль успешно изменен', 'success');

        // Редирект после небольшой задержки
        if (response.data.redirect) {
          setTimeout(() => {
            window.location.href = response.data.redirect;
          }, 1500);
        }
      }
    } catch (error) {
      // Скрываем лоадер только при ошибке
      loader.hide();
      hideInButton(submitButton);

      if (error.response && error.response.status === 422) {
        const errors = error.response.data.errors;
        const message = error.response.data.message;

        // Показываем общее сообщение об ошибке
        if (message) {
          createAndShowToast(message, 'error');
        }

        // Обрабатываем ошибки для каждого поля
        if (errors) {
          // Проверяем каждое поле
          ['email', 'password', 'password_confirmation', 'g-recaptcha-response'].forEach(
            fieldName => {
              if (errors[fieldName]) {
                // Устанавливаем класс ошибки для поля
                if (fieldName !== 'g-recaptcha-response') {
                  setFieldError(fieldName);
                }
                // Показываем первую ошибку для поля в тосте
                createAndShowToast(errors[fieldName][0], 'error');
              } else if (fieldName !== 'g-recaptcha-response') {
                // Если ошибок нет, помечаем поле как валидное
                setFieldValid(fieldName);
              }
            }
          );
        }

        // Сбрасываем reCAPTCHA при ошибке
        grecaptcha.reset();
      } else {
        // Общая ошибка сервера
        createAndShowToast('Произошла ошибка при сбросе пароля. Попробуйте позже.', 'error');
        grecaptcha.reset();
      }
    }
  });

  // Добавляем обработчики для удаления классов ошибок при вводе
  [passwordInput, passwordConfirmationInput].forEach(input => {
    if (input) {
      input.addEventListener('input', function () {
        if (this.classList.contains('error')) {
          this.classList.remove('error');
        }
      });
    }
  });
});
