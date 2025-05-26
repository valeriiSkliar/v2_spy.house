@props(['id' => 'recaptcha-custom'])

<div class="form-item mb-25 pt-2 d-flex justify-content-center">
    <div id="{{ $id }}" class="g-recaptcha" data-sitekey="{{ config('captcha.sitekey') }}"></div>
    @error('g-recaptcha-response')
    <div class="form-item mb-3">
        <span class="error-message">{{ $message }}</span>
    </div>
    @enderror
</div>

@push('scripts')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endpush