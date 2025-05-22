import { logger, loggerError } from '@/helpers/logger';
document.addEventListener('DOMContentLoaded', function () {
  logger('[DEBUG] 2FA Disable - DOM loaded');

  const codeInputs = document.querySelectorAll('[data-code-input]');
  const verificationCodeField = document.getElementById('verificationCodeField');
  const form = document.getElementById('disableTwoFactorForm');

  if (!codeInputs.length || !verificationCodeField || !form) {
    loggerError('[ERROR] 2FA Disable - Required elements not found:', {
      codeInputs: !!codeInputs.length,
      verificationCodeField: !!verificationCodeField,
      form: !!form,
    });
    return;
  }

  logger('[DEBUG] 2FA Disable - Form fields found successfully');

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
  form.addEventListener('submit', function (e) {
    // Предотвращаем стандартную отправку формы
    e.preventDefault();

    // Обновляем код перед отправкой
    updateVerificationCode();

    // Проверяем, что код полный (6 цифр)
    if (!verificationCodeField.value || verificationCodeField.value.length !== 6) {
      alert('Пожалуйста, введите полный 6-значный код подтверждения');
      loggerError('[ERROR] 2FA Disable - Incomplete verification code');
      return false;
    }

    logger(
      '[DEBUG] 2FA Disable - Form submission with code, length:',
      verificationCodeField.value.length
    );
    logger('[DEBUG] 2FA Disable - Form action:', form.action);
    logger('[DEBUG] 2FA Disable - Form method:', form.method);
    logger('[DEBUG] 2FA Disable - Full verification code:', verificationCodeField.value);

    // Логируем все данные формы
    const formData = new FormData(form);
    const formValues = {};
    for (let [key, value] of formData.entries()) {
      formValues[key] = value;
    }
    logger('[DEBUG] 2FA Disable - Form data:', formValues);

    // Отправляем форму только если код корректно заполнен
    if (verificationCodeField.value && verificationCodeField.value.length === 6) {
      logger('[DEBUG] 2FA Disable - Submitting form with code:', verificationCodeField.value);
      form.submit();
    }
  });
});
