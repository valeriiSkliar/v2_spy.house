@props(['article'])

<div class="comment-form__author">
    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&amp;background=F3FAF7&color=3DC98A&bold=true" alt="{{ auth()->user()->name }}" class="avatar">
    <span class="font-16 font-weight-600">{{ auth()->user()->name }}</span>
</div>

<form id="universal-comment-form" action="{{ route('blog.comment.store', $article['slug']) }}" method="POST" class="comment-ajax-form">
    @csrf
    
    <!-- Hidden input for parent_id, only used in reply mode -->
    <input type="hidden" name="parent_id" id="comment-parent-id" value="">
    
    <!-- Reply info section (hidden by default) -->
    <div class="reply-info mb-10" style="display: none;">
        <div class="d-block">
            Leave a comment in response to:
            <span><span class="icon-reply"></span> <a class="link font-weight-600" id="reply-to-author"></a></span>
        </div>
    </div>
    
    <div class="mb-1">
        <!-- Only shown for regular comments -->
        <label id="regular-comment-label" class="d-block mb-10 regular-comment-label">Write the text of the comment</label>
        
        <textarea name="content" required></textarea>
        @error('content')
        <div class="text-danger mt-2">{{ $message }}</div>
        @enderror
    </div>
    
    <div class="row">
        <div class="col-6 col-md-auto mt-3">
            <button type="submit" class="btn _flex _green w-100">Send</button>
        </div>
        
        <!-- Cancel button (hidden by default) -->
        <div class="col-6 col-md-auto mt-3 cancel-reply-container" style="display: none;">
            <a href="javascript:void(0)" class="btn _flex _gray w-100 cancel-reply">Cancel</a>
        </div>
    </div>
</form>