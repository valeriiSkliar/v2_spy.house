@if(session('status') === $status)
    <div class="message _bg _with-border _green mb-15">
        <span class="icon-check font-18"></span>
        <div class="message__txt">{{ $message }}</div>
    </div>
@endif