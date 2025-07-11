<section class="reviews">
    <div class="container">
        <div class="row align-items-end _offset30">
            <div class="col-12 col-md-8 pb-2">
                <div class="title-label" data-aos-delay="200" data-aos="fade-up">Reviews</div>
                <h2 class="title" data-aos-delay="200" data-aos="fade-up">What media buyers say about us</h2>
            </div>
            <div class="col-12 col-md-4 d-none d-md-flex justify-content-end mb-30" data-aos-delay="200"
                data-aos="fade-up">
                <a data-toggle="modal" data-target="#add-review" class="btn _flex _border-green _large min-170">Add
                    a review</a>
            </div>
        </div>
        <div class="reviews-slider" data-aos-delay="200" data-aos="fade-up">
            @forelse($reviews as $review)
            <div class="review">
                <div class="review__head">
                    <div class="review__avatar thumb">
                        @if($review->thumbnail_src)
                        <img src="{{ $review->thumbnail_src }}"
                            alt="{{ $review->getTranslation('user_name', app()->getLocale()) }}">
                        @else
                        <img src="https://ui-avatars.com/api/?length=1&name={{ urlencode($review->getTranslation('user_name', app()->getLocale())) }}&background=2B373D&color=ffffff&bold=true"
                            alt="{{ $review->getTranslation('user_name', app()->getLocale()) }}" class="avatar">
                        @endif
                    </div>
                    <div class="review__author">
                        <div class="review__name">{{ $review->getTranslation('user_name', app()->getLocale()) }}</div>
                        <div class="review__role">{{ $review->getTranslation('user_position', app()->getLocale()) }}
                        </div>
                        <div class="review__rating" data-rating="{{ $review->rating }}"></div>
                    </div>
                </div>
                <div class="review__txt">
                    <div class="review__txt-in">
                        {{ $review->getTranslation('content', app()->getLocale()) ?: $review->getTranslation('text',
                        app()->getLocale()) }}
                    </div>
                </div>
            </div>
            @endforelse
        </div>
        <div class="d-md-none text-center pt-5" data-aos-delay="200" data-aos="fade-up">
            <a data-toggle="modal" data-target="#add-review" class="btn _flex _border-green _large min-170">Add a
                review</a>
        </div>
    </div>
</section>