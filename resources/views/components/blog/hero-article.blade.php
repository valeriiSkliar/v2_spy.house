@props(['heroArticle'])
<x-article :big="true" :fullWidth="true">
    <x-slot name="thumb">
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