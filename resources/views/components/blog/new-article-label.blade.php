@props(['article'])

@if($article->created_at->diffInDays(now()) < 3) <span class="article-label">{{ __('blogs.new_article_label') }}</span>
    @endif