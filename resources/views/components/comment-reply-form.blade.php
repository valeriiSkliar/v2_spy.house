@props(['slug', 'comment_id', 'author'])

<div class="comment-form" data-reply>
    <div class="comment-form__author">
        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&amp;background=F3FAF7&color=3DC98A&bold=true" alt="{{ auth()->user()->name }}" class="avatar">
        <span class="font-16 font-weight-600">{{ auth()->user()->name }}</span>
    </div>
    <form action="{{ route('api.blog.reply.store', $slug) }}" method="POST">
        @csrf
        <input type="hidden" name="parent_id" value="{{ $comment_id }}">
        <div class="mb-1">
            <div class="d-block mb-10">
                Leave a comment in response to:
                <span><span class="icon-reply"></span> <a class="link font-weight-600">{{ $author }}</a></span>
            </div>
            <textarea name="content" required></textarea>
            <div class="text-danger mt-2 validation-error" style="display: none;"></div>
        </div>
        <div class="row">
            <div class="col-6 col-md-auto mt-3">
                <button type="submit" class="btn _flex _green w-100">Send</button>
            </div>
            <div class="col-6 col-md-auto mt-3">
                <button type="button" class="btn _flex _gray w-100 cancel-reply">Cancel</button>
            </div>
        </div>
    </form>
</div>