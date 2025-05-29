@extends('layouts.auth')

@section('form-content')
<form method="POST" action="{{ route('password.email') }}" id="forgot-password-form">
    @csrf
    <div class="d-flex align-items-center justify-content-between mb-30">
        <h1 class="mb-0 font-24">{{ __('auth.password_recovery.page_title') }}</h1>
    </div>

    <div class="form-item mb-3">
        <input type="email" name="email" class="input-h-57"
            placeholder="{{ __('auth.password_recovery.email_placeholder') }}" value="{{ old('email') }}" readonly
            autocomplete="off" onfocus="this.removeAttribute('readonly');" autofocus>
    </div>

    <!-- reCAPTCHA -->
    <div class="form-item mb-25 pt-2 d-flex justify-content-center">
        <div id="recaptcha-password-reset" class="g-recaptcha" data-sitekey="{{ config('captcha.sitekey') }}"></div>
    </div>

    <div class="form-item mb-30">
        <button type="submit" class="btn _flex _green _big w-100">{{
            __('auth.password_recovery.send_button') }}</button>
    </div>
</form>
@endsection

@push('page-scripts')
@vite('resources/js/pages/forgot-password.js')
@endpush