@props(['article', 'isReply' => false, 'replyTo' => null])


<div class="comment-form">
    <div class="comment-form__author">
        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&amp;background=F3FAF7&color=3DC98A&bold=true" alt="{{ auth()->user()->name }}" class="avatar">
        <span class="font-16 font-weight-600">{{ auth()->user()->name }}</span>
    </div>
    
    @if(!$isReply)
    <form action="{{ route('blog.comment.store', $article['slug']) }}" method="POST">
        @csrf

        <div class="mb-1">
            <label class="d-block mb-10">Write the text of the comment</label>
            <textarea name="content" required></textarea>
            @error('content')
            <div class="text-danger mt-2">{{ $message }}</div>
            @enderror
        </div>
        <div class="row">
            <div class="col-6 col-md-auto mt-3">
                <button type="submit" class="btn _flex _green w-100">Send</button>
            </div>
        </div>
    </form>
    @else
    <form action="{{ route('blog.reply.store', $article['slug']) }}" method="POST">
        @csrf
        <input type="hidden" name="parent_id" value="{{ $replyTo['id'] }}">
        <div class="mb-1">
            <div class="d-block mb-10">
                Leave a comment in response to:
                <span><span class="icon-reply"></span> <a class="link font-weight-600">{{ $replyTo['author'] }}</a></span>
            </div>
            <textarea name="content" required></textarea>
            @error('content')
            <div class="text-danger mt-2">{{ $message }}</div>
            @enderror
        </div>
        <div class="row">
            <div class="col-6 col-md-auto mt-3">
                <button type="submit" class="btn _flex _green w-100">Send</button>
            </div>
            <div class="col-6 col-md-auto mt-3">
                <a href="{{ route('blog.show', $article['slug']) }}" class="btn _flex _gray w-100">Cancel</a>
            </div>
        </div>
    </form>
    @endif
</div>
