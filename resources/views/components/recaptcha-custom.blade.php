@props(['id' => 'recaptcha-custom'])

<div class="form-item mb-3">
    <div id="{{ $id }}" class="g-recaptcha" data-sitekey="{{ config('captcha.sitekey') }}"></div>
    @error('g-recaptcha-response')
    <span class="error-message">{{ $message }}</span>
    @enderror
</div>

@push('scripts')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endpush