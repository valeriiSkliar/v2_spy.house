@props(['currentPage' => 1, 'totalPages' => 1])

<nav class="pagination-nav" role="navigation" aria-label="pagination">
    <ul class="pagination-list">
        <li>
            <a class="pagination-link prev {{ $currentPage <= 1 ? 'disabled' : '' }}"
                aria-disabled="{{ $currentPage <= 1 ? 'true' : 'false' }}"
                href="{{ $currentPage <= 1 ? '' : '?page=' . ($currentPage - 1) }}">
                <span class="icon-prev"></span>
                <span class="pagination-link__txt">Previous</span>
            </a>
        </li>

        @for($i = 1; $i <= $totalPages; $i++)
            <li>
            <a class="pagination-link {{ $i == $currentPage ? 'active' : '' }}"
                href="?page={{ $i }}">{{ $i }}</a>
            </li>
            @endfor

            <li>
                <a class="pagination-link next {{ $currentPage >= $totalPages ? 'disabled' : '' }}"
                    aria-disabled="{{ $currentPage >= $totalPages ? 'true' : 'false' }}"
                    href="{{ $currentPage >= $totalPages ? '' : '?page=' . ($currentPage + 1) }}">
                    <span class="pagination-link__txt">Next</span>
                    <span class="icon-next"></span>
                </a>
            </li>
    </ul>
</nav>