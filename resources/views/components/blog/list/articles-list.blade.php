@props(['articles', 'heroArticle'])

{{-- <div class="articles-grid"> --}}
    @if($heroArticle)
    <x-blog.hero-article :heroArticle="$heroArticle" />
    @endif

    @if($articles->count() > 0)
    @foreach($articles as $article)
    <x-article>
        <x-slot name="thumb">
            <a href="{{ route('blog.show', $article->slug) }}" class="article__thumb thumb">
                <img src="{{ $article->featured_image }}" alt="{{ $article->title }}">
            </a>
        </x-slot>

        <x-slot name="info">
            <div class="article-info">
                <div class="article-info__item icon-date">{{ $article->created_at->format('d.m.y / H:i') }}</div>
                <a href="{{ route('blog.show', $article->slug) }}#comments" class="article-info__item icon-comment1">
                    {{ $article->comments_count ?? 0 }}
                </a>
                <div class="article-info__item icon-view">{{ $article->views_count ?? 0 }}</div>
                <div class="article-info__item icon-rating">{{ $article->average_rating ?? 0 }}</div>
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
    @elseif(!$heroArticle)
    {{-- Show no results only if there's no hero article either --}}
    <x-blog.blog-no-results-found />
    @endif
    {{--
</div> --}}