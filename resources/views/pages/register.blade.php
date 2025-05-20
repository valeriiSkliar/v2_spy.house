@extends('layouts.guest')

@section('content')
<div class="wrapper login-page">
    <header class="header">
        <div class="header__home">
            <a href="{{ route('home') }}" class="btn-icon _dark"><span class="icon-home"></span></a>
        </div>
        <div class="header__left">
            <a href="{{ route('home') }}" class="header__logo"><img src="{{ asset('img/logo.svg') }}" alt="" width="142" height="36"></a>
        </div>
        <div class="header__lang">
            <x-frontend.language-selector />
        </div>
    </header>
    <div class="login-page__content">
        <div class="login-page__right">
            <div class="login-form">
                <div class="login-form__content">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf
                        <div class="d-flex align-items-center justify-content-between mb-30">
                            <h1 class="mb-0 font-24">{{ __('Registration') }}</h1>
                            <a href="{{ route('login') }}" class="btn _flex _black font-16 font-weight-bold">{{ __('Log In') }}</a>
                        </div>
                        
                        <div class="form-item mb-3">
                            <input type="text" name="name" class="input-h-57 @error('name') error @enderror" placeholder="{{ __('Login') }}" value="{{ old('name') }}" required>
                            @error('name')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-item mb-3">
                            <input type="email" name="email" class="input-h-57 @error('email') error @enderror" placeholder="{{ __('Email') }}" value="{{ old('email') }}" required>
                            @error('email')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-item mb-3">
                            <div class="form-password">
                                <input type="password" name="password" class="input-h-57 @error('password') error @enderror" data-pass="pass-1" placeholder="{{ __('Password') }}" required>
                                <button type="button" class="btn-icon switch-password" data-pass-switch="pass-1">
                                    <span class="icon-view-off"></span>
                                    <span class="icon-view-on"></span>
                                </button>
                            </div>
                            @error('password')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-item mb-3">
                            <div class="form-password">
                                <input type="password" name="password_confirmation" class="input-h-57" data-pass="pass-2" placeholder="{{ __('Confirm your password') }}" required>
                                <button type="button" class="btn-icon switch-password" data-pass-switch="pass-2">
                                    <span class="icon-view-off"></span>
                                    <span class="icon-view-on"></span>
                                </button>
                            </div>
                        </div>

                        <div class="form-item mb-3">
                            <div class="form-messenger">
                                <input type="text" name="messenger_username" class="input-h-57 @error('messenger_username') error @enderror" value="{{ old('messenger_username') }}" placeholder="@UserName">
                                <div class="base-select">
                                    <div class="base-select__trigger">
                                        <span class="base-select__value">Telegram</span>
                                        <span class="base-select__arrow"></span>
                                    </div>
                                    <ul class="base-select__dropdown" style="display: none;">
                                        <li class="base-select__option is-selected">Telegram</li>
                                        <li class="base-select__option">Skype</li>
                                        <li class="base-select__option">WhatsApp</li>
                                        <li class="base-select__option">Viber</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="form-item mb-3">
                            <div class="base-select base-select_big is-empty">
                                <div class="base-select__trigger">
                                    <span class="base-select__value">{{ __('Your experience') }}</span>
                                    <span class="base-select__arrow"></span>
                                </div>
                                <ul class="base-select__dropdown" style="display: none;">
                                    <li class="base-select__option">Option 2</li>
                                    <li class="base-select__option">Option 3</li>
                                </ul>
                            </div>
                        </div>

                        <div class="form-item mb-3">
                            <div class="base-select base-select_big is-empty">
                                <div class="base-select__trigger">
                                    <span class="base-select__value">{{ __('Vertical') }}</span>
                                    <span class="base-select__arrow"></span>
                                </div>
                                <ul class="base-select__dropdown" style="display: none;">
                                    <li class="base-select__option">Option 2</li>
                                    <li class="base-select__option">Option 3</li>
                                </ul>
                            </div>
                        </div>

                        <div class="form-item mb-25 pt-2 d-flex justify-content-center">
                            <img src="{{ asset('img/re.png') }}" alt="">
                        </div>

                        <div class="form-item mb-30">
                            <button type="submit" class="btn _flex _green _big w-100">{{ __('Registration') }}</button>
                        </div>

                        <div class="form-item mb-30">
                            <div class="form-text text-center">{{ __('By clicking the button, you accept') }} <br>{{ __('the') }} <a href="{{ route('terms') }}" target="_blank">{{ __('Terms of Service') }}</a></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="login-page__left">
            <div class="login-body">
                <div class="login-body__content">
                    <div class="login-body__header">
                        <a href="{{ route('home') }}" class="btn _flex _dark2"><span class="icon-home mr-2 font-20"></span>{{ __('Go home') }}</a>
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
                        </div>
                    </div>
                    <div class="login-body__main">
                        <x-common.body-logo />
                        <h2>{{ __('Need help?') }}</h2>
                        <div class="write-telegram">
                            <div class="write-telegram__txt">{{ __('Write to our Telegram channel, we will help you with any question!') }}</div>
                            <div class="write-telegram__btn">
                                <a href="#" class="btn _flex" target="_blank"><span class="icon-telegram font-18 mr-2"></span>{{ __('Chat') }}</a>
                            </div>
                        </div>
                    </div>
                    <div class="login-body__footer">Copyright © {{ date('Y') }}. spy.house</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

