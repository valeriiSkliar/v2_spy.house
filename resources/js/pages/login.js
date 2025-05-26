import { hideInButton, showInButton } from '../components/loader';
import { logger } from '../helpers/logger';
import { createAndShowToast } from '../utils';

// Флаг для предотвращения множественных отправок
let isLoginInProgress = false;

// Асинхронная обработка формы входа
const handleLoginSubmit = async function (e) {
  e.preventDefault();

  // Предотвращаем множественные отправки
  if (isLoginInProgress) {
    logger('Login already in progress, ignoring additional submit');
    return;
  }

  const form = $(this);
  const submitButton = form.find('button[type="submit"]');
  const originalButtonText = submitButton.html();

  // Получаем данные формы
  const email = form.find('input[name="email"]').val();
  const password = form.find('input[name="password"]').val();
  const twoFactorCode = form.find('input[name="code"]').val();
  const rememberMe = form.find('input[name="remember"]').is(':checked');

  // Базовая валидация
  if (!email || !password) {
    createAndShowToast('Пожалуйста, заполните email и пароль', 'error');
    return;
  }

  // Устанавливаем флаг и показываем состояние загрузки
  isLoginInProgress = true;
  submitButton.prop('disabled', true);
  // submitButton.html('Вход...');

  try {
    showInButton(submitButton[0]);
    // Подготавливаем данные для отправки
    const formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);
    formData.append('remember', rememberMe ? '1' : '0');
    formData.append(
      '_token',
      document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    );

    if (twoFactorCode) {
      formData.append('code', twoFactorCode);
    }

    // Отправляем AJAX запрос
    const response = await fetch('/login/ajax', {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    logger('Response status:', response.status);

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    logger('Login response received:', data);

    if (data.success) {
      // Успешный вход
      logger('Login successful, preparing redirect to:', data.redirect);
      createAndShowToast(data.message || 'Успешный вход в систему', 'success');

      // Обновляем CSRF токен если он присутствует в ответе
      if (data.csrf_token) {
        document.querySelector('meta[name="csrf-token"]').setAttribute('content', data.csrf_token);
      }

      // Немедленный редирект без задержки
      const redirectUrl = data.redirect || '/profile/settings';
      logger('Redirecting to:', redirectUrl);
      window.location.href = redirectUrl;
    } else {
      // Ошибка входа
      logger('Login failed:', data);
      handleLoginError(data, form);
    }
  } catch (error) {
    logger('Login error:', error);

    // Проверяем специфичные ошибки
    if (error.message.includes('419') || error.message.includes('CSRF')) {
      createAndShowToast('Ошибка безопасности. Обновите страницу и попробуйте снова.', 'error');
      // При CSRF ошибке перезагружаем страницу через 2 секунды
      setTimeout(() => {
        window.location.reload();
      }, 2000);
    } else if (error.message.includes('422')) {
      createAndShowToast('Неверные данные для входа', 'error');
    } else {
      createAndShowToast('Произошла ошибка при входе. Попробуйте еще раз.', 'error');
    }
  } finally {
    // Сбрасываем флаг и восстанавливаем кнопку только если не было успешного входа
    if (!window.location.href.includes('/profile/settings')) {
      isLoginInProgress = false;
      submitButton.prop('disabled', false);
      // submitButton.html(originalButtonText);
      hideInButton(submitButton[0]);
    }
  }
};

// Обработка ошибок входа
const handleLoginError = (data, form) => {
  // Показываем основное сообщение об ошибке
  if (data.message) {
    createAndShowToast(data.message, 'error');
  }

  // Обрабатываем специфичные ошибки полей
  if (data.errors) {
    // Очищаем предыдущие ошибки
    form.find('.error-message').remove();
    form.find('.error').removeClass('error');

    // Показываем ошибки для каждого поля
    Object.keys(data.errors).forEach(field => {
      const input = form.find(`[name="${field}"]`);
      const errors = data.errors[field];

      if (input.length && errors.length) {
        input.addClass('error');

        // Добавляем сообщение об ошибке после поля
        // const errorMessage = $(`<div class="error-message text-danger mt-1">${errors[0]}</div>`);
        // input.closest('.form-group, .mb-3, .mb-20').append(errorMessage);
      }
    });
  }
};

// Предварительная проверка 2FA (сохраняем существующую логику)
const preLogin2FACheck = async function (e) {
  const form = $(this);
  e.preventDefault();
  const loginSubmitButton = form.find('#login-submit-button')[0];
  // Предотвращаем множественные запросы
  if (isLoginInProgress) {
    logger('2FA check already in progress, ignoring additional submit');
    return;
  }

  const email = form.find('input[name="email"]').val();
  const password = form.find('input[name="password"]').val();

  if (!email || !password) {
    // Если нет email или пароля, переходим к обычной обработке
    handleLoginSubmit.call(this, e);
    return;
  }

  const submitButton = form.find('button[type="submit"]');
  isLoginInProgress = true;
  submitButton.prop('disabled', true);

  try {
    if (loginSubmitButton) {
      showInButton(loginSubmitButton);
    }
    const response = await fetch('/login/2fa/check', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        email: email,
        password: password,
      }),
    });

    const data = await response.json();

    if (data.data.has_2fa) {
      // Показываем поле 2FA
      form.find('#two-factor-container').html(data.data.html);
      form.find('#two-factor-container').show();

      const buttonText = data.data.button_text;
      submitButton.html(buttonText);

      // Сбрасываем флаг и включаем кнопку
      isLoginInProgress = false;
      submitButton.prop('disabled', false);

      // Фокус на поле 2FA
      const twoFactorInput = form.find('#two-factor-container input');
      twoFactorInput.focus();

      // Переключаемся на обычную обработку после ввода 2FA
      form.off('submit', preLogin2FACheck);
      form.on('submit', handleLoginSubmit);

      twoFactorInput.on('input', function () {
        submitButton.prop('disabled', false);
      });
    } else {
      // Нет 2FA, переходим к обычной обработке
      form.off('submit', preLogin2FACheck);
      form.on('submit', handleLoginSubmit);
      // Сбрасываем флаг перед вызовом handleLoginSubmit
      isLoginInProgress = false;
      handleLoginSubmit.call(form[0], e);
    }
  } catch (error) {
    logger('Error checking 2FA:', error);
    // Сбрасываем флаг при ошибке
    isLoginInProgress = false;
    submitButton.prop('disabled', false);
    // При ошибке переходим к обычной обработке
    form.off('submit', preLogin2FACheck);
    form.on('submit', handleLoginSubmit);
    handleLoginSubmit.call(this, e);
  } finally {
    if (loginSubmitButton) {
      hideInButton(loginSubmitButton);
    }
  }
};

export function initLogin2FA() {
  const form = $('#login-form');
  if (!form.length) return;

  // Сбрасываем флаг при инициализации
  isLoginInProgress = false;

  // Убираем все предыдущие обработчики
  form.off('submit');

  // Проверяем, видно ли уже поле 2FA
  const twoFactorContainer = form.find('#two-factor-container');
  const is2FAVisible =
    twoFactorContainer.is(':visible') && twoFactorContainer.find('input').length > 0;

  if (is2FAVisible) {
    // 2FA уже видна, используем обычную обработку
    logger('2FA field is already visible, using normal login handler');
    form.on('submit', handleLoginSubmit);
  } else {
    // 2FA не видна, используем предварительную проверку
    form.on('submit', preLogin2FACheck);
  }
}
