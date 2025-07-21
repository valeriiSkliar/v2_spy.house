@extends('layouts.auth')

@section('form-content')
<form method="POST" action="{{ route('password.store') }}" id="reset-password-form">
    @csrf
    <!-- Password Reset Token -->
    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <div class="d-flex align-items-center justify-content-between mb-30">
        <h1 class="mb-0 font-24">{{ __('auth.password_recovery.reset_title') }}</h1>
    </div>

    <!-- Email -->
    <div class="form-item mb-3">
        <input type="email" name="email" class="input-h-57" placeholder="{{ __('auth.email') }}"
            value="{{ old('email', $request->email) }}" readonly>
    </div>

    <!-- Password -->
    <div class="form-item mb-3">
        <div class="form-password">
            <input type="password" name="password" id="password" class="input-h-57" data-pass="pass-1"
                placeholder="{{ __('auth.password_recovery.new_password') }}">
            <button type="button" class="btn-icon switch-password" data-pass-switch="pass-1">
                <span class="icon-view-off"></span>
                <span class="icon-view-on"></span>
            </button>
        </div>
    </div>

    <!-- Confirm Password -->
    <div class="form-item mb-3">
        <div class="form-password">
            <input type="password" name="password_confirmation" id="password_confirmation" class="input-h-57"
                data-pass="pass-2" placeholder="{{ __('auth.password_recovery.confirm_password') }}">
            <button type="button" class="btn-icon switch-password" data-pass-switch="pass-1">
                <span class="icon-view-off"></span>
                <span class="icon-view-on"></span>
            </button>
        </div>
    </div>

    <!-- reCAPTCHA -->
    <div class="form-item mb-25 pt-2 d-flex justify-content-center">
        <div id="recaptcha-password-reset" class="g-recaptcha" data-sitekey="{{ config('captcha.sitekey') }}"></div>
    </div>

    <div class="form-item mb-30">
        <button type="submit" class="btn _flex _green _big w-100">{{
            __('auth.password_recovery.reset_button') }}</button>
    </div>
</form>
@endsection

@push('scripts')
@vite('resources/js/pages/reset-password.js')
@endpush