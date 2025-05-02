<nav class="pagination-nav" role="navigation" aria-label="pagination">
        @if ($paginator->hasPages())
        <ul class="pagination-list">
            @if ($paginator->onFirstPage())
                <li><a class="pagination-link prev disabled" data-page="{{ $paginator->previousPageUrl() }}" aria-disabled="true" href="javascript:void(0)"><span class="icon-prev"></span> <span class="pagination-link__txt">Previous</span></a></li>
            @else
                <li><a class="pagination-link prev" data-page="{{ $paginator->previousPageUrl() }}" aria-disabled="false" href="javascript:void(0)"><span class="icon-prev"></span> <span class="pagination-link__txt">Previous</span></a></li>
            @endif
            @if(isset($elements[0]) && is_array($elements[0]) && count($elements[0]) > 0)
                @foreach ($elements[0] as $page => $value)
                    @if ($page == $paginator->currentPage())
                        <li><a class="pagination-link active" data-page="{{ $page }}" href="javascript:void(0)">{{ $page }}</a></li>
                    @else
                        <li><a class="pagination-link" data-page="{{ $page }}" href="javascript:void(0)">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
            @if ($paginator->hasMorePages())
                <li><a class="pagination-link next" data-page="{{ $paginator->nextPageUrl() }}" aria-disabled="false" href="javascript:void(0)"><span class="pagination-link__txt">Next</span> <span class="icon-next"></span></a></li>
            @else
                <li><a class="pagination-link next disabled" data-page="" aria-disabled="true" href="javascript:void(0)"><span class="pagination-link__txt">Next</span> <span class="icon-next"></span></a></li>
            @endif
        </ul>
        @endif
    </nav>

    
