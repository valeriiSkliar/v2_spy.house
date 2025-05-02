@props(['relatedPosts' => [], 'heading' => 'It will also be interesting'])

<div class="pt-1">
    <div class="d-flex align-items-center justify-content-between mb-20">
        <h2 class="font-20 mb-0 mr-3">{{ $heading }}</h2>
        <x-common.carousel-controls prevId="slick-demo-2-prev" nextId="slick-demo-2-next" />
    </div>
    <div class="article-similar">
        <div class="carousel-container" id="slick-demo-2">
            @foreach($relatedPosts as $relatedPost)
            <div class="carousel-item">
                <x-blog.related-article-card :article="$relatedPost" />
            </div>
            @endforeach
        </div>
    </div>
</div>
