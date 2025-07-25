import loader, { hideInButton, showInButton } from '../components/loader.js';
import { createAndShowToast } from '../utils/uiHelpers.js';

const STORAGE_KEY = 'resend_verification_disabled_until';

/**
 * Инициализация функционала страницы верификации email
 */
export function initVerifyEmailPage() {
  // Находим кнопку для повторной отправки
  const resendButton = document.querySelector('[data-action="resend-verification"]');
  const form = document.getElementById('verify-account-form');
  const inputs = form?.querySelectorAll('input[name="code[]"]');

  if (resendButton) {
    // Сбрасываем флаг обработки при загрузке страницы
    resendButton.dataset.processing = 'false';

    // Проверяем, заблокирована ли кнопка при загрузке страницы
    checkButtonState(resendButton);

    resendButton.addEventListener('click', handleResendVerification);

    // Обработка обновления страницы - очищаем флаг обработки
    window.addEventListener('beforeunload', () => {
      resendButton.dataset.processing = 'false';
    });
  }

  if (form && inputs) {
    initVerificationForm(form, inputs);
  }
}

/**
 * Инициализация формы верификации
 */
function initVerificationForm(form, inputs) {
  // Добавляем обработчики для каждого поля ввода
  inputs.forEach((input, index) => {
    // Разрешаем только цифры
    input.addEventListener('input', e => {
      const value = e.target.value.replace(/[^0-9]/g, '');
      e.target.value = value.slice(0, 1); // Берем только первую цифру

      if (e.target.value.length === 1) {
        // Переходим к следующему полю
        if (index < inputs.length - 1) {
          inputs[index + 1].focus();
        }
      }
    });

    // Обработка удаления
    input.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !e.target.value && index > 0) {
        inputs[index - 1].focus();
      }
    });

    // Обработка вставки
    input.addEventListener('paste', e => {
      e.preventDefault();
      const pastedData = e.clipboardData
        .getData('text')
        .replace(/[^0-9]/g, '')
        .split('');

      inputs.forEach((input, i) => {
        if (pastedData[i]) {
          input.value = pastedData[i];
        }
      });

      // Фокус на последнем незаполненном поле или последнем поле
      const lastEmptyInput =
        Array.from(inputs).find(input => !input.value) || inputs[inputs.length - 1];
      lastEmptyInput.focus();
    });
  });

  // Обработка отправки формы
  form.addEventListener('submit', async e => {
    e.preventDefault();

    // Проверяем что все поля заполнены цифрами
    const isComplete = Array.from(inputs).every(input => /^[0-9]$/.test(input.value));
    if (!isComplete) {
      createAndShowToast('Пожалуйста, введите 6-значный цифровой код', 'error');
      return;
    }

    const submitButton = form.querySelector('button[type="submit"]');
    showInButton(submitButton);
    loader.show();
    try {
      const code = Array.from(inputs).map(input => input.value);

      const response = await fetch(form.action, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN':
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          Accept: 'application/json',
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ code }),
      });

      const data = await response.json();

      if (response.ok && data.success) {
        // createAndShowToast(data.message || 'Аккаунт успешно подтвержден', 'success');
        window.location.href = data.redirect || '/tariffs';
      } else {
        loader.hide();
        createAndShowToast(data.message || 'Ошибка подтверждения кода', 'error');
        // Очищаем поля при ошибке
        inputs.forEach(input => (input.value = ''));
        inputs[0].focus();
      }
    } catch (error) {
      console.error('Error verifying account:', error);
      createAndShowToast('Произошла ошибка при подтверждении аккаунта', 'error');
    } finally {
      hideInButton(submitButton);
    }
  });
}

/**
 * Проверяет состояние кнопки на основе серверных данных и localStorage
 */
