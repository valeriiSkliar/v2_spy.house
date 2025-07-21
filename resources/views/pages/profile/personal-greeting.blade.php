@extends('layouts.main-app')

@section('page-content')
<h1 class="mb-25">{{ __('profile.personal_greeting.page_title') }}</h1>

@php
$user = auth()->user();
$pendingUpdate = Cache::get('personal_greeting_update_code:' . $user->id);
$updateStep = $pendingUpdate ? 'confirmation' : 'initiation';
$updateMethod = $pendingUpdate['method'] ?? ($user->google_2fa_enabled ? 'authenticator' : 'email');
$updateExpiresAt = $pendingUpdate['expires_at'] ?? null;
$isExpired = $updateExpiresAt && now()->isAfter($updateExpiresAt);
@endphp

@if($isExpired)
<div class="message _bg _with-border font-weight-500">
    <span class="icon-warning font-18"></span>
    <div class="message__txt">
        {{ __('profile.security_settings.update_request_expired') }}
    </div>
</div>
@endif

<div class="section profile-settings">
    @if($updateStep != 'confirmation')
    <x-profile.personal-greeting-form :user="$user" :personalGreetingUpdatePending="false"
        :confirmation-method="$updateMethod" :authenticator-enabled="$user->google_2fa_enabled" />
    @else
    <x-profile.personal-greeting-confirmation-form :user="$user" :personalGreetingUpdatePending="true"
        :confirmation-method="$updateMethod" :authenticator-enabled="$user->google_2fa_enabled" />
    @endif

    @if (session('status'))
    <div class="message _bg _with-border font-weight-500 mt-4">
        <span class="icon-warning font-18"></span>
        <div class="message__txt">
            @switch(session('status'))
            @case('greeting-code-sent')
            {{ __('profile.security_settings.email_code_sent') }}
            @break
            @case('authenticator-required-for-greeting')
            {{ __('profile.security_settings.authenticator_required') }}
            @break
            @case('personal-greeting-updated')
            {{ __('profile.personal_greeting.personal_greeting_update_success') }}
            @break
            @case('personal-greeting-update-cancelled')
            {{ __('profile.personal_greeting.personal_greeting_update_cancelled') }}
            @break
            @default
            {{ session('status') }}
            @endswitch
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<x-profile.scripts />
@endpush