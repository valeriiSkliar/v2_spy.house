@props(['relatedPosts' => [], 'heading' => __('blogs.it_will_also_be_interesting')])

<div id="alsow-interesting-articles-carousel" class="pt-1">
    <div class="d-flex align-items-center justify-content-between mb-20">
        <h2 class="font-20 mb-0 mr-3">{{ $heading }}</h2>
        <x-common.carousel-controls prevId="alsow-interesting-articles-carousel-prev" nextId="alsow-interesting-articles-carousel-next" />
    </div>

    @php
        $slidesToShow = $relatedPosts->count() > 3 ? 3 : $relatedPosts->count();
    @endphp

    <div class="article-similar">
        <div 
                class="carousel-container" 
                id="alsow-interesting-articles-carousel-container"
            >
            @foreach($relatedPosts as $relatedPost)
            <div class="carousel-item">
                <x-blog.related-article-card :article="$relatedPost" />
            </div>
            @endforeach
        </div>
    </div>
</div>
