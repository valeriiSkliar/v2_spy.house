<div class="filter d-xl-none" x-data="blogCategoriesComponent">
    <div class="filter__trigger-mobile d-flex">
        <span class="btn-icon _green _big _filter">
            <span class="icon-list remore_margin"></span>
            <span class="icon-up remore_margin font-24"></span>
        </span>
        {{ __('blog.category') }}
    </div>
    <div class="filter__content _blog">
        <ul class="blog-nav pb-3">
            @php
            $activeCategory = $currentCategory ? $currentCategory : null;
            $activeCategoryId = $activeCategory ? $activeCategory->id : null;
            @endphp
            {{-- All categories link --}}
            <li :class="{ 'is-active': isAllCategoriesActive() }">
                <a href="{{ route('blog.index') }}" 
                   @click.prevent="clearCategory()">
                    <span>{{ __('blogs.all_categories') }}</span>
                </a>
            </li>
            @foreach($categories['categories'] as $category)
            <li :class="{ 'is-active': isCategoryActive('{{ $category->slug }}') }">
                <a href="{{ route('blog.category', $category->slug) }}" 
                   @click.prevent="selectCategory('{{ $category->slug }}')">
                    <span>{{ $category->name }}</span>
                    <span class="blog-nav__count">{{ $category->posts_count }}</span>
                </a>
            </li>
            @endforeach
        </ul>
    </div>
</div>