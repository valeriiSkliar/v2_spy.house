<aside class="aside _home ">
    <div class="aside__content">
        <div class="aside__btn">
            {{-- <div class="aside__lang">
                <div class="lang-menu mb-10">
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
                </div>
            </div> --}}
            @guest
            <a href="{{ route('login') }}" class="btn _flex w-100 mb-10 _dark">{{ __('auth.login') }}</a>
            <a href="{{ route('register') }}" class="btn _flex w-100 mb-10 _green">{{ __('auth.registration') }}</a>
            @endguest
            @auth
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <a class="btn _flex w-100 mb-10 _green" :href="route('logout')" onclick="event.preventDefault();
                                    this.closest('form').submit();">
                    {{ __('auth.logout') }}
                </a>
            </form> @endauth
        </div>
        <nav class="aside-menu">
            <ul>
                <li><a href="#"><span class="aside-menu__txt">{{ __('main_page.features') }}</span></a></li>
                <li><a href="#"><span class="aside-menu__txt">{{ __('main_page.prices') }}</span></a></li>
                <li><a href="#"><span class="aside-menu__txt">{{ __('main_page.reviews') }}</span></a></li>
                <li><a href="{{ route('blog.index') }}"><span class="aside-menu__txt">{{ __('main_page.blog')
                            }}</span></a></li>
            </ul>
        </nav>
        <div class="aside__contacts">
            <a data-toggle="modal" data-target="#modal-contacts" class="link">{{ __('main_page.contacts') }}</a>
        </div>
    </div>
</aside>