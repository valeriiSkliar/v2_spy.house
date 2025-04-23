@props(['items' => []])

<ul class="blog-nav">
    @foreach($items as $item)
    <li class="{{ $item['active'] ? 'is-active' : '' }}">
        <a href="{{ $item['url'] }}">
            <span>{{ $item['title'] }}</span>
            <span class="blog-nav__count">{{ $item['count'] }}</span>
        </a>
    </li>
    @endforeach
</ul>