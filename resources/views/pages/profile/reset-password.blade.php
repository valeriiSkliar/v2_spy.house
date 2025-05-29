@extends('layouts.guest')

@section('content')
<div class="wrapper login-page">
    <header class="header">
        <div class="header__home">
            <a href="#" class="btn-icon _dark"><span class="icon-home"></span></a>
        </div>
        <div class="header__left">
            <a href="/" class="header__logo"><img src="{{ asset('img/logo.svg') }}" alt="" width="142" height="36"></a>
        </div>
        <div class="header__lang">
            <x-frontend.language-selector />
        </div>
    </header>
    <div class="login-page__content">
        <div class="login-page__right">
            <div class="login-form">
                <div class="login-form__content">
                    <form method="POST" action="{{ route('password.store') }}" id="reset-password-form">
                        @csrf
                        <!-- Password Reset Token -->
                        <input type="hidden" name="token" value="{{ $request->route('token') }}">

                        <div class="d-flex align-items-center justify-content-between mb-30">
                            <h1 class="mb-0 font-24">{{ __('profile.password_recovery.reset_title') }}</h1>
                        </div>

                        <!-- Email -->
                        <div class="form-item mb-3">
                            <input type="email" name="email" class="input-h-57 @error('email') error @enderror"
                                placeholder="{{ __('profile.email') }}" value="{{ old('email', $request->email) }}"
                                readonly>
                            @error('email')
                            <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="form-item mb-3">
                            <input type="password" name="password" id="password"
                                class="input-h-57 @error('password') error @enderror"
                                placeholder="{{ __('profile.password_recovery.new_password') }}">
                            <span class="error-message" id="password-error" style="display: none;">Пароль должен
                                содержать минимум 64 символа</span>
                            @error('password')
                            <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="form-item mb-3">
                            <input type="password" name="password_confirmation" id="password_confirmation"
                                class="input-h-57" placeholder="{{ __('profile.password_recovery.confirm_password') }}">
                            <span class="error-message" id="password-confirm-error" style="display: none;">Пароли не
                                совпадают</span>
                        </div>

                        <!-- reCAPTCHA -->
                        <div class="form-item mb-25 pt-2 d-flex justify-content-center">
                            <div id="recaptcha-password-reset" class="g-recaptcha"
                                data-sitekey="{{ config('captcha.sitekey') }}"></div>
                        </div>
                        @error('g-recaptcha-response')
                        <div class="form-item mb-3">
                            <span class="error-message">{{ $message }}</span>
                        </div>
                        @enderror

                        <div class="form-item mb-30">
                            <button type="submit" class="btn _flex _green _big w-100">{{
                                __('profile.password_recovery.reset_button') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="login-page__left">
            <div class="login-body">
                <div class="login-body__content">
                    <div class="login-body__header">
                        <a href="{{ route('home') }}" class="btn _flex _dark2">
                            <span class="icon-home mr-2 font-20"></span>{{ __('profile.go_home') }}
                        </a>
                        <div class="lang-menu">
                            <div class="base-select base-select_login">
                                <div class="base-select__trigger">
                                    <span class="base-select__value"><img src="{{ asset('img/flags/US.svg') }}"
                                            alt="">Eng</span>
                                    <span class="base-select__arrow"></span>
                                </div>
                                <ul class="base-select__dropdown" style="display: none;">
                                    <li class="base-select__option is-selected"><img
                                            src="{{ asset('img/flags/US.svg') }}" alt="">Eng</li>
                                    <li class="base-select__option"><img src="{{ asset('img/flags/UA.svg') }}" alt="">Uk
                                    </li>
                                    <li class="base-select__option"><img src="{{ asset('img/flags/ES.svg') }}"
                                            alt="">Esp</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="login-body__main">
                        <div class="login-body__logo">
                            <img src="{{ asset('img/logo-big.svg') }}" alt="{{ config('app.name') }}">
                        </div>
                        <h2>{{ __('Need help?') }}</h2>
                        <div class="write-telegram">
                            <div class="write-telegram__txt">{{ __('profile.write_telegram_channel') }}</div>
                            <div class="write-telegram__btn">
                                <a href="{{ config('app.telegram_channel') }}" class="btn _flex" target="_blank">
                                    <span class="icon-telegram font-18 mr-2"></span>{{ __('Chat') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="login-body__footer">Copyright © {{ date('Y') }}. {{ config('app.name') }}</div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('reset-password-form');
        const password = document.getElementById('password');
        const passwordConfirmation = document.getElementById('password_confirmation');
        const passwordError = document.getElementById('password-error');
        const confirmError = document.getElementById('password-confirm-error');

        // Валидация только при отправке формы
        form.addEventListener('submit', function(e) {
            let isValid = true;

// Проверка длины пароля
if (password.value.length < 8) { passwordError.style.display='block' ; password.classList.add('error'); isValid=false;
    } else { passwordError.style.display='none' ; password.classList.remove('error'); } // Проверка совпадения паролей
    if (password.value !==passwordConfirmation.value) { confirmError.style.display='block' ;
    passwordConfirmation.classList.add('error'); isValid=false; } else { confirmError.style.display='none' ;
    passwordConfirmation.classList.remove('error'); } if (!isValid) { e.preventDefault(); } }); }); 
</script>
@endpush