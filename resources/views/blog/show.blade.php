@extends('layouts.blog')

@section('breadcrumbs')
<x-breadcrumbs :items="$breadcrumbs" />
@endsection

@section('page-content')
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
                <a class="link _green js-toggle-txt font-weight-600" data-show="See all" data-hide="Hide">See all</a>
            </div>
        </div>
        @endif

        {!! $article->content !!}
    </div>
    @auth

    @php
        $userRating = Auth::check() ? $article->ratings()->where('user_id', Auth::id())->first() : null;
    @endphp
        <x-blog.article-rating 
        :rating="$article->average_rating ?? 0" 
        :slug="$article->slug"
        :isRated="$isRated"
        :userRating="$userRating ? $userRating->rating : null" />
    @endauth
    
    @guest

        <div class="message _bg _with-border font-weight-500 mt-4">
            <span class="icon-warning font-18"></span>
            <div class="message__txt">To leave rating, please <a href="{{ route('login') }}" class="link">Log in</a> to our Spy.house service</div>
        </div>
    @endguest
</div>

<a href="#" target="_blank" class="banner-item mb-25">
    <img src="/img/665479769a2c02372b9aeb068bd2ba2a.gif" alt="">
</a>

<x-blog.related-articles-carousel :relatedPosts="$relatedPosts" />

<div class="sep"></div>

<div class="article _big _single">
    <div class="comments" id="comments">
        <h2>Comments <span class="comment-count">{{ $comments->total() }}</span></h2>

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
        @endguest

        <x-blog.comment-list-with-reply-form :comments="$comments" :article="$article" />

        @auth
        {{ $comments->links('components.blog.comment.async-pagination', ['paginator' => $comments]) }}
        @endauth
    </div>
</div>
@endsection





@section('bottom-banner')
<a href="#" target="_blank" class="banner-item mb-20">
    <img src="/img/7e520e96565eeafe22e8a1249c5f7896.gif" alt="">
</a>
@endsection