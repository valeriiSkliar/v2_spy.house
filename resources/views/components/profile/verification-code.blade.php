@props([
    'method' => 'email',
    'name' => 'verification_code',
    'placeholder' => 'xxx  xxx',
    'showErrors' => true
])

@php
    $isAuthenticator = $method === 'authenticator';
    $labelKey = $isAuthenticator ? 'profile.authenticator_mode.authenticator' : 'profile.authenticator_mode.email';
    $iconPath = $isAuthenticator ? '/img/google-authenticator.svg' : '/img/email-code.svg';
    $textKey = $isAuthenticator ? 'profile.app' : 'profile.letter';
@endphp

<div class="form-item mb-20">
    <label class="d-block mb-15">
        {{ __('profile.code from the') }} 
        <span class="font-weight-500">{{ __($labelKey) }}</span> 
        {{ __($textKey) }}
    </label>

    <div class="form-code-authenticator">
        <img src="{{ $iconPath }}" alt="{{ __($labelKey) }}">
        <input 
            type="text" 
            name="{{ $name }}" 
            class="input-h-57" 
            placeholder="{{ $placeholder }}"
            maxlength="6"
            pattern="[0-9]{6}"
            autocomplete="off"
        >
    </div>

    @if($showErrors)
        @error($name)
            <span class="text-danger">{{ $message }}</span>
        @enderror
    @endif
</div>