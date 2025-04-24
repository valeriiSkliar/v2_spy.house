<nav class="aside-menu">
    <ul>
        <li><a href="#" class="{{ request()->routeIs('creatives*') ? 'active' : '' }}"><span class="icon-creatives"></span> <span class="aside-menu__txt">Creatives</span></a></li>
        <li><a href="{{ route('landings.index') }}" class="{{ request()->routeIs('landings*') ? 'active' : '' }}"><span class="icon-landings"></span> <span class="aside-menu__txt">Landings</span></a></li>
        <li><a href="#"><span class="icon-offers"></span> <span class="aside-menu__txt">Offers</span></a></li>
        <li><a href="#"><span class="icon-ai" class="{{ request()->routeIs('ai*') ? 'active' : '' }}"></span> <span class="aside-menu__txt">Creative AI</span></a></li>
        <li><a href="{{ route('services.index') }}" class="{{ request()->routeIs('services*') ? 'active' : '' }}"><span class="icon-services"></span> <span class="aside-menu__txt">Services</span></a></li>
        <li><a href="{{ route('finances.index') }}" class="{{ request()->routeIs('finances*') ? 'active' : '' }}"><span class="icon-finance"></span> <span class="aside-menu__txt">Finance</span></a></li>
        <li><a href="{{ route('tariffs.index') }}" class="{{ request()->routeIs('tariffs*') ? 'active' : '' }}"><span class="icon-tariffs"></span> <span class="aside-menu__txt">Tariffs</span></a></li>
        <li><a href="#"><span class="icon-program" class="{{ request()->routeIs('referrals*') ? 'active' : '' }}"></span> <span class="aside-menu__txt">Referrals</span></a></li>
        <li><a href="{{ route('blog.index') }}" class="{{ request()->routeIs('blog*') ? 'active' : '' }}"><span class="icon-blog"></span> <span class="aside-menu__txt">Blog</span> <span class="aside-menu__count">100</span></a></li>
        <li><a href="#"><span class="icon-faq" class="{{ request()->routeIs('faq*') ? 'active' : '' }}"></span> <span class="aside-menu__txt">FAQ</span></a></li>
    </ul>
</nav>