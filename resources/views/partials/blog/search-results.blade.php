@if ($articles->count() > 0)
<x-blog.hero-article :heroArticle="$articles->first()" />

@else
<x-blog.blog-no-results-found :query="$query" />
@endif


@if ($articles->count() > 1)
<x-blog.articles-list :articles="$articles->skip(1)" />
@endif



@if ($total > count($articles))
<div class="text-center mt-4 mb-4">
    <a href="{{ url('/blog/search?q=' . urlencode($query)) }}" class="btn _flex _green _medium">{{
        __('blog.view_all_results', ['total' => $total]) }}</a>
</div>
@endif