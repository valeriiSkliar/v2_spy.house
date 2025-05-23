import { logger, loggerError } from '@/helpers/logger';
import { createAndShowToast } from '@/utils/uiHelpers';
import { hideInElement, showInElement } from '../../components/loader';

document.addEventListener('DOMContentLoaded', function () {
  logger('[DEBUG] 2FA Disable - DOM loaded');
  const disable2faSection = document.getElementById('disable-2fa-section');
  const disableBtn = document.getElementById('disableBtn');
  const warningSection = document.getElementById('warning-section');
  const formSection = document.getElementById('form-section');
  const warningText = document.getElementById('warning-text');
  const warningTitle = document.getElementById('warning-title');

  if (!disableBtn || !warningSection || !formSection || !warningText || !warningTitle) {
    loggerError('[ERROR] 2FA Disable - Required elements not found:', {
      disableBtn: !!disableBtn,
      warningSection: !!warningSection,
      formSection: !!formSection,
      warningText: !!warningText,
      warningTitle: !!warningTitle,
    });
    return;
  }

  logger('[DEBUG] 2FA Disable - Elements found successfully');

  // Обработка нажатия кнопки "Отключить 2FA"
  disableBtn.addEventListener('click', async function () {
    logger('[DEBUG] 2FA Disable - Disable button clicked');
    let loader = null;
    loader = showInElement(disable2faSection);
    try {
      // Показываем состояние загрузки
      disableBtn.disabled = true;
      // disableBtn.innerHTML = '<span class="font-weight-500">Загрузка...</span>';

      // Отправляем запрос на загрузку формы
      const response = await fetch('/profile/2fa/load-disable-form', {
        method: 'GET',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN':
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });

      const data = await response.json();
      logger('[DEBUG] 2FA Disable - Form load response:', data);

      if (data.success) {
        // Скрываем предупреждение и показываем форму
        warningSection.style.display = 'none';
        warningText.style.display = 'none';
        warningTitle.style.display = 'none';
        formSection.innerHTML = data.html;
        formSection.style.display = 'block';

        // Инициализируем обработчики для загруженной формы
        initializeForm();

        logger('[DEBUG] 2FA Disable - Form loaded successfully');
      } else {
        // Показываем ошибку
        createAndShowToast(data.message || 'Ошибка загрузки формы', 'error');

        if (data.redirect) {
          setTimeout(() => {
            window.location.href = data.redirect;
          }, 2000);
        }
      }
    } catch (error) {
      loggerError('[ERROR] 2FA Disable - Error loading form:', error);
      createAndShowToast('Ошибка загрузки формы', 'error');
    } finally {
      // Восстанавливаем состояние кнопки
      disableBtn.disabled = false;
      // disableBtn.innerHTML = '<span class="font-weight-500">Отключить 2FA</span>';
      if (loader) {
        setTimeout(() => {
          hideInElement(loader);
        }, 1500);
      }
    }
  });

  function initializeForm() {
    logger('[DEBUG] 2FA Disable - Initializing form handlers');

    const codeInputs = document.querySelectorAll('[data-code-input]');
    const verificationCodeField = document.getElementById('verificationCodeField');
    const form = document.getElementById('disableTwoFactorForm');
    const cancelBtn = document.getElementById('cancelBtn');

    if (!codeInputs.length || !verificationCodeField || !form) {
      loggerError('[ERROR] 2FA Disable - Form elements not found:', {
        codeInputs: !!codeInputs.length,
        verificationCodeField: !!verificationCodeField,
        form: !!form,
      });
      return;
    }

    // Обработка кнопки отмены
    if (cancelBtn) {
      cancelBtn.addEventListener('click', function () {
        // Возвращаемся к предупреждению
        formSection.style.display = 'none';
        warningSection.style.display = 'block';
        warningText.style.display = 'block';
        warningTitle.style.display = 'block';
      });
    }

    // Обработка ввода цифр и автоматического перехода между полями
    codeInputs.forEach((input, index) => {
      input.addEventListener('input', function (e) {
        // Позволяем вводить только цифры
        this.value = this.value.replace(/[^0-9]/g, '');

        // Если введена цифра, переходим к следующему полю
        if (this.value && index < codeInputs.length - 1) {
          codeInputs[index + 1].focus();
        }

        // Обновляем скрытое поле с полным кодом
        updateVerificationCode();
      });

      // Обработка клавиши Backspace для перехода к предыдущему полю
      input.addEventListener('keydown', function (e) {
        if (e.key === 'Backspace' && !this.value && index > 0) {
          codeInputs[index - 1].focus();
        }
      });
    });

    // Функция обновления скрытого поля с полным кодом
    function updateVerificationCode() {
      let code = '';
      codeInputs.forEach(input => {
        code += input.value;
      });
      verificationCodeField.value = code;
    }

    // Обработка отправки формы
    form.addEventListener('submit', async function (e) {
      // Предотвращаем стандартную отправку формы
      e.preventDefault();

      const disable2faSection = document.getElementById('disable-2fa-section');

      // Обновляем код перед отправкой
      updateVerificationCode();

      // Проверяем, что код полный (6 цифр)
      if (!verificationCodeField.value || verificationCodeField.value.length !== 6) {
        createAndShowToast('Пожалуйста, введите полный 6-значный код подтверждения', 'error');
        loggerError('[ERROR] 2FA Disable - Incomplete verification code');
        return false;
      }

      logger(
        '[DEBUG] 2FA Disable - Form submission with code, length:',
        verificationCodeField.value.length
      );
      let loader = null;
      const submitBtn = form.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      loader = showInElement(disable2faSection);
      try {
        // Показываем состояние загрузки
        submitBtn.disabled = true;

        // Отправляем AJAX запрос
        const formData = new FormData(form);

        const response = await fetch(form.action, {
          method: 'POST',
          body: formData,
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });

        const data = await response.json();
        logger('[DEBUG] 2FA Disable - Form submission response:', data);

        if (data.success) {
          // Показываем сообщение об успехе
          createAndShowToast(data.message || 'Двухфакторная аутентификация отключена', 'success');

          // Перенаправляем пользователя
          if (data.redirect) {
            setTimeout(() => {
              window.location.href = data.redirect;
            }, 1500);
          }
        } else {
          // Показываем ошибку
          createAndShowToast(data.message || 'Неверный код подтверждения', 'error');

          // Очищаем поля ввода
          codeInputs.forEach(input => {
            input.value = '';
          });
          verificationCodeField.value = '';

          // Фокусируемся на первом поле
          if (codeInputs[0]) {
            codeInputs[0].focus();
          }

          // Если есть редирект, выполняем его
          if (data.redirect) {
            setTimeout(() => {
              window.location.href = data.redirect;
            }, 2000);
          }
        }
      } catch (error) {
        loggerError('[ERROR] 2FA Disable - Error submitting form:', error);
        createAndShowToast('Ошибка отправки формы', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      } finally {
        // Восстанавливаем состояние кнопки
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = false;
        if (loader) {
          hideInElement(loader);
        }
      }
    });

    // Фокусируемся на первом поле ввода
    if (codeInputs[0]) {
      codeInputs[0].focus();
    }
  }
});
