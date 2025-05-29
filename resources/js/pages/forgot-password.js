import axios from 'axios';
import loader, { hideInButton, showInButton } from '../components/loader';
import { createAndShowToast } from '../utils/uiHelpers';

document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('forgot-password-form');
  if (!form) return;

  const submitButton = form.querySelector('button[type="submit"]');
  const emailInput = form.querySelector('input[name="email"]');

  // Функция для очистки классов валидации
  function clearValidationClasses() {
    if (emailInput) {
      emailInput.classList.remove('error', 'valid');
    }
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

    // Проверяем заполненность email
    if (!emailInput.value.trim()) {
      setFieldError('email');
      createAndShowToast('Введите email адрес', 'error');
      return;
    }

    // Получаем значение reCAPTCHA
    const recaptchaResponse = grecaptcha.getResponse();
    if (!recaptchaResponse) {
      createAndShowToast('Пожалуйста, подтвердите, что вы не робот', 'error');
      return;
    }

    // Показываем лоадер в кнопке
    loader.show();
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
        // Устанавливаем email поле как валидное
        setFieldValid('email');

        // Показываем успешное сообщение
        createAndShowToast(
          response.data.message || 'Ссылка для сброса пароля отправлена на email',
          'success'
        );

        // Очищаем форму
        form.reset();
        grecaptcha.reset();

        // Скрываем лоадер через 2 секунды после успеха
        setTimeout(() => {
          loader.hide();
          hideInButton(submitButton);
        }, 2000);
      }
    } catch (error) {
      loader.hide();
      // Скрываем лоадер только при ошибке
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
          ['email', 'g-recaptcha-response'].forEach(fieldName => {
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
          });
        }

        // Сбрасываем reCAPTCHA при ошибке
        grecaptcha.reset();
      } else {
        // Общая ошибка сервера
        createAndShowToast('Произошла ошибка при отправке запроса. Попробуйте позже.', 'error');
        grecaptcha.reset();
      }
    }
  });

  // Добавляем обработчик для удаления класса ошибки при вводе в email
  if (emailInput) {
    emailInput.addEventListener('input', function () {
      if (this.classList.contains('error')) {
        this.classList.remove('error');
      }
    });
  }
});
