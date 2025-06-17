/**
 * Current Subscription Modal Component
 * Унифицированная система через глобальные модальные окна
 */

document.addEventListener('DOMContentLoaded', function () {
  // Очистка дублированных backdrop'ов при показе модального окна
  function clearDuplicateBackdrops() {
    const backdrops = document.querySelectorAll('.modal-backdrop');
    if (backdrops.length > 1) {
      // Удаляем все backdrop'ы кроме последнего
      for (let i = 0; i < backdrops.length - 1; i++) {
        backdrops[i].remove();
      }
    }
  }

  // Инициализация обработчиков для показа модального окна подписки
  document.addEventListener('click', function (e) {
    // Обработка ТОЛЬКО элементов с data-target (не data-toggle)
    const target = e.target.closest(
      '[data-target="#modal-current-subscription"]:not([data-toggle])'
    );

    if (target) {
      e.preventDefault();
      e.stopPropagation(); // Останавливаем всплытие для предотвращения двойного срабатывания

      // Используем глобальную систему модальных окон
      if (window.Modal) {
        window.Modal.showCurrentSubscription();

        // Очищаем дублированные backdrop'ы через небольшую задержку
        setTimeout(clearDuplicateBackdrops, 100);
      }
    }
  });

  // Слушаем событие показа модального окна для очистки backdrop'ов
  document.addEventListener('shown.bs.modal', function (e) {
    if (e.target && e.target.id === 'modal-current-subscription') {
      clearDuplicateBackdrops();
    }
  });

  console.log('Current Subscription Modal unified system initialized');
});

// Экспорт функции для внешнего использования
window.showCurrentSubscriptionModal = function () {
  if (window.Modal) {
    window.Modal.showCurrentSubscription();

    // Очищаем дублированные backdrop'ы
    setTimeout(() => {
      const backdrops = document.querySelectorAll('.modal-backdrop');
      if (backdrops.length > 1) {
        for (let i = 0; i < backdrops.length - 1; i++) {
          backdrops[i].remove();
        }
      }
    }, 100);
  }
};
