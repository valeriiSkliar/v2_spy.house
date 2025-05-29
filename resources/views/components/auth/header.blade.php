@props(['homeRoute' => 'home'])

<header class="header">
    <div class="header__home">
        <a href="{{ route($homeRoute) }}" class="btn-icon _dark"><span class="icon-home"></span></a>
    </div>
    <div class="header__left">
        <a href="{{ route($homeRoute) }}" class="header__logo">
            <img src="{{ asset('img/logo.svg') }}" alt="" width="142" height="36">
        </a>
    </div>
    <div class="header__lang">
        <x-frontend.language-selector />
    </div>
</header>