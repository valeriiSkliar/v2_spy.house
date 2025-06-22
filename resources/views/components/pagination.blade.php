@props(['currentPage' => 1, 'totalPages' => 1])

{{--
This partial accepts:
- $currentPage: Current page number
- $totalPages: Total number of pages
--}}

{{-- @dump($currentPage, $totalPages) --}}

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
$prevUrl = $currentPage <= 1 ? '#' : buildUrl($prevParams); // Создаем URL для следующей страницы
    $nextParams=$queryParams; $nextParams['page']=$currentPage + 1; $nextUrl=$currentPage>= $totalPages ? '#' :
    buildUrl($nextParams);
    @endphp

    <nav class="pagination-nav" role="navigation" aria-label="pagination">
        <ul class="pagination-list">
            <li>
                <a class="pagination-link prev {{ $currentPage <= 1 ? 'disabled' : '' }}"
                    aria-disabled="{{ $currentPage <= 1 ? 'true' : 'false' }}" href="{{ $prevUrl }}"
                    data-page="{{ $currentPage - 1 }}" data-action="prev">
                    <span class="icon-prev"></span>
                    <span class="pagination-link__txt">{{ __('pagination.previous') }}</span>
                </a>
            </li>

            @for($i = 1; $i <= $totalPages; $i++) @php // Создаем URL для каждой страницы $pageParams=$queryParams;
                $pageParams['page']=$i; $pageUrl=buildUrl($pageParams); @endphp <li>
                <a class="pagination-link {{ $i == $currentPage ? 'active' : '' }}" href="{{ $pageUrl }}"
                    data-page="{{ $i }}">{{ $i }}</a>
                </li>
                @endfor

                <li>
                    <a class="pagination-link next {{ $currentPage >= $totalPages ? 'disabled' : '' }}"
                        aria-disabled="{{ $currentPage >= $totalPages ? 'true' : 'false' }}" href="{{ $nextUrl }}"
                        data-page="{{ $currentPage + 1 }}" data-action="next">
                        <span class="pagination-link__txt">{{ __('pagination.next') }}</span>
                        <span class="icon-next"></span>
                    </a>
                </li>
        </ul>
    </nav>