const preLogin2FACheck = async function (e) {
  const form = $(this);
  e.preventDefault();

  if (!form.find('input[name="email"]').val()) {
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
        email: form.find('input[name="email"]').val(),
      }),
    });

    const data = await response.json();

    if (data.data.has_2fa) {
      form.find('#two-factor-container').html(data.data.html);
      form.find('#two-factor-container').show();
      const buttonText = data.data.button_text;
      form.find('button[type="submit"]').html(buttonText);
      form.find('button[type="submit"]').prop('disabled', false);

      form.find('#two-factor-container input').on('input', function () {
        form.off('submit', preLogin2FACheck);
        form.find('button[type="submit"]').prop('disabled', false);
      });
    } else {
      form.trigger('submit');
    }
  } catch (error) {
    form.find('button[type="submit"]').prop('disabled', false);
    console.error('Error checking 2FA:', error);
    form.trigger('submit');
  }
};

export function initLogin2FA() {
  const form = $('#login-form');
  if (!form.length) return;

  form.on('submit', preLogin2FACheck);
}
