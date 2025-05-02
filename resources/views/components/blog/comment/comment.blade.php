@props(['comment', 'slug'])

<div class="comment">
    <div class="comment-main">
        <img src="https://ui-avatars.com/api/?name={{ urlencode($comment['author_name']) }}&amp;background=F3FAF7&color=3DC98A&bold=true" alt="{{ $comment['author_name'] }}" class="avatar">
        <div class="comment-content">
            <header>
                <h4>{{ $comment['author_name'] }}</h4>
                <time>{{ \Carbon\Carbon::parse($comment['created_at'])->format('d.m.Y') }}</time>
                @auth
                <a 
                    {{-- href="{{ route('api.blog.get-reply-form', ['slug' => $slug, 'comment_id' => $comment['id']]) }}"  --}}
                    data-comment-id="{{ $comment['id'] }}" 
                    data-author-name="{{ $comment['author_name'] }}" 
                    class="reply-btn">
                    Reply
                </a>
                @endauth
            </header>
            <p>{{ $comment['content'] }}</p>
        </div>
    </div>

    @if(!empty($comment['replies']))
    <div class="replies">
        @foreach($comment['replies'] as $reply)
        <div class="reply">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($reply['author_name']) }}&amp;background=F3FAF7&color=3DC98A&bold=true" alt="{{ $reply['author_name'] }}" class="avatar">
            <div class="reply-content">
                <header>
                    <h4>{{ $reply['author_name'] }}</h4>
                    <time>{{ \Carbon\Carbon::parse($reply['created_at'])->format('d.m.Y') }}</time>
                </header>
                <p>{{ $reply['content'] }}</p>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>