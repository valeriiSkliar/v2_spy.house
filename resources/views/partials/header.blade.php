<header class="header">
    <div class="header__burger">
        <button class="btn-icon _dark js-menu">
            <span class="menu-burger"><span></span><span></span><span></span><span></span></span>
        </button>
    </div>
    <div class="header__left">
        <a href="/" class="header__logo"><img src="/img/logo.svg" alt="" width="142" height="36"></a>
        @if(Route::is('blog.index') || Route::is('blog.category') || Route::is('blog.show'))
        <div class="header-blog font-roboto">{{ __('header.blog_title') }}</div>
        @if(Auth::check())
        <div class="header__btn">
            <a href="{{ route('creatives.index') }}" class="btn _flex _small _green2 ml-2">{{
                __('header.back_to_ads') }} <span class="icon-next font-16 ml-2"></span></a>
        </div>
        @endif
        @endif
    </div>
    <div class="header__right">
        @auth
        @php
        $user = auth()->user();
        $currentTariff = $user->currentTariff();
        @endphp


        @if(Auth::check() && $user->isTrialPeriod())
        <div class="header__tariff">
            <x-tariff-link :type="'trial'" data-toggle="modal" data-target="#modal-current-subscription"
                style="cursor: pointer;">
                {{ __('tariffs.trial') }}
            </x-tariff-link>
        </div>
        @elseif(Auth::check() && $user->email_verified_at)
        {{-- Current Subscription --}}
        <div class="header__tariff">
            <x-tariff-link :type="$currentTariff['css_class']" data-toggle="modal"
                data-target="#modal-current-subscription" style="cursor: pointer;">
                {{ $currentTariff['name'] }}
            </x-tariff-link>
        </div>
        @endif
        {{-- User Balance --}}
        @if($user->available_balance > 0)
        <div class="header__balance">
            <a href="#" class="user-balance">
                <span class="user-balance__currency">$</span>
                <span class="user-balance__val">{{ $user->getFormattedBalanceWithoutCurrency() }}</span>
            </a>
        </div>
        @endif


        @else
        {{-- Guest User --}}

        @if(Auth::check())
        <div class="header__tariff">
            <x-tariff-link>{{ __('tariffs.free') }}</x-tariff-link>
        </div>
        @endif
        @endauth

        <div class="header__lang">
            <x-frontend.language-selector />
        </div>
        @include('components.user-preview')
    </div>
</header>