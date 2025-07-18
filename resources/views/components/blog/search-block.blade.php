<div class="blog-filter">
    <div class="mb-20">
        <div class="blog-filter-search">
            <span class="icon-search"></span>
            <input type="search" placeholder="{{ __('blog.search.placeholder') }}">
        </div>
    </div>
    <div class="mb-20">
        <div class="blog-filter-order">
            <div class="blog-filter-order__label">{{ __('blog.search.order') }}</div>
            <div class="blog-filter-order__item">
                <!-- asc desc -->
                <button class="w-100 btn _flex _medium sorting-btn asc">{{ __('blog.search.popular') }} <span
                        class="sorting-btn__icon"></span></button>
            </div>
            <div class="blog-filter-order__item">
                <button class="w-100 btn _flex _medium sorting-btn">{{ __('blog.search.viewed') }} <span
                        class="sorting-btn__icon"></span></button>
            </div>
        </div>
    </div>
</div>