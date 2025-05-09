@php
    $activeTab = request()->query('tab', 'personal');
@endphp
<ul class="tubs _mob100">
    <li class="flex-grow-1">
        <a href="{{ route('profile.settings', ['tab' => 'personal']) }}" data-tub="personal" data-group="profile" class="{{ $activeTab === 'personal' ? 'active' : '' }}">
            <span class="icon-personal"></span> {{ __('profile.tabs.personal') }}
        </a>
    </li>
    <li class="flex-grow-1">
        <a href="{{ route('profile.settings', ['tab' => 'security']) }}" data-tub="security" data-group="profile" class="{{ $activeTab === 'security' ? 'active' : '' }}">
            <span class="icon-security"></span> {{ __('profile.tabs.security') }}
        </a>
    </li>
    <li class="flex-grow-1">
        <a href="{{ route('profile.settings', ['tab' => 'notifications']) }}" data-tub="notifications" data-group="profile" class="{{ $activeTab === 'notifications' ? 'active' : '' }}">
            <span class="icon-email"></span> {{ __('profile.tabs.notifications') }}
        </a>
    </li>
</ul>