import { logger } from '../helpers/logger';

const preLogin2FACheck = async function (e) {
  const form = $(this);
  e.preventDefault();

  const email = form.find('input[name="email"]').val();
  const password = form.find('input[name="password"]').val();

  if (!email || !password) {
    form.submit();
    return;
  }

  form.find('button[type="submit"]').prop('disabled', true);

  try {
    const response = await fetch('/login/2fa/check', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({
        email: email,
        password: password,
      }),
    });

    const data = await response.json();

    if (data.data.has_2fa) {
      form.find('#two-factor-container').html(data.data.html);
      form.find('#two-factor-container').show();
      const buttonText = data.data.button_text;
      form.find('button[type="submit"]').html(buttonText);
      form.find('button[type="submit"]').prop('disabled', false);

      // Focus on the 2FA input field for better UX
      const twoFactorInput = form.find('#two-factor-container input');
      twoFactorInput.focus();

      // Once the user starts typing in the 2FA input, remove the preLogin2FACheck handler
      twoFactorInput.on('input', function () {
        form.off('submit', preLogin2FACheck);
        form.find('button[type="submit"]').prop('disabled', false);
      });
    } else {
      // Важно! Убираем обработчик preLogin2FACheck перед отправкой формы
      // для предотвращения циклического выполнения запросов
      form.off('submit', preLogin2FACheck);
      form.submit();
    }
  } catch (error) {
    logger('Error checking 2FA:', error);
    form.find('button[type="submit"]').prop('disabled', false);
    // Также убираем обработчик при ошибке
    form.off('submit', preLogin2FACheck);
    form.submit();
  }
};

export function initLogin2FA() {
  const form = $('#login-form');
  if (!form.length) return;

  // Check if the 2FA field is already visible (e.g., after form validation error)
  const twoFactorContainer = form.find('#two-factor-container');
  const is2FAVisible = twoFactorContainer.is(':visible');

  // If the 2FA field is already visible, don't attach the preLogin2FACheck handler
  if (is2FAVisible) {
    logger('2FA field is already visible, skipping preLogin2FACheck');
    // Make sure the form submits normally
    return;
  }

  // Otherwise, attach the preLogin2FACheck handler
  form.on('submit', preLogin2FACheck);
}
