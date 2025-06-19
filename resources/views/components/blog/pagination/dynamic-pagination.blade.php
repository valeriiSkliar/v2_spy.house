{{-- Dynamic Blog Pagination Component --}}
{{-- Использует Alpine.js для динамической навигации --}}

<nav x-data="blogPagination" x-show="hasPagination && totalPages > 1" x-blog-loading="loading" class="pagination-nav"
    role="navigation" aria-label="Blog pagination">
    <ul class="pagination-list">
        {{-- Previous Page Link --}}
        <li>
            <a x-bind:class="getNavClasses('prev')" x-bind:aria-disabled="isFirstPage" x-blog-paginate.prev
                href="javascript:void(0)" x-on:click="goToPrev()">
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
                    <a x-bind:class="getPageClasses(page)" x-bind:aria-current="page === currentPage ? 'page' : null"
                        x-blog-paginate="page" href="javascript:void(0)" x-on:click="handlePageClick($event, page)"
                        x-text="page"></a>
                </template>
            </li>
        </template>

        {{-- Next Page Link --}}
        <li>
            <a x-bind:class="getNavClasses('next')" x-bind:aria-disabled="isLastPage" x-blog-paginate.next
                href="javascript:void(0)" x-on:click="goToNext()">
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
    <div x-show="false" class="pagination-debug" style="margin-top: 10px; font-size: 12px; color: #666;">
        <button x-on:click="debug()"
            style="background: #f0f0f0; border: 1px solid #ccc; padding: 4px 8px; border-radius: 3px;">
            Debug Pagination
        </button>
        <div style="margin-top: 5px;">
            Current: <span x-text="currentPage"></span> |
            Total: <span x-text="totalPages"></span> |
            Loading: <span x-text="loading"></span>
        </div>
    </div>
    @endif
</nav>