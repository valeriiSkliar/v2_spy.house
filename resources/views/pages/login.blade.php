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
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <div class="d-flex align-items-center justify-content-between mb-30">
                                <h1 class="mb-0 font-24">{{ __('Login') }}</h1>
                                <a href="{{ route('register') }}" class="btn _flex _black font-16 font-weight-bold">{{ __('Registration') }}</a>
                            </div>
                            
                            <div class="form-item mb-3">
                                <input type="email" name="email" class="input-h-57 @error('email') is-invalid @enderror" 
                                    placeholder="{{ __('Email') }}" value="{{ old('email') }}" required autofocus>
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="form-item mb-3">
                                <div class="form-password">
                                    <input type="password" name="password" class="input-h-57 @error('password') is-invalid @enderror" 
                                        data-pass="pass-1" placeholder="{{ __('Password') }}" required>
                                    <button type="button" class="btn-icon switch-password" data-pass-switch="pass-1">
                                        <span class="icon-view-off"></span>
                                        <span class="icon-view-on"></span>
                                    </button>
                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-item mb-25">
                                <label class="d-block mb-15">{{ __('Code from the') }} <span class="font-weight-500">{{ __('Authenticator') }}</span> {{ __('app') }}</label>
                                <div class="form-code-authenticator">
                                    <img src="{{ asset('img/google-authenticator.svg') }}" alt="" width="50">
                                    <input type="text" name="code" class="input-h-57 @error('code') is-invalid @enderror" 
                                        placeholder="xxx  xxx" required>
                                    @error('code')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-item mb-30">
                                <button type="submit" class="btn _flex _green _big w-100">{{ __('Log In') }}</button>
                            </div>

                            <div class="form-item mb-30">
                                <div class="form-text text-center">
                                    <a href="{{ route('password.request') }}" target="_blank">{{ __('Forgot your password?') }}</a>
                                </div>
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
                                <span class="icon-home mr-2 font-20"></span>{{ __('Go home') }}
                            </a>
                            <div class="lang-menu">
                                <div class="base-select base-select_login">
                                    <div class="base-select__trigger">
                                        <span class="base-select__value"><img src="{{ asset('img/flags/US.svg') }}" alt="">Eng</span>
                                        <span class="base-select__arrow"></span>
                                    </div>
                                    <ul class="base-select__dropdown" style="display: none;">
                                        <li class="base-select__option is-selected"><img src="{{ asset('img/flags/US.svg') }}" alt="">Eng</li>
                                        <li class="base-select__option"><img src="{{ asset('img/flags/UA.svg') }}" alt="">Uk</li>
                                        <li class="base-select__option"><img src="{{ asset('img/flags/ES.svg') }}" alt="">Esp</li>
                                    </ul>
                                </div>
                            </div>                        </div>
                        <div class="login-body__main">
                            <div class="login-body__logo">
                                <img src="{{ asset('img/logo-big.svg') }}" alt="{{ config('app.name') }}">
                            </div>
                            <h2>{{ __('Need help?') }}</h2>
                            <div class="write-telegram">
                                <div class="write-telegram__txt">{{ __('Write to our Telegram channel, we will help you with any question!') }}</div>
                                <div class="write-telegram__btn">
                                    <a href="{{ config('app.telegram_channel') }}" class="btn _flex" target="_blank">
                                        <span class="icon-telegram font-18 mr-2"></span>{{ __('Chat') }}
                                    </a>
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