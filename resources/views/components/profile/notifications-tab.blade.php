<div class="pt-3">
    <div class="row _offset30">
        <div class="col-12 d-flex">
            <x-profile.notification-settings :user="$user" />

        </div>
    </div>
    <div class="col-12 col-md-auto mb-10">
        <x-profile.submit-button :label="__('profile.save_button')" />
    </div>
</div>