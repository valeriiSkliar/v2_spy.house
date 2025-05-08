@props(['user'])

<div class="pt-3">
    <div class="row _offset30">
        <div class="col-12 d-flex">
            <x-profile.notification-settings :user="$user" />
        </div>
    </div>

    <div class="col-12 col-md-auto mb-10">
        <x-profile.submit-button formId="notification-settings-form" :label="__('profile.save_button')" />
    </div>
    <x-profile.success-message status="notifications-updated" :message="__('profile.notification_settings.update_success')" />
</div>