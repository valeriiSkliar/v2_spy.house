@props(['id' => 'recaptcha'])

<div class="mb-3">
    <div id="{{ $id }}" class="g-recaptcha" data-sitekey="{{ config('captcha.sitekey') }}"></div>
    @error('g-recaptcha-response')
    <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

@push('scripts')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endpush