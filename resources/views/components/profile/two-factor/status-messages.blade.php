<!-- Status messages -->
@if (session('status') == '2fa-enabled')
<div class="message _bg _with-border font-weight-500">
    <span class="icon-warning font-18"></span>
    <div class="message__txt">
        {{ __('profile.2fa.status_enabled') }}
    </div>
</div>
@endif
@if (session('status') == '2fa-disabled')
<div class="message _bg _with-border font-weight-500">
    <span class="icon-warning font-18"></span>
    <div class="message__txt">
        {{ __('profile.2fa.status_disabled') }}
    </div>
</div>
@endif
