@extends('layouts.main-app')

@section('page-content')
<h1 class="mb-25">{{ __('profile.security_settings.change_password_title') }}</h1>

@php
$user = auth()->user();
$pendingUpdate = Cache::get('password_update_code:' . $user->id);
$updateStep = $pendingUpdate ? 'confirmation' : 'initiation';
$updateMethod = $confirmationMethod ;
$updateExpiresAt = $pendingUpdate['expires_at'] ?? null;
$isExpired = $updateExpiresAt && now()->isAfter($updateExpiresAt);
@endphp

@if($isExpired)
<div class="alert alert-warning mb-25">
    {{ __('profile.security_settings.update_request_expired') }}
</div>
@endif

<div id="change-password-form-container" class="section">
    <x-profile.change-password-form :confirmation-method="$updateMethod" :user="$user"
        :password-update-pending="$updateStep === 'confirmation'" />
</div>
@endsection

@push('scripts')
<x-profile.scripts />
@endpush