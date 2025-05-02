@props(['article'])

<div class="article__thumb thumb">
    <img src="{{ $article->featured_image }}" alt="{{ $article->title }}">
</div>
