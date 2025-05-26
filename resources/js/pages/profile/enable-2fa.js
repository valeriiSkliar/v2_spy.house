import { hideInElement, showInElement } from '../../components/loader';
import { createAndShowToast } from '../../utils';

export async function regenerate2faSecret() {
  const regenerateButton = document.querySelector('.js-regenerate-2fa-secret');
  const twoFactorContainer = document.querySelector('#two-factor-container');
  if (!regenerateButton) return;
  const secretElement = document.querySelector('.js-2fa-secret');
  const qrCodeContainer = document.querySelector('.js-qr-code-container');
  let loader = null;
  try {
    // Показываем состояние загрузки
    loader = showInElement(twoFactorContainer);
    regenerateButton.disabled = true;
    // regenerateButton.textContent = 'Генерирую...';

    const response = await fetch('/profile/regenerate-2fa-secret', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    const data = await response.json();

    if (data.success) {
      // Обновляем секретный ключ
      if (secretElement) {
        secretElement.textContent = data.secret;
      }

      // Обновляем QR код
      if (qrCodeContainer && data.qrCode) {
        const imgElement = qrCodeContainer.querySelector('img');
        if (imgElement) {
          imgElement.src = data.qrCode;
        }
      }

      // Восстанавливаем кнопку
      regenerateButton.textContent = 'Сгенерировать другой';
    } else {
      throw new Error('Ошибка регенерации секрета');
    }
  } catch (error) {
    console.error('Ошибка при регенерации 2FA секрета:', error);
    createAndShowToast('Произошла ошибка при генерации нового кода. Попробуйте еще раз.', 'error');
  } finally {
    hideInElement(loader);
    regenerateButton.disabled = false;
    if (regenerateButton.textContent === 'Генерирую...') {
      regenerateButton.textContent = 'Сгенерировать другой';
    }
  }
}

// Переход на второй шаг
async function goToStep2() {
  const twoFactorContainer = document.querySelector('#two-factor-container');
  let loader = null;
  try {
    loader = showInElement(twoFactorContainer);
    const response = await fetch('/profile/connect-2fa-step2-content', {
      method: 'GET',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    const data = await response.json();

    if (data.success) {
      // Находим контейнер первого шага
      const step1Container = document.querySelector('.step-2fa');
      if (step1Container) {
        // Сохраняем HTML первого шага для возможности возврата
        window.step1Html = step1Container.outerHTML;

        // Заменяем контент
        step1Container.outerHTML = data.html;

        // Инициализируем обработчики для второго шага
        initStep2Handlers();
      }
    } else {
      throw new Error(data.message || 'Ошибка загрузки второго шага');
    }
  } catch (error) {
    console.error('Ошибка при переходе на второй шаг:', error);
    createAndShowToast(
      error.message || 'Произошла ошибка при переходе на второй шаг. Попробуйте еще раз.',
      'error'
    );
  } finally {
    hideInElement(loader);
  }
}

// Возврат на первый шаг
function goBackToStep1() {
  // Проверяем наличие сохраненного HTML до показа loader'а
  if (!window.step1Html) {
    createAndShowToast('Не удалось вернуться на первый шаг. Перезагрузите страницу.', 'error');
    return;
  }

  const step2Container = document.querySelector('.step-2fa');
  if (!step2Container) {
    createAndShowToast('Элемент формы не найден.', 'error');
    return;
  }

  const twoFactorContainer = document.querySelector('#two-factor-container');
  let loader = null;

  try {
    // Показываем loader только на время DOM операций
    loader = showInElement(twoFactorContainer);

    // Выполняем DOM операции в одном блоке для минимизации reflow
    step2Container.outerHTML = window.step1Html;

    // Скрываем loader сразу после DOM операций
    hideInElement(loader);
    loader = null;

    // Инициализируем обработчики после скрытия loader'а
    // Это не вызывает визуальных изменений, поэтому loader не нужен
    initStep1Handlers();
  } catch (error) {
    console.error('Ошибка при возврате на первый шаг:', error);
    createAndShowToast('Произошла ошибка при возврате на первый шаг. Попробуйте еще раз.', 'error');
  } finally {
    // Убеждаемся что loader скрыт в случае ошибки
    if (loader) {
      hideInElement(loader);
    }
  }
}

// Инициализация обработчиков для первого шага
function initStep1Handlers() {
  // Регенерация секрета
  const regenerateButton = document.querySelector('.js-regenerate-2fa-secret');
  if (regenerateButton) {
    regenerateButton.addEventListener('click', regenerate2faSecret);
  }

  // Кнопка "Далее"
  const nextButton = document.querySelector('a[href*="connect-2fa-step2"]');
  if (nextButton) {
    nextButton.addEventListener('click', function (e) {
      e.preventDefault();
      goToStep2();
    });
  }
}

// Инициализация обработчиков для второго шага
function initStep2Handlers() {
  // Кнопка "Назад"
  const backButton = document.querySelector('.js-back-to-step1');
  if (backButton) {
    backButton.addEventListener('click', goBackToStep1);
  }

  // Обработка формы подтверждения через AJAX
  const form = document.getElementById('twoFactorFormAjax');
  const otpInput = document.querySelector('input[name="verification_code"]');
  const submitButton = document.querySelector('.js-submit-2fa');

  if (form && otpInput && submitButton) {
    form.addEventListener('submit', async function (e) {
      e.preventDefault();

      // Проверяем, заполнено ли поле
      if (!otpInput.value || otpInput.value.length === 0) {
        showStep2Error('Пожалуйста, введите код подтверждения');
        return false;
      }

      // Скрываем предыдущие ошибки
      hideStep2Error();

      const twoFactorContainer = document.querySelector('#two-factor-container');
      let loader = null;

      try {
        // Показываем состояние загрузки
        loader = showInElement(twoFactorContainer);
        submitButton.disabled = true;
        const originalText = submitButton.textContent;
        submitButton.textContent = 'Проверяю...';

        const formData = new FormData(form);
        const response = await fetch(form.dataset.ajaxUrl, {
          method: 'POST',
          body: formData,
          headers: {
            'X-CSRF-TOKEN': document
              .querySelector('meta[name="csrf-token"]')
              .getAttribute('content'),
            'X-Requested-With': 'XMLHttpRequest',
          },
        });

        const data = await response.json();

        if (data.success) {
          // Успешное подтверждение - редирект
          if (data.redirect) {
            setTimeout(() => {
              window.location.href = data.redirect;
            }, 3000);
          }
        } else {
          // Показываем ошибку
          showStep2Error(data.message || 'Неверный код подтверждения');
        }
      } catch (error) {
        hideInElement(loader);
        submitButton.disabled = false;
        if (submitButton.textContent === 'Проверяю...') {
          submitButton.textContent =
            submitButton.getAttribute('data-original-text') || 'Подтвердить';
        }
        console.error('Ошибка при подтверждении 2FA:', error);
        createAndShowToast('Произошла ошибка при подтверждении. Попробуйте еще раз.', 'error');
      } finally {
      }
    });
  }
}

// Показ ошибки на втором шаге
function showStep2Error(message) {
  createAndShowToast(message, 'error');
}

// Скрытие ошибки на втором шаге
function hideStep2Error() {
  const errorContainer = document.getElementById('step2-errors');
  if (errorContainer) {
    errorContainer.classList.add('d-none');
  }
}

export function initEnable2FA() {
  // Проверяем, на каком шаге мы находимся
  const step1Container = document.querySelector('.step-2fa');
  const step2Form =
    document.getElementById('twoFactorForm') || document.getElementById('twoFactorFormAjax');

  if (step1Container && !step2Form) {
    // Мы на первом шаге
    initStep1Handlers();
  } else if (step2Form) {
    // Мы на втором шаге (полная загрузка страницы)
    initStep2Handlers();
  }
}
