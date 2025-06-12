<header class="header">
    <div class="header__burger">
        <button class="btn-icon _dark js-menu">
            <span class="menu-burger"><span></span><span></span><span></span><span></span></span>
        </button>
    </div>
    <div class="header__left">
        <a href="/" class="header__logo"><img src="/img/logo.svg" alt="" width="142" height="36"></a>
    </div>
    <div class="header__right">
        @auth
        @php
        $user = auth()->user();
        $currentTariff = $user->currentTariff();
        @endphp

        {{-- User Balance --}}
        @if($user->available_balance > 0)
        <div class="header__balance">
            <span class="header__balance-label">Баланс:</span>
            <span class="header__balance-amount">{{ $user->getFormattedBalance() }}</span>
        </div>
        @endif

        {{-- Current Subscription --}}
        <div class="header__tariff">
            <x-tariff-link :type="$currentTariff['css_class']">
                {{ $currentTariff['name'] }}
            </x-tariff-link>
        </div>
        @else
        {{-- Guest User --}}
        <div class="header__tariff">
            <x-tariff-link>{{ __('tariffs.free') }}</x-tariff-link>
        </div>
        @endauth

        <div class="header__lang">
            <x-frontend.language-selector />
        </div>
        @include('components.user-preview')
    </div>
</header>