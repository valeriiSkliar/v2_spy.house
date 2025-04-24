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
            <x-tariff-link>@yield('tariff-type', 'Trial')</x-tariff-link>
        </div>
        <div class="header__lang">
            @include('partials.language-selector')
        </div>
        @include('components.user-preview')
    </div>
</header>