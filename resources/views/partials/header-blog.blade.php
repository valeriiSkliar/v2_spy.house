<header class="header">
    <div class="header__burger">
        <button class="btn-icon _dark js-menu">
            <span class="menu-burger"><span></span><span></span><span></span><span></span></span>
        </button>
    </div>
    <div class="header__left">
        <a href="/" class="header__logo"><img src="/img/logo.svg" alt="" width="142" height="36"></a>
        <div class="header-blog font-roboto">BLOG</div>
        <div class="header__btn">
            <a href="{{ route('creatives.index') }}" class="btn _flex _small _green2 ml-2">Go to ads <span class="icon-next font-16 ml-2"></span></a>
        </div>
    </div>
    <div class="header__right">
        <button class="btn-icon _transparent js-toggle-search"><span class="icon-search font-20"></span></button>
        <div class="header__lang">
            @include('partials.language-selector')
        </div>
        <a href="#" class="btn _flex _dark _small font-15 d-none d-lg-inline-flex"><span class="icon-login font-16 mr-2 txt-gray-2"></span>Login</a>
        <a href="#" class="btn-icon _dark d-lg-none"><span class="icon-login font-16"></span></a>
    </div>
</header>