function checkButtonState(button) {
  // Приоритет: серверные данные > localStorage
  const serverUnblockTime = button.dataset.unblockTime;
  const serverTime = parseInt(button.dataset.serverTime) || Date.now();
  const localStorageUnblockTime = localStorage.getItem(STORAGE_KEY);

  let unblockTime = null;
  let source = null;

  // Определяем какой источник использовать
  if (serverUnblockTime) {
    unblockTime = parseInt(serverUnblockTime);
    source = 'server';
  } else if (localStorageUnblockTime) {
    unblockTime = parseInt(localStorageUnblockTime);
    source = 'localStorage';
  }

  if (unblockTime) {
    // Используем серверное время как базу для расчетов
    const currentTime = source === 'server' ? serverTime : Date.now();
    const adjustedUnblockTime = source === 'server' ? unblockTime : unblockTime;

    if (currentTime < adjustedUnblockTime) {
      // Кнопка еще должна быть заблокирована
      disableButton(button);

      // Синхронизируем localStorage с серверными данными
      if (source === 'server') {
        localStorage.setItem(STORAGE_KEY, unblockTime.toString());
      }

      // Устанавливаем таймер для разблокировки
      const remainingTime = adjustedUnblockTime - Date.now();
      if (remainingTime > 0) {
        setTimeout(() => {
          enableButton(button);
          localStorage.removeItem(STORAGE_KEY);
        }, remainingTime);
      } else {
        enableButton(button);
        localStorage.removeItem(STORAGE_KEY);
      }
    } else {
      // Время истекло, разблокируем кнопку
      enableButton(button);
      localStorage.removeItem(STORAGE_KEY);
    }
  } else {
    // Нет ограничений, убеждаемся что кнопка активна
    enableButton(button);
  }
}

/**
 * Блокирует кнопку
 */
function disableButton(button) {
  console.log('Блокировка кнопки');
  button.disabled = true;
  //   button.classList.add('disabled');
}

/**
 * Разблокирует кнопку
 */
function enableButton(button) {
  console.log('Разблокировка кнопки');
  button.disabled = false;
  loader.hide();

  // Очищаем активный таймер если есть
  const timeoutId = button.dataset.timeoutId;
  if (timeoutId) {
    clearTimeout(parseInt(timeoutId));
    delete button.dataset.timeoutId;
  }

  //   button.classList.remove('disabled');
}

/**
 * Обработчик клика по кнопке повторной отправки ссылки
 */
