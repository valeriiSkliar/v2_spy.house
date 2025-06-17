@props(['currentPage', 'totalPages', 'pagination'])

@if ($pagination->hasPages())
<nav class="pagination-nav" role="navigation" aria-label="pagination">
    <ul class="pagination-list">
        {{-- Previous Page Link --}}
        <li>
            <a class="pagination-link prev {{ $pagination->onFirstPage() ? 'disabled' : '' }}"
                aria-disabled="{{ $pagination->onFirstPage() ? 'true' : 'false' }}"
                href="{{ $pagination->onFirstPage() ? '' : $pagination->previousPageUrl() }}"
                data-page="{{ $pagination->onFirstPage() ? '' : $pagination->currentPage() - 1 }}">
                <span class="icon-prev"></span>
                <span class="pagination-link__txt">{{ __('tariffs.previous') }}</span>
            </a>
        </li>

        {{-- Pagination Elements --}}
        @foreach ($pagination->getUrlRange(1, $pagination->lastPage()) as $page => $url)
        <li>
            <a class="pagination-link {{ $page == $pagination->currentPage() ? 'active' : '' }}" href="{{ $url }}"
                data-page="{{ $page }}">{{ $page }}</a>
        </li>
        @endforeach

        {{-- Next Page Link --}}
        <li>
            <a class="pagination-link next {{ $pagination->hasMorePages() ? '' : 'disabled' }}"
                aria-disabled="{{ $pagination->hasMorePages() ? 'false' : 'true' }}"
                href="{{ $pagination->hasMorePages() ? $pagination->nextPageUrl() : '' }}"
                data-page="{{ $pagination->hasMorePages() ? $pagination->currentPage() + 1 : '' }}">
                <span class="pagination-link__txt">{{ __('tariffs.next') }}</span>
                <span class="icon-next"></span>
            </a>
        </li>
    </ul>
</nav>
@endif