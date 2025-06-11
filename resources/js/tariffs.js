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
        const methodName = this.value;
        hiddenInput.value = methodName;
      });
    });
  }

  // Handle async payment form submission
  const paymentForm = document.getElementById('subscription-payment-form');
  if (paymentForm) {
    paymentForm.addEventListener('submit', async function (e) {
      e.preventDefault();

      const submitBtn = this.querySelector('button[type="submit"]');
      const originalText = submitBtn.textContent;

      // Disable button and show loading state
      submitBtn.disabled = true;
      submitBtn.textContent = 'Processing...';
      submitBtn.classList.add('loading');

      try {
        const formData = new FormData(this);
        const csrfToken = document
          .querySelector('meta[name="csrf-token"]')
          ?.getAttribute('content');

        const response = await fetch(this.action, {
          method: 'POST',
          body: formData,
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            Accept: 'application/json',
          },
        });

        const result = await response.json();

        if (response.ok && result.success) {
          // Success - redirect to payment URL
          console.log('Payment created successfully:', result);
          window.location.href = result.payment_url;
        } else {
          // Error - show message
          console.error('Payment creation failed:', result);
          showPaymentError(result.error || 'Произошла ошибка при создании платежа');
        }
      } catch (error) {
        console.error('Network error:', error);
        showPaymentError('Ошибка соединения. Попробуйте еще раз.');
      } finally {
        // Restore button state
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        submitBtn.classList.remove('loading');
      }
    });
  }

  // Function to show payment error
  function showPaymentError(message) {
    // Remove existing error messages
    const existingError = document.querySelector('.payment-error-message');
    if (existingError) {
      existingError.remove();
    }

    // Create and show error message
    const errorDiv = document.createElement('div');
    errorDiv.className = 'payment-error-message alert alert-danger mb-3';
    errorDiv.innerHTML = `
      <div class="d-flex align-items-center">
        <svg class="me-2" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
          <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
        </svg>
        <span>${message}</span>
      </div>
    `;

    // Insert error before form
    const paymentForm = document.getElementById('subscription-payment-form');
    if (paymentForm) {
      paymentForm.parentNode.insertBefore(errorDiv, paymentForm);

      // Auto-hide error after 5 seconds
      setTimeout(() => {
        if (errorDiv.parentNode) {
          errorDiv.remove();
        }
      }, 5000);
    }
  }
});
