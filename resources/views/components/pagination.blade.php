@props(['currentPage' => 1, 'totalPages' => 1])

@php
// Получаем текущие параметры запроса
$queryParams = request()->query();

// Функция для создания URL с сохранением всех параметров
function buildUrl($params) {
return '?' . http_build_query($params);
}

// Создаем URL для предыдущей страницы
$prevParams = $queryParams;
$prevParams['page'] = $currentPage - 1;
$prevUrl = $currentPage <= 1 ? '' : buildUrl($prevParams);

    // Создаем URL для следующей страницы
    $nextParams=$queryParams;
    $nextParams['page']=$currentPage + 1;
    $nextUrl=$currentPage>= $totalPages ? '' : buildUrl($nextParams);
    @endphp

    <nav class="pagination-nav" role="navigation" aria-label="pagination">
        <ul class="pagination-list">
            <li>
                <a class="pagination-link prev {{ $currentPage <= 1 ? 'disabled' : '' }}"
                    aria-disabled="{{ $currentPage <= 1 ? 'true' : 'false' }}"
                    href="{{ $prevUrl }}">
                    <span class="icon-prev"></span>
                    <span class="pagination-link__txt">Previous</span>
                </a>
            </li>

            @for($i = 1; $i <= $totalPages; $i++)
                @php
                // Создаем URL для каждой страницы
                $pageParams=$queryParams;
                $pageParams['page']=$i;
                $pageUrl=buildUrl($pageParams);
                @endphp
                <li>
                <a class="pagination-link {{ $i == $currentPage ? 'active' : '' }}"
                    href="{{ $pageUrl }}">{{ $i }}</a>
                </li>
                @endfor

                <li>
                    <a class="pagination-link next {{ $currentPage >= $totalPages ? 'disabled' : '' }}"
                        aria-disabled="{{ $currentPage >= $totalPages ? 'true' : 'false' }}"
                        href="{{ $nextUrl }}">
                        <span class="pagination-link__txt">Next</span>
                        <span class="icon-next"></span>
                    </a>
                </li>
        </ul>
    </nav>