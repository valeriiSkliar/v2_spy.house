document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('verify-account-form');
  const inputs = form.querySelectorAll('input[name="code[]"]');
  const errorContainer = document.createElement('div');
  errorContainer.className = 'alert alert-danger mt-3 d-none';
  form.appendChild(errorContainer);

  // Обработка вставки из буфера
  inputs[0].addEventListener('paste', e => {
    e.preventDefault();
    const pastedText = (e.clipboardData || window.clipboardData).getData('text');
    const digits = pastedText.match(/\d/g);

    if (digits && digits.length === 6) {
      inputs.forEach((input, index) => {
        input.value = digits[index];
        if (index < 5) inputs[index + 1].focus();
      });
    }
  });

  // Автопереход между полями
  inputs.forEach((input, index) => {
    input.addEventListener('input', e => {
      if (e.target.value.length === 1) {
        if (index < inputs.length - 1) {
          inputs[index + 1].focus();
        }
      }
    });

    input.addEventListener('keydown', e => {
      if (e.key === 'Backspace' && !e.target.value && index > 0) {
        inputs[index - 1].focus();
      }
    });
  });

  // Отправка формы
  form.addEventListener('submit', async e => {
    e.preventDefault();

    const submitButton = form.querySelector('button[type="submit"]');
    submitButton.disabled = true;

    try {
      const response = await fetch(form.action, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          Accept: 'application/json',
        },
        body: JSON.stringify({
          code: Array.from(inputs).map(input => input.value),
        }),
      });

      const data = await response.json();

      if (data.success) {
        window.location.href = data.redirect;
      } else {
        errorContainer.textContent = data.message;
        errorContainer.classList.remove('d-none');
        submitButton.disabled = false;
      }
    } catch (error) {
      errorContainer.textContent = 'Произошла ошибка при подтверждении аккаунта';
      errorContainer.classList.remove('d-none');
      submitButton.disabled = false;
    }
  });

  // Обработка повторной отправки кода
  const resendButton = document.querySelector('[data-action="resend-verification"]');
  if (resendButton) {
    resendButton.addEventListener('click', async () => {
      if (resendButton.disabled) return;

      try {
        const response = await fetch('/api/resend-activation', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            Accept: 'application/json',
          },
        });

        const data = await response.json();

        if (data.success) {
          const unblockTime = new Date().getTime() + 300000; // 5 minutes
          resendButton.dataset.unblockTime = unblockTime;
          startResendTimer(resendButton);
        } else {
          errorContainer.textContent = data.message;
          errorContainer.classList.remove('d-none');
        }
      } catch (error) {
        errorContainer.textContent = 'Произошла ошибка при отправке кода';
        errorContainer.classList.remove('d-none');
      }
    });

    // Запуск таймера при загрузке, если есть время разблокировки
    if (resendButton.dataset.unblockTime) {
      startResendTimer(resendButton);
    }
  }
});

function startResendTimer(button) {
  const unblockTime = parseInt(button.dataset.unblockTime);
  const updateTimer = () => {
    const now = new Date().getTime();
    const timeLeft = unblockTime - now;

    if (timeLeft > 0) {
      button.disabled = true;
      const minutes = Math.floor(timeLeft / 60000);
      const seconds = Math.floor((timeLeft % 60000) / 1000);
      button.innerHTML = `<span class="icon-resend mr-2"></span>Отправить снова (${minutes}:${seconds
        .toString()
        .padStart(2, '0')})`;
      setTimeout(updateTimer, 1000);
    } else {
      button.disabled = false;
      button.innerHTML = '<span class="icon-resend mr-2"></span>Отправить снова';
    }
  };
  updateTimer();
}
