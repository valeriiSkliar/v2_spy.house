<aside class="aside _home ">
    <div class="aside__content">
        <div class="aside__btn">
            <div class="lang-menu">
                <x-frontend.language-selector class="mb-10" />
            </div>

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