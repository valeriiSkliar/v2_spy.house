<nav class="blog-layout__nav">
    <ul class="blog-nav">
        @php
        $activeCategory = $currentCategory ? $currentCategory : null;
        $activeCategoryId = $activeCategory ? $activeCategory->id : null;
        @endphp
        @foreach($categories['categories'] as $category)
        <li class="{{ $category->id == $activeCategoryId ? 'is-active' : '' }}"><a href="{{ route('blog.category', $category->slug) }}"><span>{{ $category->name }}</span> <span class="blog-nav__count">{{ $category->posts_count }}</span></a></li>
        @endforeach

    </ul>
</nav>
<a href="#" target="_blank" class="banner-item mb-25">
    <img src="/img/17d29531d484dadde7c2a0c58893953d.gif" alt="">
</a>

<x-blog.read-often-carousel :popularPosts="$categories['popularPosts']" :heading="'Read often'" />