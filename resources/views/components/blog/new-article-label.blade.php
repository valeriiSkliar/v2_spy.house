@props(['article'])

@if($article->created_at->diffInDays(now()) < 30)
    <span class="article-label">New</span>
@endif