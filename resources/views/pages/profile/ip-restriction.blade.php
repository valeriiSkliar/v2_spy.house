@extends('layouts.main-app')

@section('page-content')

<h1 class="mb-25">{{ __('profile.ip_restriction.page_title') }}</h1>
<div id="ip-restriction-form-container" class="section profile-settings">
    <x-profile.info-v2-message status="ip-restriction-updated" :message="__('profile.ip_restriction.update_success')" />
    <x-profile.ip-restriction-form :user="$user" :ip_restrictions="$ip_restrictions" />
</div>
@endsection

@push('scripts')
<!-- IP Restriction auto-resize is handled by update-ip-restriction.js -->
@endpush

@push('scripts')
<x-profile.scripts />
@endpush