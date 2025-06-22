@if ($paginator->hasPages())

<nav class="pagination-nav" role="navigation" aria-label="pagination">
    <ul class="pagination-list">
        @if ($paginator->onFirstPage())
        <li><a class="pagination-link prev disabled" aria-disabled="true" href="#"><span class="icon-prev"></span> <span
                    class="pagination-link__txt">Previous</span></a></li>
        @else
        <li><a class="pagination-link prev" aria-disabled="false" href="{{ $paginator->previousPageUrl() }}"><span
                    class="icon-prev"></span> <span class="pagination-link__txt">Previous</span></a></li>
        @endif
        @foreach ($elements as $element)
        @if (is_array($element))
        @foreach ($element as $page => $url)
        @if ($page == $paginator->currentPage())
        <li><a class="pagination-link active" href="#">{{ $page }}</a></li>
        @else
        <li><a class="pagination-link" href="{{ $url }}">{{ $page }}</a></li>
        @endif
        @endforeach
        @endif
        @endforeach
        @if ($paginator->hasMorePages())
        <li><a class="pagination-link next" aria-disabled="false" href="{{ $paginator->nextPageUrl() }}"><span
                    class="pagination-link__txt">Next</span> <span class="icon-next"></span></a></li>
        @else
        <li><a class="pagination-link next disabled" aria-disabled="true" href="#"><span
                    class="pagination-link__txt">Next</span> <span class="icon-next"></span></a></li>
        @endif
    </ul>
</nav>

@endif