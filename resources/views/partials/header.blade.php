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
        <div class="header__tariff">
            <div class="header__tariff">
                @if(auth()->check() && auth()->user()->hasTariff())
                <x-tariff-link :type="auth()->user()->currentTariff()['css_class']">
                    {{ auth()->user()->currentTariff()['name'] }}
                </x-tariff-link>
                @else
                <x-tariff-link>Free</x-tariff-link>
                @endif
            </div>
        </div>
        <div class="header__lang">
            <x-frontend.language-selector />
        </div>
        @include('components.user-preview')
    </div>
</header>