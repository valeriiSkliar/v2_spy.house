// resources/js/tariffs.js
document.addEventListener('DOMContentLoaded', function() {
    // Toggle rate details
    const toggleRateBtn = document.querySelector('.js-toggle-rate');
    if (toggleRateBtn) {
        toggleRateBtn.addEventListener('click', function() {
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
            tab.addEventListener('click', function() {
                const period = this.dataset.tub;
                
                // Update active state on buttons
                paymentTabs.forEach(t => t.classList.remove('active'));
                document.querySelectorAll(`[data-tub="${period}"][data-group="pay"]`).forEach(t => {
                    if (t.tagName === 'BUTTON' || t.tagName === 'A') {
                        t.classList.add('active');
                    }
                });
                
                // Show correct price blocks
                document.querySelectorAll(`div[data-tub][data-group="pay"]`).forEach(block => {
                    if (block.dataset.tub === period) {
                        block.classList.add('active');
                    } else {
                        block.classList.remove('active');
                    }
                });
            });
        });
    }
    
    // Handle payment method selection
    const paymentMethods = document.querySelectorAll('input[name="payment"]');
    const hiddenInput = document.getElementById('selected_payment_method');
    
    if (paymentMethods.length > 0 && hiddenInput) {
        paymentMethods.forEach(method => {
            method.addEventListener('change', function() {
                const methodName = this.closest('.payment-method').querySelector('span > span').textContent;
                hiddenInput.value = methodName.trim();
            });
        });
    }
});