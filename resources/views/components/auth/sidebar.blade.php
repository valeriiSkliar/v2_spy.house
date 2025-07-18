@props(['homeRoute' => 'home'])

<div class="login-page__left">
    <div class="login-body">
        <div class="login-body__content">
            <div class="login-body__header">
                <a href="{{ route($homeRoute) }}" class="btn _flex _dark2 pe-3">
                    <span class="icon-home mr-2 font-20"></span>{{ __('auth.go_home') }}
                </a>
                <x-frontend.language-selector />
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