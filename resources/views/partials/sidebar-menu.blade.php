<nav class="aside-menu">
    <ul>
        <li><a href="{{ route('creatives.index') }}" class="{{ request()->routeIs('creatives*') ? 'active' : '' }}"><span class="icon-creatives"></span> <span class="aside-menu__txt">{{__('menu.creatives')}}</span></a></li>
        <li><a href="{{ route('landings.index') }}" class="{{ request()->routeIs('landings*') ? 'active' : '' }}"><span class="icon-landings"></span> <span class="aside-menu__txt">{{__('menu.landings')}}</span></a></li>
        <li><a href="#"><span class="icon-offers"></span> <span class="aside-menu__txt">{{__('menu.offers')}}</span></a></li>
        <li><a href="#"><span class="icon-ai" class="{{ request()->routeIs('ai*') ? 'active' : '' }}"></span> <span class="aside-menu__txt">{{__('menu.creative_ai')}}</span></a></li>
        <li><a href="{{ route('services.index') }}" class="{{ request()->routeIs('services*') ? 'active' : '' }}"><span class="icon-services"></span> <span class="aside-menu__txt">{{__('menu.services')}}</span></a></li>
        <li><a href="{{ route('finances.index') }}" class="{{ request()->routeIs('finances*') ? 'active' : '' }}"><span class="icon-finance"></span> <span class="aside-menu__txt">{{__('menu.finance')}}</span></a></li>
        <li><a href="{{ route('tariffs.index') }}" class="{{ request()->routeIs('tariffs*') ? 'active' : '' }}"><span class="icon-tariffs"></span> <span class="aside-menu__txt">{{__('menu.tariffs')}}</span></a></li>
        <li><a href="#"><span class="icon-program" class="{{ request()->routeIs('referrals*') ? 'active' : '' }}"></span> <span class="aside-menu__txt">{{__('menu.referrals')}}</span></a></li>
        <li><a href="{{ route('blog.index') }}" class="{{ request()->routeIs('blog*') ? 'active' : '' }}"><span class="icon-blog"></span> <span class="aside-menu__txt">{{__('menu.blog')}}</span> <span class="aside-menu__count">
                    @php
                    $posts = App\Models\Frontend\Blog\BlogPost::all();
                    echo $posts->count();
                    @endphp
                </span></a></li>
        <li><a href="#"><span class="icon-faq" class="{{ request()->routeIs('faq*') ? 'active' : '' }}"></span> <span class="aside-menu__txt">{{__('menu.faq')}}</span></a></li>
    </ul>
</nav>