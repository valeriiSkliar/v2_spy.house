@props(['article', 'currentCategory'])

<div class="article__row">
    <div class="article__cat">
        <div class="cat-links">
            <a href="{{ route('blog.category', $currentCategory->slug) }}" data-color="{{ $currentCategory->color }}">{{ $currentCategory->name }}</a>
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
