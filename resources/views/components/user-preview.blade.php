@auth
<div class="user-preview">
    <div class="user-preview__trigger">
        <div id="user-preview-avatar-header" class="user-preview__avatar thumb">
            @if(auth()->user()->user_avatar)
            <img src="{{ asset('storage/' . auth()->user()->user_avatar) }}" alt="{{ auth()->user()->name }}">
            @else
            <span>{{ substr(auth()?->user()?->name, 0, 2) }}</span>
            @endif
        </div>
        <div id="user-preview-name" class="user-preview__name">{{ auth()->user()?->login }}</div>
        <div class="btn-icon _dark">
            <span id="notification-indicator-preview" class="icon-settings remore_margin"></span>
            @if(auth()->user()->unreadNotifications->count() > 0)
            <span class="has-notification"></span>
            @endif
        </div>
    </div>
    <div class="user-preview__dropdown" style="display: none">
        <nav class="user-menu">
            <ul>
                <li><a href="{{ route('notifications.index') }}">
                        <span id="notification-indicator-notification-menu" class="icon-notification">
                            @if(auth()->user()->unreadNotifications->count() > 0)
                            <span class="has-notification"></span>
                            @endif
                        </span>
                        <span>
                            {{ __('header.notifications') }}
                        </span>
                    </a>
                </li>
                <li><a href="{{ route('profile.settings') }}"><span class="icon-settings remore_margin"></span> <span>{{
                            __('header.profile_settings') }}</span></a></li>
                <li><a href="{{ route('tariffs.index') }}"><span class="icon-tariffs remore_margin"></span> <span>{{
                            __('header.tariffs') }}</span></a></li>
            </ul>
            <form method="POST" action="{{ route('logout') }}" class="w-100">
                @csrf
                <button type="submit" class="btn _flex _gray w-100 font-16"><span class="icon-logout mr-3"></span>{{
                    __('header.logout') }}</button>
            </form>
        </nav>
    </div>
</div>
@endauth

@guest
<a href="{{ route('login') }}" class="btn _flex _dark _small font-15"><span
        class="icon-login font-16 mr-2 txt-gray-2"></span>{{ __('header.login') }}</a>
@endguest