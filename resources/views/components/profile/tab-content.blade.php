@props(['user', 'api_token', 'scopes', 'experiences', 'displayDefaultValues', 'activeTab'])
@php
$activeTab = request()->query('tab', 'personal');
@endphp
<div class="tubs-content">
    <div class="tubs-content__item {{ $activeTab === 'personal' ? 'active' : '' }}" data-tub="personal"
        data-group="profile">
        <x-profile.personal-settings-form :user="$user" :api_token="$api_token" :scopes="$scopes"
            :experiences="$experiences" :active-tab="$activeTab" :display-default-values="$displayDefaultValues" />
    </div>
    <div class="tubs-content__item {{ $activeTab === 'security' ? 'active' : '' }}" data-tub="security"
        data-group="profile">
        <x-profile.security-settings />
    </div>
    <div class="tubs-content__item {{ $activeTab === 'notifications' ? 'active' : '' }}" data-tub="notifications"
        data-group="profile">
        <x-profile.notifications-tab :user="$user" />
    </div>
</div>