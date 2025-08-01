<header class="header">
    <div class="header__burger">
        <button class="btn-icon _dark js-menu">
            <span class="menu-burger"><span></span><span></span><span></span><span></span></span>
        </button>
    </div>
    <div class="header__left">
        <a href="/" class="header__logo"><img src="/img/logo.svg" alt="" width="142" height="36"></a>
        <div class="header-blog font-roboto">{{ __('header.blog_title') }}</div>
        @if(Auth::check())
        <div class="header__btn">
            <a href="{{ route('creatives.index') }}" class="btn _flex _small _green2 ml-2">{{
                __('header.back_to_ads') }} <span class="icon-next font-16 ml-2"></span></a>
        </div>
        @endif
    </div>
    <div class="header__right">
        <div class="header__lang">
            <x-frontend.language-selector />
        </div>
        @include('components.user-preview')
    </div>
</header>