@props(['route' => 'services.index', 'text' => 'To the list of services'])

<div class="mb-20">
    <a href="{{ route($route) }}" class="btn _flex _medium _gray"><span class="icon-prev mr-2 font-18"></span> {{ $text }}</a>
</div>