<style>
    .search-clear-btn {
        position: absolute;
        right: 40px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        opacity: 0.6;
        transition: opacity 0.2s;
    }

    .search-clear-btn:hover {
        opacity: 1;
    }

    .blog-filter-search {
        position: relative;
    }

    .search-loading {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
    }

    .search-status {
        margin-top: 10px;
        font-size: 0.9em;
        color: #666;
        text-align: center;
    }

    .loading-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #333;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    .blog-search-input.error {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    .validation-error {
        color: #dc3545;
        font-size: 0.875em;
        margin-top: 0.25rem;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>

<div class="blog-filter" x-data="blogSearchComponent()">
    <div class="mb-20">
        <div class="blog-filter-search">
            <span class="icon-search"></span>
            <input type="search" placeholder="Поиск по блогу" x-model="$store.blog.filters.search" x-ref="searchInput"
                x-on:input="debouncedSearch()" x-on:keyup.enter="handleSearchEnter()" :disabled="isLoading"
                class="blog-search-input">

            <!-- Кнопка очистки поиска -->
            <button x-show="hasActiveSearch" x-on:click="clearSearch()" class="search-clear-btn" type="button"
                :disabled="isLoading">
                <span class="icon-close"></span>
            </button>

            <!-- Индикатор загрузки -->
            <div x-show="isLoading" class="search-loading">
                <span class="loading-spinner"></span>
            </div>
        </div>


    </div>
    <div class="mb-20">
        <div class="blog-filter-order">
            <div class="blog-filter-order__label">Сортировка:</div>
            <div class="blog-filter-order__item">
                <!-- asc desc -->
                <button class="w-100 btn _flex _medium sorting-btn asc">Популярные <span
                        class="sorting-btn__icon"></span></button>
            </div>
            <div class="blog-filter-order__item">
                <button class="w-100 btn _flex _medium sorting-btn">Просматрывамые <span
                        class="sorting-btn__icon"></span></button>
            </div>
        </div>
    </div>
</div>