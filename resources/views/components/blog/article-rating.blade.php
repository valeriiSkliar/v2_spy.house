@props(['rating' => 0, 'slug' => null, 'isRated' => false, 'userRating' => null])

<div class="article-rate">
    @if(!$isRated)
    <div class="article-rate__txt">
        <p class="mb-1 font-18 font-weight-600">{{ __('blogs.article_rating.rate_this_article') }}</p>
        <p class="mb-0">{{ __('blogs.article_rating.rate_from_1_to_5') }}</p>
    </div>
    <div class="article-rate__stars">
        <div class="article-rate__rating" data-rating="{{ $rating }}" data-slug="{{ $slug }}"   ></div>
        <div class="article-rate__value font-18"><span class="font-weight-600">{{ $rating }}</span> / 5</div>
    </div>
    @else
    <div class="article-rate__success">
        <p class="mb-0 font-18 font-weight-600">{{ __('blogs.article_rating.thank_you_for_rating') }}</p>
        @if($userRating)
            <p class="mb-0">{{ __('blogs.article_rating.you_rated_this_article') }}: {{ $userRating }} {{ __('blogs.article_rating.stars') }}</p>
        @endif
        <p class="mb-0">{{ __('blogs.article_rating.average_rating') }}: {{ $rating }}</p>
    </div>
    @endif
</div>
