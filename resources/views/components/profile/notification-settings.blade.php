<div class="confirmation-method">
    <figure class="confirmation-method__icon"><img width="29" height="23" src="/img/notification.svg" alt=""></figure>
    <div class="row justify-content-between">
        <div class="col-12 col-lg-auto">
            <div class="confirmation-method__title">{{ __('profile.notification_settings.email_title') }}</div>
            <div class="confirmation-method__desc">{!! __('profile.notification_settings.email_description', ['email' => '<strong>' . $user->email . '</strong>']) !!}</div>
        </div>
        <div class="col-12 col-lg-auto">
            <div class="confirmation-method__btns">
                <x-profile.notification-settings-form :user="$user" />
            </div>
        </div>
    </div>
</div>