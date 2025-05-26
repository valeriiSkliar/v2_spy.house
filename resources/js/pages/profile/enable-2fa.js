export function initEnable2FA() {
  const regenerateButton = document.querySelector('.js-regenerate-2fa-secret');
  const secretElement = document.querySelector('.js-2fa-secret');
  const qrCodeContainer = document.querySelector('.js-qr-code-container');

  if (!regenerateButton) return;

  regenerateButton.addEventListener('click', async function () {
    try {
      // Показываем состояние загрузки
      regenerateButton.disabled = true;
      regenerateButton.textContent = 'Генерирую...';

      const response = await fetch('/profile/regenerate-2fa-secret', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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
      alert('Произошла ошибка при генерации нового кода. Попробуйте еще раз.');
    } finally {
      regenerateButton.disabled = false;
      if (regenerateButton.textContent === 'Генерирую...') {
        regenerateButton.textContent = 'Сгенерировать другой';
      }
    }
  });
}
