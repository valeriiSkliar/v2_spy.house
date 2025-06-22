<nav class="pagination-nav" role="navigation" aria-label="pagination">
    @if ($paginator->hasPages())
    <ul class="pagination-list">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
        <li><a class="pagination-link prev disabled" aria-disabled="true" href="#"><span class="icon-prev"></span> <span
                    class="pagination-link__txt">{{ __('pagination.previous') }}</span></a></li>
        @else
        <li><a class="pagination-link prev" aria-disabled="false" href="{{ $paginator->previousPageUrl() }}"><span
                    class="icon-prev"></span> <span class="pagination-link__txt">{{ __('pagination.previous')
                    }}</span></a></li>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
        {{-- "Three Dots" Separator --}}
        @if (is_string($element)) {{-- This is for "..." separator which is not in your new template design directly but
        Laravel's default logic might produce it. Let's assume for now the new template doesn't want "..." and we'd rely
        only on numbered links and prev/next. If "..." is needed, the template needs a <li> for it. The provided loop
            for $elements usually handles this. The provided template seems to only iterate if $element is an array for
            numbered pages. We'll keep Laravel's default $elements loop as it's robust. --}}
            @if (is_string($element))
        <li class="disabled"><span>{{ $element }}</span></li> {{-- Standard way to show "..." --}}
        @endif
        @endif

        {{-- Array Of Links --}}
        @if (is_array($element))
        @foreach ($element as $page => $url)
        @if ($page == $paginator->currentPage())
        <li><a class="pagination-link active" href="#" aria-current="page">{{ $page }}</a></li>
        @else
        <li><a class="pagination-link" href="{{ $url }}">{{ $page }}</a></li>
        @endif
        @endforeach
        @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
        <li><a class="pagination-link next" aria-disabled="false" href="{{ $paginator->nextPageUrl() }}"><span
                    class="pagination-link__txt">{{ __('pagination.next') }}</span> <span class="icon-next"></span></a>
        </li>
        @else
        <li><a class="pagination-link next disabled" aria-disabled="true" href="#"><span class="pagination-link__txt">{{
                    __('pagination.next') }}</span> <span class="icon-next"></span></a></li>
        @endif
    </ul>
    @endif
</nav>