<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle base select change for scope
        const scopeSelectOptions = document.querySelectorAll('.base-select_big .base-select__option[data-scope]');
        const scopeInput = document.querySelector('input[name="scope"]');
        const scopeSelectValue = document.querySelector('.base-select_big .base-select__value[data-scope]');

        if (scopeSelectOptions && scopeInput && scopeSelectValue) {
            scopeSelectOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const value = this.textContent.trim();
                    scopeInput.value = value;
                    scopeSelectValue.textContent = value;

                    // Update selected class
                    scopeSelectOptions.forEach(opt => opt.classList.remove('is-selected'));
                    this.classList.add('is-selected');
                });
            });
        }

        // Handle base select change for confirmation method
        const confirmationSelectOptions = document.querySelectorAll('.base-select_big .base-select__option[data-confirmation]');
        const confirmationMethodInput = document.querySelector('input[name="confirmation_method"]');
        const confirmationSelectValue = document.querySelector('.base-select_big .base-select__value[data-confirmation]');

        if (confirmationSelectOptions && confirmationMethodInput && confirmationSelectValue) {
            confirmationSelectOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const value = this.textContent.trim() === 'Authenticator app handful of hours' ? 'authenticator' : 'sms';
                    confirmationMethodInput.value = value;
                    confirmationSelectValue.textContent = this.textContent.trim();

                    // Update selected class
                    confirmationSelectOptions.forEach(opt => opt.classList.remove('is-selected'));
                    this.classList.add('is-selected');

                    // Show/hide the authenticator code field
                    const authenticatorSection = document.querySelector('.form-code-authenticator')?.closest('.row');
                    if (authenticatorSection) {
                        authenticatorSection.style.display = value === 'authenticator' ? 'flex' : 'none';
                    }
                });
            });
        }

        @if(session('api_token'))
            const apiToken = @json(session('api_token'));
            if (apiToken) {
                localStorage.setItem('api_token', apiToken);
                console.log('API token stored in localStorage.');
            }
        @endif
    });
</script>