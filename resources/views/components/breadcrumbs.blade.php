@props(['items' => []])

<nav class="breadcrumbs-block" aria-label="Breadcrumb">
    <ol class="breadcrumbs">
        @foreach($items as $index => $item)
        <li class="{{ $index === count($items) - 1 ? 'current' : '' }}">
            @if($index === count($items) - 1)
            <span class="breadcrumb-text">{{ $item['title'] }}</span>
            @else
            <a class="breadcrumb-link" href="{{ $item['url'] }}">{{ $item['title'] }}</a>
            <span class="separator" aria-hidden="true"><span class="icon-next"></span></span>
            @endif
        </li>
        @endforeach
    </ol>
</nav>