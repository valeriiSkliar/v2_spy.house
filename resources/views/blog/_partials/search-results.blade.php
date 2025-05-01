@if ($total > 0)
<h2>Search Results for "{{ $query }}"</h2>

@foreach ($articles as $article)
<div class="article">
    <a href="{{ url('/blog/' . $article['slug']) }}" class="article__thumb thumb">
        {{-- Use asset() helper for images --}}
        <img src="{{ $article['image'] }}" alt="{{ $article['title'] }}">
    </a>
    <div class="article-info">
        <div class="article-info__item icon-date">{{ $article['date'] }}</div>
        {{-- Ensure comments_count exists --}}
        <a href="{{ url('/blog/' . $article['slug'] . '#comments') }}" class="article-info__item icon-comment1">{{ $article['comments_count'] ?? 0 }}</a>
        {{-- Ensure views exists --}}
        <div class="article-info__item icon-view">{{ $article['views'] ?? 0 }}</div>
        {{-- Ensure rating exists --}}
        <div class="article-info__item icon-rating">{{ $article['rating'] ?? 0 }}</div>
    </div>
    <a href="{{ url('/blog/' . $article['slug']) }}" class="article__title">{{ $article['title'] }}</a>
    {{-- Ensure category exists and has needed keys --}}
    @if (!empty($article['category']))
    <div class="cat-links">
        <a href="{{ url('/blog/category/' . ($article['category']['slug'] ?? 'uncategorized')) }}" style="color:{{ $article['category']['color'] ?? '#cccccc' }};">{{ $article['category']['name'] ?? 'Uncategorized' }}</a>
    </div>
    @endif
</div>
@endforeach

@if ($total > count($articles)) {{-- Show 'View all' only if there are more results than shown --}}
<div class="text-center mt-4 mb-4">
    <a href="{{ url('/blog/search?q=' . urlencode($query)) }}" class="btn _flex _green _medium">View all {{ $total }} results</a>
</div>
@endif

@else
<div class="text-center mt-4 mb-4">
    <h2>No results found for "{{ $query }}"</h2>
    <p>Try different keywords or check out our categories below.</p>
</div>
@endif