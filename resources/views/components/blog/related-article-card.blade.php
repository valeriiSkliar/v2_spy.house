@props(['article'])

<div class="article _similar">
    <a href="{{ route('blog.show', $article->slug) }}" class="article__thumb thumb">
        <img src="{{ $article->featured_image }}" alt="{{ $article->getTranslation('title', app()->getLocale()) }}">
    </a>
    <a href="{{ route('blog.show', $article->slug) }}" class="article__title">{{ $article->getTranslation('title', app()->getLocale()) }}</a>
</div>
