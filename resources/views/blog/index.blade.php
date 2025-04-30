@extends('layouts.blog')



@section('page-content')
<div class="blog-list">


    <x-article :big="true" :fullWidth="true">
        <x-slot name="thumb">
            <!-- https://blog.spy.house/wp-content/uploads/2023/07/PH_blog_15.png -->
            <a href="{{ route('blog.show', $heroArticle->slug) }}" class="article__thumb thumb"><img src="{{ $heroArticle->featured_image }}" alt=""></a>
        </x-slot>

        <x-slot name="category">
            <div class="cat-links">
                <a href="{{ route('blog.category', $heroArticle->categories->first()->slug) }}" style="color:#CD4F51;">{{ $heroArticle->categories->first()->name }}</a>
            </div>
        </x-slot>

        <x-slot name="info">
            <div class="article-info">
                <div class="article-info__item icon-date">{{ $heroArticle->created_at->format('d.m.y') }}</div>
                <a href="{{ route('blog.show', $heroArticle->slug) }}#comments" class="article-info__item icon-comment1">{{ $heroArticle->comments_count ?? 0 }}</a>
                <div class="article-info__item icon-view">{{ $heroArticle->views_count ?? 0 }}</div>
                <div class="article-info__item icon-rating">{{ $heroArticle->rating ?? 0 }}</div>
            </div>
        </x-slot>

        <x-slot name="title">
            <a href="{{ route('blog.show', $heroArticle->slug) }}" class="article__title h1">{{ $heroArticle->title }}</a>
        </x-slot>

        <x-slot name="description">
            {{ $heroArticle->summary }}
        </x-slot>
    </x-article>



    @foreach($articles as $article)
    <x-article>
        <x-slot name="thumb">
            <!-- https://blog.spy.house/wp-content/uploads/2023/06/PH_blog_microbidding_02.png -->
            <a href="{{ route('blog.show', $article->slug) }}" class="article__thumb thumb"><img src="{{ $article->featured_image }}" alt=""></a>
        </x-slot>

        <x-slot name="info">
            <div class="article-info">
                <div class="article-info__item icon-date">{{ $article->created_at->format('d.m.y') }}</div>
                <a href="{{ route('blog.show', $article->slug) }}#comments" class="article-info__item icon-comment1">{{ $article->comments_count ?? 0 }}</a>
                <div class="article-info__item icon-view">{{ $article->views_count ?? 0 }}</div>
                <div class="article-info__item icon-rating">{{ $article->rating ?? 0 }}</div>
            </div>
        </x-slot>

        <x-slot name="title">
            <a href="{{ route('blog.show', $article->slug) }}" class="article__title">{{ $article->title }}</a>
        </x-slot>

        <x-slot name="category">
            <div class="cat-links">
                @foreach($article->categories as $category)
                <a href="{{ route('blog.category', $category->slug) }}" style="color:#694fcd;">{{ $category->name }}</a>
                @endforeach
            </div>
        </x-slot>
    </x-article>
    @endforeach

    <div class="full-width">
        <a href="#" target="_blank" class="banner-item">
            <img src="/img/665479769a2c02372b9aeb068bd2ba2a.gif" alt="">
        </a>
    </div>

</div>

<nav class="pagination-nav" role="navigation" aria-label="pagination">
    <ul class="pagination-list">
        <li><a class="pagination-link prev disabled" aria-disabled="true" href=""><span class="icon-prev"></span> <span class="pagination-link__txt">Previous</span></a></li>
        <li><a class="pagination-link active" href="#">1</a></li>
        <li><a class="pagination-link" href="#">2</a></li>
        <li><a class="pagination-link" href="#">3</a></li>
        <li><a class="pagination-link next" aria-disabled="false" href="#"><span class="pagination-link__txt">Next</span> <span class="icon-next"></span></a></li>
    </ul>
</nav>
@endsection

@section('bottom-banner')
<a href="#" target="_blank" class="banner-item mb-20">
    <img src="/img/7e520e96565eeafe22e8a1249c5f7896.gif" alt="">
</a>
@endsection