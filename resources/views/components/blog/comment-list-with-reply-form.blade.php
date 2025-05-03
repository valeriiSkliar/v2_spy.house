@props(['comments' => [], 'article' => null])

@if($article)
<div class="comment-list">
    <div class="sep"></div>
    @auth
    <div class="comment-form">
        <x-blog.comment.reply-form :article="$article" />
    </div>
    @endauth
    @foreach($comments as $comment)
    <x-blog.comment :comment="$comment" :slug="$article['slug']" />
    @endforeach
</div>
@endif