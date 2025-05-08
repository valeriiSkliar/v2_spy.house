<div class="confirmation-method">
    <figure class="confirmation-method__icon"><img width="29" height="23" src="/img/notification.svg" alt=""></figure>
    <div class="row justify-content-between align-items-center">
        <div class="col-12 col-lg-auto">
            <div class="confirmation-method__title">{{ __('profile.notification_settings.email_title') }}</div>
            <div class="confirmation-method__desc">{!! __('profile.notification_settings.email_description', ['email' => '<strong>' . $user->email . '</strong>']) !!}</div>
        </div>
        <div class="col-12 col-lg-auto">
            <div class="confirmation-method__btns">
                <form action="{{ route('profile.update-notifications') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <x-profile.success-message status="notifications-updated" :message="__('profile.notification_settings.update_success')" />
                    <div class="row _offset20 mt-3">
                        <div class="col-12 col-md-auto mb-10">
                            <label class="checkbox-btn">
                                <input type="checkbox" name="notifications[]" value="system" {{ in_array('system', old('notifications', $user->notification_settings ?? ['system'])) ? 'checked' : '' }}>
                                <span class="checkbox-btn__content">
                                    <span class="checkbox-btn__icon icon-check-circle"></span>
                                    <span class="checkbox-btn__text">{{ __('profile.notification_settings.system_messages_label') }}</span>
                                </span>
                            </label>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>