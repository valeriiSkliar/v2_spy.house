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
<div class="pt-2">
    <div class="d-flex align-items-center justify-content-between mb-20">
        <h2 class="font-20 mb-0">Read often</h2>
        <div class="carousel-controls">
            <button id="slick-demo-1-prev" class="carousel-prev"> <span class="icon-prev"></span> </button>
            <button id="slick-demo-1-next" class="carousel-next"> <span class="icon-next"></span> </button>
        </div>
    </div>
    <div class="carousel-container" id="slick-demo-1">
        @foreach($categories['popularPosts'] as $post)
        <div class="carousel-item">
            <div class="article _similar">
                <a href="{{ route('blog.show', $post->slug) }}" class="article__thumb thumb"><img src="{{ $post->featured_image }}" alt=""></a>
                <a href="{{ route('blog.show', $post->slug) }}" class="article__title">{{ $post->title }}</a>
            </div>
        </div>
        @endforeach
    </div>
</div>