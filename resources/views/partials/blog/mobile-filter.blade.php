<div class="filter d-xl-none">
    <div class="filter__trigger-mobile d-flex">
        <span class="btn-icon _green _big _filter">
            <span class="icon-list"></span>
            <span class="icon-up font-24"></span>
        </span>
        {{ __('blog.category') }}
    </div>
    <div class="filter__content _blog">
        <ul class="blog-nav pb-3">
            @php
            $activeCategory = $currentCategory ? $currentCategory : null;
            $activeCategoryId = $activeCategory ? $activeCategory->id : null;
            @endphp
            @foreach($categories['categories'] as $category)
            <li class="{{ $category->id == $activeCategoryId ? 'is-active' : '' }}">
                <a href="{{ route('blog.category', $category->slug) }}">
                    <span>{{ $category->name }}</span>
                    <span class="blog-nav__count">{{ $category->posts_count }}</span>
                </a>
            </li>
            @endforeach
        </ul>
    </div>
</div>