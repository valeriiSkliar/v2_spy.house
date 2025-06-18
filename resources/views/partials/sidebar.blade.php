<aside class="aside">
    <div class="aside__head">
        <div class="aside__lang">
            <x-frontend.language-selector />
        </div>
        <div class="aside__tariff">
            @auth
            @php
            $user = auth()->user();
            $currentTariff = $user->currentTariff();
            @endphp

            @if($user->isTrialPeriod())
            <x-tariff-link :type="'trial'" data-toggle="modal" data-target="#modal-current-subscription"
                style="cursor: pointer;">
                {{ __('tariffs.trial') }}
            </x-tariff-link>
            @else
            @if($user->hasActiveSubscription())
            <x-tariff-link :type="$currentTariff['css_class']" data-toggle="modal"
                data-target="#modal-current-subscription" style="cursor: pointer;">
                {{ $currentTariff['name'] }}
            </x-tariff-link>
            @else
            <x-tariff-link>{{ __('tariffs.free') }}</x-tariff-link>
            @endif
            @endif
            {{-- Push modal to global stack --}}
            {{-- @push('modals')
            <x-modals.subscribtion-activated :currentTariff="$currentTariff" />
            @endpush --}}
            @else
            <x-tariff-link>{{ __('tariffs.free') }}</x-tariff-link>
            @endauth
        </div>
        <div class="aside__balance">
            @auth
            @if(auth()->user()->available_balance > 0)
            <a href="#" class="user-balance">
                <span class="user-balance__currency">$</span>
                <span class="user-balance__val">{{ auth()->user()->getFormattedBalanceWithoutCurrency() }}</span>
            </a>
            @endif
            @endauth
        </div>
    </div>
    <div class="aside__content">
        <x-auth.buttons-mobile />
        @include('partials.sidebar-menu')
        @if(auth()->check() && auth()->user()->showUpgradeTariffPromo())
        <x-promo-tariff />
        @endif
        <div class="aside__copyright">{{ __('footer.copyright', ['year' => date('Y')]) }}</div>
    </div>
    <div class="sep _h20"></div>
    <a href="#" target="_blank" class="banner-item"><img src="/img/52400c8bd4719323579fd1a074fff985.gif" alt=""
            class="d-block w-100 rounded-10"></a>
</aside>