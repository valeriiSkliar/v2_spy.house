@extends('layouts.guest')

@section('content')
<div class="wrapper login-page">
    <header class="header">
        <div class="header__home">
            <a href="#" class="btn-icon _dark"><span class="icon-home"></span></a>
        </div>
        <div class="header__left">
            <a href="/" class="header__logo"><img src="img/logo.svg" alt="" width="142" height="36"></a>
        </div>
        <div class="header__lang">
            <x-frontend.language-selector />
        </div>
    </header>
    <div class="login-page__content">
        <div class="login-page__right">
            <div class="login-form">
                <div class="login-form__content">
                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf
                        <div class="d-flex align-items-center justify-content-between mb-30">
                            <h1 class="mb-0 font-24">{{ __('profile.password_recovery.page_title') }}</h1>
                        </div>

                        <!-- Success Message -->
                        @if (session('status'))
                        <div class="form-item mb-3">
                            <div class="alert alert-success text-center">
                                {{ session('status') }}
                            </div>
                        </div>
                        @endif

                        <div class="form-item mb-3">
                            <input type="email" name="email" class="input-h-57 @error('email') error @enderror"
                                placeholder="{{ __('profile.password_recovery.email_placeholder') }}"
                                value="{{ old('email') }}" readonly autocomplete="off"
                                onfocus="this.removeAttribute('readonly');" autofocus>
                            @error('email')
                            <span class="error-message">{{ $message }}</span>
                            @enderror
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
                                __('profile.password_recovery.send_button') }}</button>
                        </div>
                        {{-- <div class="form-item mb-30">
                            <div class="form-text text-center"><a href="{{ route('login') }}" target="_self">Вход</a>
                            </div>
                        </div> --}}
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
</div>

@endsection

@push('scripts')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endpush