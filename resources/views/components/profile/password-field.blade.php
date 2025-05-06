<div class="form-item mb-20">
    <div class="row justify-content-between align-items-center mb-10">
        <div class="col-auto"><label class="d-block">{{ __('profile.personal_info.password_label') }}</label></div>
        <div class="col-auto"><a href="{{ route('profile.change-password') }}" class="link">{{ __('profile.personal_info.change_password_link') }}</a></div>
    </div>
    <div class="form-password">
        <input type="password" class="input-h-57" data-pass="pass-1" value="••••••••" disabled>
        <button type="button" class="btn-icon switch-password" data-pass-switch="pass-1">
            <span class="icon-view-off"></span>
            <span class="icon-view-on"></span>
        </button>
    </div>
</div>