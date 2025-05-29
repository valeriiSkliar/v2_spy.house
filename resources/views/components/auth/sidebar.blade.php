@props(['homeRoute' => 'home'])

<div class="login-page__left">
    <div class="login-body">
        <div class="login-body__content">
            <div class="login-body__header">
                <a href="{{ route($homeRoute) }}" class="btn _flex _dark2">
                    <span class="icon-home mr-2 font-20"></span>{{ __('auth.go_home') }}
                </a>
                <div class="lang-menu">
                    <div class="base-select base-select_login">
                        <div class="base-select__trigger">
                            <span class="base-select__value">
                                <img src="{{ asset('img/flags/US.svg') }}" alt="">Eng
                            </span>
                            <span class="base-select__arrow"></span>
                        </div>
                        <ul class="base-select__dropdown" style="display: none;">
                            <li class="base-select__option is-selected">
                                <img src="{{ asset('img/flags/US.svg') }}" alt="">Eng
                            </li>
                            <li class="base-select__option">
                                <img src="{{ asset('img/flags/UA.svg') }}" alt="">Uk
                            </li>
                            <li class="base-select__option">
                                <img src="{{ asset('img/flags/ES.svg') }}" alt="">Esp
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="login-body__main">
                <div class="login-body__logo">
                    <img src="{{ asset('img/logo-big.svg') }}" alt="{{ config('app.name') }}">
                </div>
                <h2>{{ __('auth.need_help') }}</h2>
                <div class="write-telegram">
                    <div class="write-telegram__txt">
                        {{ __('auth.write_telegram_channel') }}
                    </div>
                    <div class="write-telegram__btn">
                        <a href="{{ config('app.telegram_channel') }}" class="btn _flex" target="_blank">
                            <span class="icon-telegram font-18 mr-2"></span>{{ __('auth.chat') }}
                        </a>
                    </div>
                </div>
            </div>
            <div class="login-body__footer">
                Copyright © {{ date('Y') }}. {{ config('app.name') }}
            </div>
        </div>
    </div>
</div>