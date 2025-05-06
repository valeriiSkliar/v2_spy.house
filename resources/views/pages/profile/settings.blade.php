@extends('layouts.main')

@section('page-content')
    <h1 class="mb-25">{{ __('profile.settings_page_title') }}</h1>
    <div class="section profile-settings">
        <x-profile.tab-nav :user="$user" />
        <x-profile.tab-content :user="$user" :scopes="$scopes" :api_token="$api_token" />
    </div>
@endsection

@section('scripts')
    <x-profile.scripts />
@endsection