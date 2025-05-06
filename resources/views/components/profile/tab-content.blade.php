@props(['user', 'api_token', 'scopes'])
<div class="tubs-content">
    <div class="tubs-content__item active" data-tub="personal" data-group="profile">
        <x-profile.personal-settings-form :user="$user" :api_token="$api_token" :scopes="$scopes" />
    </div>
    <div class="tubs-content__item" data-tub="security" data-group="profile">
        <x-profile.security-settings />
    </div>
    <div class="tubs-content__item" data-tub="notifications" data-group="profile">
        <x-profile.notifications-tab :user="$user" />
    </div>
</div>