@props(['popularPosts' => [], 'heading' => __('blogs.read_often')])
<div id="read-often-articles-carousel-header" class="pt-2">
    <div class="d-flex align-items-center justify-content-between mb-20">
        <h2 class="font-20 mb-0">{{ $heading }}</h2>
        <x-common.carousel-controls prevId="read-often-articles-carousel-prev"
            nextId="read-often-articles-carousel-next" />

    </div>
    <div class="carousel-container" id="read-often-articles-carousel-container">
        @foreach($popularPosts as $post)
        <div class="carousel-item">
            <x-blog.related-article-card :article="$post" />
        </div>
        @endforeach
    </div>
</div>