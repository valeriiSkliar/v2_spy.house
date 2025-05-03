@props(['rating' => 0, 'slug' => null])

<div class="article-rate">
    <div class="article-rate__txt">
        <p class="mb-1 font-18 font-weight-600">Rate this article</p>
        <p class="mb-0">Rate from 1 to 5</p>
    </div>
    <div class="article-rate__stars">
        <div class="article-rate__rating" data-rating="{{ $rating }}" data-slug="{{ $slug }}"   ></div>
        <div class="article-rate__value font-18"><span class="font-weight-600">{{ $rating }}</span> / 5</div>
    </div>
</div>
