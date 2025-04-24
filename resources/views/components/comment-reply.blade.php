<!-- resources/views/components/comment-reply.blade.php -->
@props(['reply', 'slug'])

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