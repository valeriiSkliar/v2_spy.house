{{-- resources/views/modals/contact.blade.php --}}
<div class="modal-head">
    <h2 class="mb-2">Contacts</h2>
    <p>If you have any questions, you can write to any of our managers</p>
</div>

<div class="row">
    @foreach($managers as $manager)
    <div class="col-12 col-md-6 mb-10">
        <a href="https://t.me/{{ str_replace('@', '', $manager['telegram']) }}" target="_blank" class="manager">
            <span class="icon-telegram"></span>
            <span class="manager__thumb"><img src="{{ $manager['photo'] }}" alt=""></span>
            <span class="manager__content">
                <span class="manager__name">{{ $manager['name'] }}</span>
                <span class="manager__link">{{ $manager['telegram'] }}</span>
            </span>
        </a>
    </div>
    @endforeach
</div>

<div class="sep"></div>

<h3 class="mb-2">Or use the form below</h3>
<p class="mb-20">If you have any suggestions or wishes, please write to us.</p>

<form action="{{ route('contact.send') }}" method="POST" id="contact-form">
    @csrf
    <div class="row _offset20">
        <div class="col-12 col-md-6 mb-15">
            <input type="text" name="name" placeholder="Name" required>
        </div>
        <div class="col-12 col-md-6 mb-15">
            <input type="email" name="email" placeholder="Email" required>
        </div>
        <div class="col-12 mb-15">
            <textarea name="message" placeholder="Message" required></textarea>
        </div>
        <div class="col-6 mb-15">
            <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
        </div>
        <div class="col-12 mb-15">
            <button type="submit" class="btn _flex _green _medium min-120 w-mob-100">Send</button>
        </div>
    </div>
</form>

<script>
    // Initialize form submission
    document.getElementById('contact-form').addEventListener('submit', function(e) {
        e.preventDefault();

        const form = this;
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Sending...';

        fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Replace form with success message
                    form.innerHTML = `
                    <div class="alert alert-success">
                        <p>${data.message}</p>
                    </div>
                `;

                    // Close modal after delay
                    setTimeout(() => {
                        window.Modal.hide('contactModal');
                    }, 3000);
                } else {
                    // Show error message
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Send';

                    let errorMessage = data.message || 'Error sending message. Please try again.';

                    if (data.errors) {
                        errorMessage += '<ul>';
                        Object.values(data.errors).forEach(error => {
                            errorMessage += `<li>${error}</li>`;
                        });
                        errorMessage += '</ul>';
                    }

                    // Display error message
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'alert alert-danger mt-3';
                    errorDiv.innerHTML = errorMessage;

                    // Remove any existing error messages
                    const existingError = form.querySelector('.alert-danger');
                    if (existingError) {
                        existingError.remove();
                    }

                    form.appendChild(errorDiv);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Send';
            });
    });
</script>