async function handleResendVerification(event) {
  event.preventDefault();

  const button = event.target;

  // Проверяем, не заблокирована ли кнопка
  if (button.disabled) {
    return;
  }

  // Защита от быстрых повторных кликов
  if (button.dataset.processing === 'true') {
    return;
  }

  button.dataset.processing = 'true';

  // Показываем загрузчик в кнопке
  showInButton(button, '_green');

  try {
    const response = await fetch('/api/resend-activation', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN':
          document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        Accept: 'application/json',
      },
    });

    const data = await response.json();

    if (response.ok && data.success) {
      // Успешная отправка
      createAndShowToast(data.message || 'Ссылка отправлена на ваш email', 'success');

      // Скрываем загрузчик перед блокировкой
      hideInButton(button);

      // Блокируем кнопку используя время разблокировки от сервера
      if (data.unblock_time) {
        const unblockTime = parseInt(data.unblock_time);
        const currentTime = Date.now();
        const blockDuration = Math.max(0, unblockTime - currentTime);

        // Сохраняем время разблокировки в localStorage
        localStorage.setItem(STORAGE_KEY, unblockTime.toString());
        console.log('unblockTime', unblockTime);
        blockButtonTemporarily(button, blockDuration, currentTime);
      } else {
        // Fallback к старой логике
        console.log('Fallback', data);
        const blockDuration =
          data.block_duration || parseInt(button.dataset.blockDuration) || 300000;
        const serverTime = data.server_time || Date.now();
        console.log('blockDuration', blockDuration);
        blockButtonTemporarily(button, blockDuration, serverTime);
      }
    } else {
      // Обработка ошибок
      let message = getErrorMessage(response.status, data);
      let blockTime = null;

      if (response.status === 429) {
        // Превышен лимит
        message = data.message || 'Слишком частые запросы';

        // Если есть время разблокировки, используем его
        if (data.unblock_time) {
          const unblockTime = parseInt(data.unblock_time);
          const currentTime = Date.now();
          blockTime = Math.max(0, unblockTime - currentTime);

          // Сохраняем время разблокировки в localStorage для синхронизации
          localStorage.setItem(STORAGE_KEY, unblockTime.toString());
        } else if (data.retry_after) {
          blockTime = data.retry_after * 1000; // конвертируем в миллисекунды
        }
      }

      createAndShowToast(message, 'error');

      // Блокируем кнопку если нужно
      if (blockTime) {
        // Скрываем загрузчик перед блокировкой
        hideInButton(button);
        blockButtonTemporarily(button, blockTime);
      } else {
        // Если кнопка не блокируется, скрываем загрузчик
        hideInButton(button);
      }
    }
  } catch (error) {
    console.error('Error resending verification:', error);

    // Определяем тип сетевой ошибки
    let errorMessage = 'Произошла ошибка сети';
    if (error.name === 'TypeError' && error.message.includes('fetch')) {
      errorMessage = 'Нет соединения с сервером. Проверьте интернет-подключение';
    } else if (error.name === 'AbortError') {
      errorMessage = 'Запрос был отменен';
    }

    createAndShowToast(errorMessage, 'error');

    // В случае сетевой ошибки скрываем загрузчик
    hideInButton(button);
  } finally {
    // Убираем только флаг обработки, не трогаем состояние кнопки
    button.dataset.processing = 'false';
  }
}

/**
 * Временно блокирует кнопку на указанное количество миллисекунд
 */
function blockButtonTemporarily(button, milliseconds, serverTime = null) {
  // Используем серверное время если доступно, иначе клиентское
  const baseTime = serverTime || Date.now();
  const unblockTime = baseTime + milliseconds;

  // Сохраняем время разблокировки в localStorage
  localStorage.setItem(STORAGE_KEY, unblockTime.toString());

  // Блокируем кнопку
  disableButton(button);

  // Вычисляем точное время для таймера на основе клиентского времени
  const clientUnblockTime = Date.now() + milliseconds;
  const actualTimeout = Math.max(0, clientUnblockTime - Date.now());

  // Устанавливаем таймер для разблокировки
  const timeoutId = setTimeout(() => {
    // Дополнительная проверка времени при разблокировке
    const currentTime = Date.now();
    if (currentTime >= unblockTime - 1000) {
      // Допускаем погрешность в 1 секунду
      console.log('Разблокировка кнопки');
      enableButton(button);
      localStorage.removeItem(STORAGE_KEY);
    } else {
      // Если время еще не истекло, устанавливаем новый таймер
      const remainingTime = unblockTime - currentTime;
      setTimeout(() => {
        console.log('Разблокировка кнопки через таймер');
        enableButton(button);
        localStorage.removeItem(STORAGE_KEY);
      }, remainingTime);
    }
  }, actualTimeout);

  // Сохраняем ID таймера для возможной отмены
  button.dataset.timeoutId = timeoutId;
}

/**
 * Получает сообщение об ошибке в зависимости от статуса ответа
 */
function getErrorMessage(status, data) {
  if (data && data.message) {
    return data.message;
  }

  switch (status) {
    case 400:
      return 'Неверный запрос';
    case 401:
      return 'Необходима авторизация';
    case 403:
      return 'Доступ запрещен';
    case 422:
      return 'Ошибка валидации данных';
    case 429:
      return 'Слишком частые запросы';
    case 500:
      return 'Серверная ошибка, попробуйте позже';
    case 503:
      return 'Сервис временно недоступен';
    default:
      return 'Произошла ошибка при отправке ссылки';
  }
}

// Автоматическая инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', initVerifyEmailPage);
