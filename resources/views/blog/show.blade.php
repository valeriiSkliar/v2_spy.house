@extends('layouts.blog')

@section('breadcrumbs')
<x-breadcrumbs :items="$breadcrumbs" />
@endsection

@section('page-content')
<div class="article _big _single">
    <div class="article__thumb thumb"><img src="{{ $article['image'] }}" alt="{{ $article['title'] }}"></div>
    <div class="article__row">
        <div class="article__cat">
            <div class="cat-links">
                <a href="{{ route('blog.category', $article->categories->first()->slug) }}" data-color="{{ $article->categories->first()->color }}">{{ $article->categories->first()->name }}</a>
            </div>
        </div>
        <div class="article__info">
            <div class="article-info">
                <div class="article-info__item icon-date">{{ $article->created_at->format('d.m.y') }}</div>
                <a href="#comments" class="article-info__item icon-comment1">{{ $article->comments_count }}</a>
                <div class="article-info__item icon-view">{{ $article->views_count }}</div>
                <div class="article-info__item icon-rating">{{ $article->rating }}</div>
            </div>
        </div>
    </div>
    <h1 class="article__title">
        @if($article['is_new'])
        <span class="article-label">New</span>
        @endif
        {{ $article['title'] }}
    </h1>
    <div class="entry-content pt-3">
        @if(isset($article['table_of_contents']))
        <div class="article__content">
            <div class="hidden-txt _creative">
                <div class="hidden-txt__content">
                    <p><strong>The article consists of:</strong></p>
                    <ul>
                        @foreach($article['table_of_contents'] as $item)
                        <li><a href="{{ $item['link'] }}">{{ $item['title'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <a class="link _green js-toggle-txt font-weight-600" data-show="See all" data-hide="Hide">See all</a>
            </div>
        </div>
        @endif

        {!! $article['content'] !!}
    </div>
    <div class="article-rate">
        <div class="article-rate__txt">
            <p class="mb-1 font-18 font-weight-600">Rate this article</p>
            <p class="mb-0">Rate from 1 to 5</p>
        </div>
        <div class="article-rate__stars">
            <div class="article-rate__rating"></div>
            <div class="article-rate__value font-18"><span class="font-weight-600">{{ $article['user_rating'] ?? 0 }}</span> / 5</div>
        </div>
    </div>
</div>

<a href="#" target="_blank" class="banner-item mb-25">
    <img src="/img/665479769a2c02372b9aeb068bd2ba2a.gif" alt="">
</a>

<div class="pt-1">
    <div class="d-flex align-items-center justify-content-between mb-20">
        <h2 class="font-20 mb-0 mr-3">It will also be interesting</h2>
        <div class="carousel-controls">
            <button id="slick-demo-2-prev" class="carousel-prev"> <span class="icon-prev"></span> </button>
            <button id="slick-demo-2-next" class="carousel-next"> <span class="icon-next"></span> </button>
        </div>
    </div>
    <div class="article-similar">
        <div class="carousel-container" id="slick-demo-2">
            @foreach($relatedArticles as $relatedArticle)
            <div class="carousel-item">
                <div class="article _similar">
                    <a href="{{ route('blog.show', $relatedArticle['slug']) }}" class="article__thumb thumb">
                        <img src="{{ $relatedArticle['image'] }}" alt="{{ $relatedArticle['title'] }}">
                    </a>
                    <a href="{{ route('blog.show', $relatedArticle['slug']) }}" class="article__title">{{ $relatedArticle['title'] }}</a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

<div class="sep"></div>

<div class="article _big _single">
    <div class="comments" id="comments">
        <h2>Comments <span class="comment-count">{{ count($article['comments']) }}</span></h2>

        @if(session('success'))
        <div class="message _bg _with-border _green mb-15">
            <span class="icon-check font-18"></span>
            <div class="message__txt">{{ session('success') }}</div>
        </div>
        @endif

        @guest
        <div class="message _bg _with-border font-weight-500">
            <span class="icon-warning font-18"></span>
            <div class="message__txt">To leave comments, please <a href="{{ route('login') }}" class="link">Log in</a> to our Spy.house service</div>
        </div>
        @else
        <div class="comment-list">
            <div class="sep"></div>
            <div class="comment-form">
                <div class="comment-form__author">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&amp;background=F3FAF7&color=3DC98A&bold=true" alt="{{ auth()->user()->name }}" class="avatar">
                    <span class="font-16 font-weight-600">{{ auth()->user()->name }}</span>
                </div>
                <form action="{{ route('blog.comment.store', $article['slug']) }}" method="POST">
                    @csrf
                    <div class="mb-1">
                        <label class="d-block mb-10">Write the text of the comment</label>
                        <textarea name="content" required></textarea>
                        @error('content')
                        <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="row">
                        <div class="col-6 col-md-auto mt-3">
                            <button type="submit" class="btn _flex _green w-100">Send</button>
                        </div>
                    </div>
                </form>
            </div>

            @if(session('reply_to'))
            <div class="sep"></div>
            <div class="comment-form">
                <div class="comment-form__author">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&amp;background=F3FAF7&color=3DC98A&bold=true" alt="{{ auth()->user()->name }}" class="avatar">
                    <span class="font-16 font-weight-600">{{ auth()->user()->name }}</span>
                </div>
                <form action="{{ route('blog.reply.store', $article['slug']) }}" method="POST">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ session('reply_to')['id'] }}">
                    <div class="mb-1">
                        <div class="d-block mb-10">
                            Leave a comment in response to:
                            <span><span class="icon-reply"></span> <a class="link font-weight-600">{{ session('reply_to')['author'] }}</a></span>
                        </div>
                        <textarea name="content" required></textarea>
                        @error('content')
                        <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="row">
                        <div class="col-6 col-md-auto mt-3">
                            <button type="submit" class="btn _flex _green w-100">Send</button>
                        </div>
                        <div class="col-6 col-md-auto mt-3">
                            <a href="{{ route('blog.show', $article['slug']) }}" class="btn _flex _gray w-100">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
            @endif

            @foreach($article['comments'] as $comment)
            <x-comment :comment="$comment" :slug="$article['slug']" />
            @endforeach
        </div>
        @endguest

        @if($commentsPages > 1)
        <x-pagination :currentPage="$currentPage" :totalPages="$commentsPages" />
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    // $(document).ready(function() {
    //     $(".article-rate__rating").starRating({
    //         initialRating: {
    //             {
    //                 $article['user_rating'] ?? 0
    //             }
    //         },
    //         strokeColor: '#894A00',
    //         strokeWidth: 10,
    //         starSize: 25,
    //         disableAfterRate: false,
    //         useFullStars: true,
    //         hoverColor: '#ffb700',
    //         activeColor: '#ffb700',
    //         ratedColor: '#ffb700',
    //         useGradient: false,
    //         callback: function(currentRating, $el) {
    //             // Отправка рейтинга на сервер через AJAX
    //             $.ajax({
    //                 url: "{{ route('blog.rate', $article['slug']) }}",
    //                 type: "POST",
    //                 data: {
    //                     rating: currentRating,
    //                     _token: "{{ csrf_token() }}"
    //                 },
    //                 success: function(response) {
    //                     if (response.success) {
    //                         $(".article-rate__value .font-weight-600").text(response.rating);
    //                     }
    //                 },
    //                 error: function() {
    //                     alert("Error saving rating. Please try again.");
    //                 }
    //             });
    //         }
    //     });
    // });

    $(document).ready(function() {
        $('.category-link').click(function() {
            var color = $(this).data('color');
            $(this).css('color', color);
        });
    });
    $(document).ready(function() {
        $('.cat-links').click(function() {
            var color = $(this).data('color');
            $(this).css('color', color);
        });
    });
</script>
@endsection



@section('bottom-banner')
<a href="#" target="_blank" class="banner-item mb-20">
    <img src="/img/7e520e96565eeafe22e8a1249c5f7896.gif" alt="">
</a>
@endsection