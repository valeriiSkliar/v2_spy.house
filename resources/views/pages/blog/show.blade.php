@extends('layouts.blog-new')

@section('blog-content')
@if($breadcrumbs && count($breadcrumbs) > 0)
<x-breadcrumbs :items="$breadcrumbs" />
@endif
<div class="blog-layout">
    <div class="blog-layout__content">
        @include('partials.blog.mobile-filter')

        <div class="article _big _single">
            <x-blog.article-thumb :article="$article" />
            <x-blog.article-meta-header :article="$article" :currentCategory="$currentCategory" />
            <h1 class="article__title">
                <x-blog.new-article-label :article="$article" />
                {{ $article->title }}
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
                        <a class="link _green js-toggle-txt font-weight-600" data-show="See all" data-hide="Hide">See
                            all</a>
                    </div>
                </div>
                @endif

                {!! $article->content !!}
            </div>
            @auth

            @php
            $userRating = Auth::check() ? $article->ratings()->where('user_id', Auth::id())->first() : null;
            @endphp
            <x-blog.article-rating :rating="$article->average_rating ?? 0" :slug="$article->slug" :isRated="$isRated"
                :userRating="$userRating ? $userRating->rating : null" />
            @endauth

            @guest

            <div class="message _bg _with-border font-weight-500 mt-4">
                <span class="icon-warning font-18"></span>
                <div class="message__txt">{{ __('blogs.article_rating.leave_rating') }} <a href="{{ route('login') }}"
                        class="link">{{__('blogs.article_rating.login')}}</a> {{__('blogs.article_rating.to_service')}}
                </div>
            </div>
            @endguest
        </div>

        <a href="#" target="_blank" class="banner-item mb-25">
            <img src="/img/665479769a2c02372b9aeb068bd2ba2a.gif" alt="">
        </a>

        <x-blog.related-articles-carousel :relatedPosts="$relatedPosts" />

        <div class="sep"></div>

        <div id="article-comments-container" class="article _big _single">
            <div class="comments" id="comments"
                data-comments-ajax-url="{{ route('api.blog.comments.get', $article->slug) }}">
                <h2>{{ __('blogs.comments.title') }} <span class="comment-count">{{ $commentsCount }}</span></h2>

                @guest
                <div class="message _bg _with-border font-weight-500">
                    <span class="icon-warning font-18"></span>
                    <div class="message__txt">{{ __('blogs.comments.leave_comment') }} <a href="{{ route('login') }}"
                            class="link">{{__('blogs.comments.login')}}</a> {{__('blogs.comments.to_service')}}</div>
                </div>
                @endguest

                {{-- AJAX container for comment list --}}
                <div id="comments-list-container">
                    <x-blog.comment-list-with-reply-form :comments="$comments" :article="$article" />
                </div>

                {{-- AJAX container for pagination --}}
                <div id="comments-pagination-container" data-pagination-container>
                    @if($comments->hasPages())
                    {{ $comments->links('components.blog.comment.async-pagination', ['paginator' => $comments]) }}
                    @endif
                </div>
            </div>
        </div>
    </div>
    <aside class="blog-layout__aside">
        @include('partials.blog.sidebar')
    </aside>
</div>
@endsection





@section('bottom-banner')
{{-- <a href="#" target="_blank" class="banner-item mb-20">
    <img src="/img/7e520e96565eeafe22e8a1249c5f7896.gif" alt="">
</a> --}}
@endsection