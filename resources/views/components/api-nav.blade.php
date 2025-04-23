@props(['navItems' => [], 'showTitle' => true])

<nav class="api-nav">
    @if($showTitle)
    <div class="api-nav__title"><span class="icon-list"></span> Navigation</div>
    @endif
    <ul>
        @foreach($navItems as $section)
        <li>
            <div class="api-nav__title-group">{{ $section['title'] }}</div>
            <ul>
                @foreach($section['items'] as $item)
                <li class="{{ $item['active'] ? 'active' : '' }}">
                    <a href="{{ $item['url'] }}">{{ $item['name'] }}</a>
                </li>
                @endforeach
            </ul>
        </li>
        @endforeach
    </ul>
</nav>