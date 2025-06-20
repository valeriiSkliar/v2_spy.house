{{-- Dynamic Blog Pagination Component --}}
{{-- Использует Alpine.js для динамической навигации --}}

<nav x-data="blogPaginationSimple" x-show="$store.blog.pagination.hasPagination && totalPages > 1"
    x-blog-loading="$store.blog.loading" class="pagination-nav" role="navigation" aria-label="Blog pagination">
    <ul class="pagination-list">
        {{-- Previous Page Link --}}
        <li>
            <a class="pagination-link prev" :class="{ 'disabled': currentPage === 1 || loading }"
                :aria-disabled="currentPage === 1 || loading" x-blog-paginate.prev href="javascript:void(0)">
                <span class="icon-prev"></span>
                <span class="pagination-link__txt">{{ __('pagination.previous') }}</span>
            </a>
        </li>

        {{-- Page Numbers --}}
        <template x-for="page in getVisiblePages()" x-bind:key="page">
            <li>
                <template x-if="page === '...'">
                    <span class="pagination-link ellipsis">...</span>
                </template>

                <template x-if="page !== '...'">
                    <a class="pagination-link" :class="{ 'active': parseInt(page) === parseInt(currentPage) }"
                        :aria-current="parseInt(page) === parseInt(currentPage) ? 'page' : null" x-blog-paginate="page"
                        href="javascript:void(0)" x-text="page"></a>
                </template>
            </li>
        </template>

        {{-- Next Page Link --}}
        <li>
            <a class="pagination-link next" :class="{ 'disabled': currentPage === totalPages || loading }"
                :aria-disabled="currentPage === totalPages || loading" x-blog-paginate.next href="javascript:void(0)">
                <span class="pagination-link__txt">{{ __('pagination.next') }}</span>
                <span class="icon-next"></span>
            </a>
        </li>
    </ul>

    {{-- Loading indicator
    <div x-show="loading" class="pagination-loading" x-transition>
        <span class="loading-spinner"></span>
        <span class="loading-text">Загрузка...</span>
    </div> --}}

    {{-- Debug info (только в development режиме) --}}
    @if(config('app.debug'))
    <div x-show="false" class="pagination-debug"
        style="margin-top: 10px; font-size: 12px; color: #666; border: 1px solid #ddd; padding: 5px;">
        <div style="margin-top: 5px;">
            <strong>Debug info:</strong><br>
            Current: <span x-text="currentPage"></span> (type: <span x-text="typeof currentPage"></span>) |
            Total: <span x-text="totalPages"></span> |
            Loading: <span x-text="loading"></span><br>
            Store currentPage: <span x-text="$store.blog.pagination.currentPage"></span> |
            Store filters.page: <span x-text="$store.blog.filters.page"></span>
        </div>
    </div>
    @endif
</nav>