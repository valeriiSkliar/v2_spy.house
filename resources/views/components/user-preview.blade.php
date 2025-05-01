@auth
<div class="user-preview">
    <div class="user-preview__trigger">
        <div class="user-preview__avatar thumb"><span>{{ substr(auth()?->user()?->name, 0, 2) }}</span></div>
        <div class="user-preview__name">{{ auth()->user()?->name }}</div>
        <div class="btn-icon _dark">
            <span class="icon-settings"></span>
            <span class="has-notification"></span>
        </div>
    </div>
    <div class="user-preview__dropdown" style="display: none">
        <nav class="user-menu">
            <ul>
                <li><a href="{{ route('notifications.index') }}"><span class="icon-notification"><span class="has-notification"></span></span> <span>Notifications</span></a></li>
                <li><a href="{{ route('profile.settings') }}"><span class="icon-settings"></span> <span>Profile Settings</span></a></li>
                <li><a href="{{ route('tariffs.index') }}"><span class="icon-tariffs"></span> <span>Tariffs</span></a></li>
            </ul>
            <form method="POST" action="{{ route('logout') }}" class="w-100">
                @csrf
                <button type="submit" class="btn _flex _gray w-100 font-16"><span class="icon-logout mr-3"></span>Log out</button>
            </form>
        </nav>
    </div>
</div>
@endauth

@guest
<a href="{{ route('login') }}" class="btn _flex _dark _small font-15"><span class="icon-login font-16 mr-2 txt-gray-2"></span>Login</a>
@endguest