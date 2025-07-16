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


        @if($user->isTrialPeriod())
        <div class="header__tariff">
            <x-tariff-link :type="'trial'" data-toggle="modal" data-target="#modal-current-subscription"
                style="cursor: pointer;">
                {{ __('tariffs.trial') }}
            </x-tariff-link>
        </div>
        @elseif($user->email_verified_at)
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

        {{-- Push modal to global stack --}}
        @push('modals')
        <x-modals.subscribtion-activated :currentTariff="$currentTariff" />
        @endpush
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