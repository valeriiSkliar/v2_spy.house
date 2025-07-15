// Entry point for services page
/* @vite-ignore */
import './pages/services';

// Инициализация функциональности на детальной странице сервиса
document.addEventListener('DOMContentLoaded', function () {
  // Toggle read more/less
  const toggleButtons = document.querySelectorAll('.js-toggle-txt');
  toggleButtons.forEach(button => {
    button.addEventListener('click', function () {
      const container = this.closest('.hidden-txt');
      container.classList.toggle('is-open');

      if (container.classList.contains('is-open')) {
        this.textContent = this.dataset.hide || 'Скрыть';
      } else {
        this.textContent = this.dataset.show || 'Читать больше';
      }
    });
  });

  // Toggle promo code
  const promoCodeButtons = document.querySelectorAll('.js-toggle-code');
  promoCodeButtons.forEach(button => {
    button.addEventListener('click', function () {
      const container = this.closest('.single-market__code');
      container.classList.toggle('is-open');

      if (container.classList.contains('is-open')) {
        this.textContent = 'Скрыть промокод';
      } else {
        this.textContent = 'Показать промокод';
      }
    });
  });

  // Copy to clipboard
  const copyButtons = document.querySelectorAll('.btn-copy');
  copyButtons.forEach(button => {
    button.addEventListener('click', function () {
      const input = this.closest('.form-item__field').querySelector('input');
      input.select();
      document.execCommand('copy');

      // Show copied indicator
      this.classList.add('copied');
      setTimeout(() => {
        this.classList.remove('copied');
      }, 2000);
    });
  });
});

console.log('Services page scripts loaded');
