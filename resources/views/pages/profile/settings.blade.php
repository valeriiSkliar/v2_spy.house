@extends('layouts.main')

@section('page-content')
    <h1 class="mb-25">{{ __('profile.settings_page_title') }}</h1>
    @if ($user->personal_greeting && $user->personal_greeting !== '')
        <p>
            {{ $user->personal_greeting }}
        </p>
    @endif
    <div class="section profile-settings">
        <x-profile.tab-nav :user="$user" :active-tab="$activeTab" />
        <x-profile.tab-content 
            :user="$user" 
            :scopes="$scopes" 
            :api_token="$api_token" 
            :experiences="$experiences" 
            :google_2fa_enabled="$user->google_2fa_enabled"
            :active-tab="$activeTab"
        />
    </div>
@endsection

@section('scripts')
    <x-profile.scripts />
@endsection