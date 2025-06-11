// resources/js/tariffs.js
document.addEventListener('DOMContentLoaded', function () {
  // Toggle rate details
  const toggleRateBtn = document.querySelector('.js-toggle-rate');
  if (toggleRateBtn) {
    toggleRateBtn.addEventListener('click', function () {
      this.classList.toggle('show-all');
      document.querySelector('.rate-item-body._fixed').classList.toggle('show-all');

      const hiddenContent = document.querySelector('.rate-item-body__hidden');
      if (hiddenContent) {
        hiddenContent.style.display = this.classList.contains('show-all') ? 'block' : 'none';
      }

      if (this.classList.contains('show-all')) {
        this.querySelector('.btn__text').textContent = this.dataset.hide || 'Hide';
      } else {
        this.querySelector('.btn__text').textContent = this.dataset.show || 'Show all';
      }
    });
  }

  // Payment period tabs
  const paymentTabs = document.querySelectorAll('[data-tub][data-group="pay"]');
  if (paymentTabs.length > 0) {
    paymentTabs.forEach(tab => {
      tab.addEventListener('click', function () {
        const period = this.dataset.tub;
        const rateItem = this.closest('.rate-item');

        // Update active state on buttons in this rate item
        if (rateItem) {
          // Локальное переключение внутри карточки тарифа
          rateItem
            .querySelectorAll('[data-group="pay"]')
            .forEach(t => t.classList.remove('active'));
          rateItem.querySelectorAll(`[data-tub="${period}"][data-group="pay"]`).forEach(t => {
            t.classList.add('active');
          });

          // Update tariff select button URL for this specific card
          const selectBtn = rateItem.querySelector('.tariff-select-btn');
          if (selectBtn) {
            const tariffId = selectBtn.getAttribute('data-tariff-id');
            const baseUrl = window.location.origin + '/tariffs/payment/' + tariffId;
            const newUrl = baseUrl + '?billing_type=' + period;

            selectBtn.href = newUrl;
            selectBtn.setAttribute('data-billing-type', period);
          }

          // Update tariff renew button URL for this specific card
          const renewBtn = rateItem.querySelector('.tariff-renew-btn');
          if (renewBtn) {
            const tariffId = renewBtn.getAttribute('data-tariff-id');
            const baseUrl = window.location.origin + '/tariffs/payment/' + tariffId;
            const newUrl = baseUrl + '?billing_type=' + period;

            renewBtn.href = newUrl;
            renewBtn.setAttribute('data-billing-type', period);
          }
        } else {
          // Глобальное переключение (кнопки в header)
          // Обновляем активные состояния всех табов
          document
            .querySelectorAll('[data-group="pay"]')
            .forEach(t => t.classList.remove('active'));
          document.querySelectorAll(`[data-tub="${period}"][data-group="pay"]`).forEach(t => {
            t.classList.add('active');
          });

          // Обновляем все кнопки "Выбрать"
          const allSelectBtns = document.querySelectorAll('.tariff-select-btn');
          allSelectBtns.forEach(selectBtn => {
            const tariffId = selectBtn.getAttribute('data-tariff-id');
            const baseUrl = window.location.origin + '/tariffs/payment/' + tariffId;
            const newUrl = baseUrl + '?billing_type=' + period;

            selectBtn.href = newUrl;
            selectBtn.setAttribute('data-billing-type', period);
          });

          // Обновляем все кнопки "Продлить"
          const allRenewBtns = document.querySelectorAll('.tariff-renew-btn');
          allRenewBtns.forEach(renewBtn => {
            const tariffId = renewBtn.getAttribute('data-tariff-id');
            const baseUrl = window.location.origin + '/tariffs/payment/' + tariffId;
            const newUrl = baseUrl + '?billing_type=' + period;

            renewBtn.href = newUrl;
            renewBtn.setAttribute('data-billing-type', period);
          });
        }
      });
    });
  }

  // Handle payment method selection
  const paymentMethods = document.querySelectorAll('input[name="payment"]');
  const hiddenInput = document.getElementById('selected_payment_method');

  if (paymentMethods.length > 0 && hiddenInput) {
    paymentMethods.forEach(method => {
      method.addEventListener('change', function () {
        const methodName = this.closest('.payment-method').querySelector('span > span').textContent;
        hiddenInput.value = methodName.trim();
      });
    });
  }
});
