@props(['articles', 'heroArticle'])

@if($heroArticle)
<x-article :big="true" :fullWidth="true" :isNew="true" :showMoreButton="true">
    <x-slot name="thumb">
        <a href="{{ route('blog.show', $heroArticle->slug) }}" class="article__thumb thumb">
            <img src="{{ $heroArticle->featured_image }}" alt="{{ $heroArticle->title }}">
        </a>
    </x-slot>

    <x-slot name="info">
        <div class="article-info">
            <div class="article-info__item icon-date">{{ $heroArticle->created_at->format('d.m.y / H:i') }}</div>
            <a href="{{ route('blog.show', $heroArticle->slug) }}#comments" class="article-info__item icon-comment1">
                {{ $heroArticle->comments->count() }}
            </a>
            <div class="article-info__item icon-view">{{ $heroArticle->views_count ?? 0 }}</div>
            <div class="article-info__item icon-rating">{{ $heroArticle->average_rating ?? 0 }}</div>
        </div>
    </x-slot>

    <x-slot name="title">
        <a href="{{ route('blog.show', $heroArticle->slug) }}">{{ $heroArticle->title }}</a>
    </x-slot>

    <x-slot name="description">
        {{ $heroArticle->summary }}
    </x-slot>

    <x-slot name="category">
        <div class="cat-links">
            @foreach($heroArticle->categories as $category)
            <a href="{{ route('blog.category', $category->slug) }}" style="color:#CD4F51;">{{ $category->name }}</a>
            @endforeach
        </div>
    </x-slot>

    <x-slot name="moreButton">
        <a href="{{ route('blog.show', $heroArticle->slug) }}"
            class="btn _flex _green {{ request()->is('*/mobile/*') ? '_medium' : '' }}">
            More <span class="icon-next font-16 ml-2"></span>
        </a>
    </x-slot>
</x-article>
@endif

@if($articles->count() > 0)
@foreach($articles as $article)
<x-article :showMoreButton="true">
    <x-slot name="thumb">
        <a href="{{ route('blog.show', $article->slug) }}" class="article__thumb thumb">
            <img src="{{ $article->featured_image }}" alt="{{ $article->title }}">
        </a>
    </x-slot>

    <x-slot name="info">
        <div class="article-info">
            <div class="article-info__item icon-date">{{ $article->created_at->format('d.m.y / H:i') }}</div>
            <a href="{{ route('blog.show', $article->slug) }}#comments" class="article-info__item icon-comment1">
                {{ $article->comments->count() }}
            </a>
            <div class="article-info__item icon-view">{{ $article->views_count ?? 0 }}</div>
            <div class="article-info__item icon-rating">{{ $article->average_rating ?? 0 }}</div>
        </div>
    </x-slot>

    <x-slot name="title">
        <a href="{{ route('blog.show', $article->slug) }}">{{ $article->title }}</a>
    </x-slot>

    <x-slot name="description">
        {{ $article->summary }}
    </x-slot>

    <x-slot name="category">
        <div class="cat-links">
            @foreach($article->categories as $category)
            <a href="{{ route('blog.category', $category->slug) }}" style="color:#694fcd;">{{ $category->name }}</a>
            @endforeach
        </div>
    </x-slot>

    <x-slot name="moreButton">
        <a href="{{ route('blog.show', $article->slug) }}" class="btn _flex _green _medium">
            More <span class="icon-next font-16 ml-2"></span>
        </a>
    </x-slot>
</x-article>
@endforeach
@elseif(!$heroArticle)
<x-blog.blog-no-results-found />
@endif