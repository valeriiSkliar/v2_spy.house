@extends('layouts.auth')

@section('form-content')
<form method="POST" action="{{ route('login') }}" id="login-form">
    @csrf
    <div class="d-flex align-items-center justify-content-between mb-30">
        <h1 class="mb-0 font-24">{{ __('auth.login') }}</h1>
        <a href="{{ route('register') }}" class="btn _flex _black font-16 font-weight-bold">{{
            __('auth.registration') }}</a>
    </div>

    <div class="form-item mb-3">
        <input type="email" name="email" class="input-h-57 @error('email') error @enderror"
            placeholder="{{ __('auth.email') }}" value="{{ old('email') }}" readonly autocomplete="off"
            onfocus="this.removeAttribute('readonly');" autofocus>
        {{-- @error('email')
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
        @enderror --}}
    </div>

    <div class="form-item mb-3">
        <div class="form-password">
            <input type="password" name="password" class="input-h-57 @error('password') error @enderror"
                data-pass="pass-1" placeholder="{{ __('auth.password') }}" readonly autocomplete="off"
                onfocus="this.removeAttribute('readonly');">
            <button type="button" class="btn-icon switch-password" data-pass-switch="pass-1">
                <span class="icon-view-off"></span>
                <span class="icon-view-on"></span>
            </button>
            {{-- @error('password')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
            @enderror --}}
        </div>
    </div>

    @php
    $code = old('code');
    @endphp

    <div id="two-factor-container" class="form-item mb-3" style="display: {{ $code ? 'block' : 'none' }};">
        @if ($code)
        <x-auth.login-2fa-confirmation :message="'asdasdada'" />
        @endif
        <!-- 2FA component will be inserted here -->
    </div>

    <!-- reCAPTCHA -->
    <x-recaptcha-custom />

    <div class="form-item mb-30">
        <button id="login-submit-button" type="submit" class="btn _flex _green _big w-100">{{
            __('auth.log_in') }}</button>
    </div>

    <div class="form-item mb-30">
        <div class="form-text text-center">
            <a href="{{ route('password.request') }}" target="_blank">{{ __('auth.forgot_password') }}</a>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endpush