/**
 * Current Subscription Modal Component
 * Простой показ модального окна с информацией о подписке
 */

class CurrentSubscriptionModal {
  constructor() {
    this.modalElement = document.getElementById('modal-current-subscription');
    this.bindEvents();
    console.log('Current Subscription Modal initialized');
  }

  bindEvents() {
    // Привязка к триггерам
    document.querySelectorAll('[data-target="#modal-current-subscription"]').forEach(trigger => {
      trigger.addEventListener('click', e => {
        e.preventDefault();
        this.show();
      });
    });

    // События модального окна
    if (this.modalElement) {
      this.modalElement.addEventListener('shown.bs.modal', () => {
        console.log('Current subscription modal shown');
      });
    }
  }

  show() {
    if (this.modalElement && window.bootstrap) {
      const modal = new window.bootstrap.Modal(this.modalElement);
      modal.show();
    }
  }

  hide() {
    if (this.modalElement && window.bootstrap) {
      const modal = window.bootstrap.Modal.getInstance(this.modalElement);
      if (modal) modal.hide();
    }
  }
}

// Простая инициализация
document.addEventListener('DOMContentLoaded', () => {
  new CurrentSubscriptionModal();
});

export default CurrentSubscriptionModal;
