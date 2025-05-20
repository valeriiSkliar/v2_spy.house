@props(['qrCodeInline'])

<div class="mb-30">
    <div class="step-2fa__qr">
        <img src="{{ $qrCodeInline }}" alt="{{ __('profile.2fa.qr_code_alt') }}">
    </div>
</div>
