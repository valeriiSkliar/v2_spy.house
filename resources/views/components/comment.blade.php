@props(['comment', 'slug'])

<div class="comment">
    <div class="comment-main">
        <img src="https://ui-avatars.com/api/?name={{ urlencode($comment['author']) }}&amp;background=F3FAF7&color=3DC98A&bold=true" alt="{{ $comment['author'] }}" class="avatar">
        <div class="comment-content">
            <header>
                <h4>{{ $comment['author'] }}</h4>
                <time>{{ $comment['date'] }}</time>
                @auth
                <a href="{{ route('blog.reply', ['slug' => $slug, 'comment_id' => $comment['id']]) }}" class="reply-btn">Reply</a>
                @endauth
            </header>
            <p>{{ $comment['content'] }}</p>
        </div>
    </div>

    @if(!empty($comment['replies']))
    <div class="replies">
        @foreach($comment['replies'] as $reply)
        <div class="reply">
            <img src="https://ui-avatars.com/api/?name={{ urlencode($reply['author']) }}&amp;background=F3FAF7&color=3DC98A&bold=true" alt="{{ $reply['author'] }}" class="avatar">
            <div class="reply-content">
                <header>
                    <h4>{{ $reply['author'] }}</h4>
                    <time>{{ $reply['date'] }}</time>
                </header>
                <p>{{ $reply['content'] }}</p>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>