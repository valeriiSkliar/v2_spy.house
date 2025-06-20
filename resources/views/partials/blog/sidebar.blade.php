<nav class="blog-layout__nav" data-blog-sidebar x-data="blogCategoriesComponent">
    <ul class="blog-nav">
        @php
        $activeCategory = $currentCategory ? $currentCategory : null;
        $activeCategoryId = $activeCategory ? $activeCategory->id : null;
        @endphp
        {{-- All categories link --}}
        <li :class="{ 'is-active': isAllCategoriesActive() }">
            <a href="{{ route('blog.index') }}" 
               @click.prevent="clearCategory()" 
               data-category-slug="">
                <span>{{ __('blogs.all_categories') }}</span>
            </a>
        </li>
        @foreach($categories['categories'] as $category)
        <li :class="{ 'is-active': isCategoryActive('{{ $category->slug }}') }">
            <a href="{{ route('blog.category', $category->slug) }}" 
               @click.prevent="selectCategory('{{ $category->slug }}')"
               data-category-slug="{{ $category->slug }}">
                <span>{{ $category->name }}</span>
                <span class="blog-nav__count">{{ $category->posts_count }}</span>
            </a>
        </li>
        @endforeach
    </ul>
</nav>
<a href="#" target="_blank" class="banner-item mb-25">
    <img src="/img/17d29531d484dadde7c2a0c58893953d.gif" alt="">
</a>

{{--
<x-blog.read-often-carousel :popularPosts="$categories['popularPosts']" :heading="__('blogs.read_often')" /> --}}