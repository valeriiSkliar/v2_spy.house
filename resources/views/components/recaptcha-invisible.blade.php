@props(['id' => 'recaptcha-invisible', 'callback' => 'onSubmit'])

<div id="{{ $id }}" class="g-recaptcha" data-sitekey="{{ config('captcha.sitekey') }}" data-callback="{{ $callback }}"
    data-size="invisible">
</div>

@error('g-recaptcha-response')
<div class="text-danger small mt-1">{{ $message }}</div>
@enderror

@push('scripts')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
    function {{ $callback }}(token) {
    document.getElementById("{{ $id }}").closest('form').submit();
}
</script>
@endpush