@props(['user'])
<form id="notification-settings-form" data-api-endpoint="{{ route('api.profile.update-notifications') }}">
    @csrf
    <div class="row _offset20 mt-3">
        <div class="col-12 col-md-auto mb-10">
            <label class="checkbox-btn">
                <input type="checkbox" name="notification_settings[system]" value="1" {{ $user->notification_settings['system'] ?? false ? 'checked' : '' }}
                    class="notification-setting-toggle">
                <span class="checkbox-btn__content">
                    <span class="checkbox-btn__icon icon-check-circle"></span>
                    <span class="checkbox-btn__text">{{ __('profile.notification_settings.system_messages_label') }}</span>
                </span>
            </label>
        </div>
    </div>
</form>