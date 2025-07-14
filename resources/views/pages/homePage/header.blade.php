<header class="header _home">
    <div class="container" data-aos-delay="200" data-aos="fade-down">
        <div class="header__burger">
            <button class="btn-icon _dark js-menu">
                <span class="menu-burger"><span></span><span></span><span></span><span></span></span>
            </button>
        </div>
        <div class="header__left">
            <a href="/" class="header__logo"><img src="img/logo.svg?v=2" alt=""></a>
        </div>
        <nav class="header__nav">
            <ul>
                <li><a href="#features">{{ __('main_page.features') }}</a></li>
                <li><a href="#prices">{{ __('main_page.prices') }}</a></li>
                <li><a href="#reviews">{{ __('main_page.reviews') }}</a></li>
                <li><a href="{{ route('blog.index') }}">{{ __('main_page.blog') }}</a></li>
            </ul>
        </nav>
        <div class="header__right">
            <div class="header__contacts">
                <a data-toggle="modal" data-target="#modal-contacts" class="link">{{ __('main_page.contacts') }}</a>
            </div>
            <div class="header__lang">
                {{-- <div class="lang-menu">
                    <div class="base-select">
                        <div class="base-select__trigger">
                            <span class="base-select__value"><img src="img/flags/US.svg" alt="">Eng</span>
                            <span class="base-select__arrow"></span>
                        </div>
                        <ul class="base-select__dropdown" style="display: none;">
                            <li class="base-select__option is-selected"><img src="img/flags/US.svg" alt="">Eng</li>
                            <li class="base-select__option"><img src="img/flags/UA.svg" alt="">Uk</li>
                            <li class="base-select__option"><img src="img/flags/ES.svg" alt="">Esp</li>
                        </ul>
                    </div>
                </div> --}}
                {{--
                <x-frontend.language-selector /> --}}

            </div>
            <div class="header__login">
                @guest
                <a href="{{ route('login') }}" class="btn _flex _orange font-16 font-weight-bold">{{ __('auth.login')
                    }}</a>
                @endguest
            </div>
            @guest

            <div class="header__login-mobile">
                <a href="#" class="btn-icon _dark"><span class="icon-login font-20"></span></a>
            </div>
            @endguest
            @auth
            <div class="user-preview">
                <a href="{{ route('profile.settings') }}" class="user-preview__trigger">
                    <div class="user-preview__avatar thumb">
                        <span>{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 2)) }}</span>
                    </div>
                    <div class="user-preview__name">{{ Auth::user()->name }}</div>
                </a>
            </div>
            @endauth
            <!-- User Login
            <div class="user-preview">
                <a href="#" class="user-preview__trigger">
                    <div class="user-preview__avatar thumb"><span>LV</span></div>
                    <div class="user-preview__name">Lysenko V.</div>
                </a>
            </div>
            -->
        </div>
    </div>
</header>