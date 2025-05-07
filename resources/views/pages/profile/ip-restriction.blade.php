@extends('layouts.main')

@section('page-content')
    <h1 class="mb-25">{{ __('profile.ip_restriction.page_title') }}</h1>
    <div class="section profile-settings">
        <form action="{{ route('profile.update-ip-restriction') }}" method="POST" class="pt-3">
            @csrf
            @method('PUT')
            <x-profile.info-message>
                {{ __('profile.ip_restriction.info') }}
            </x-profile.info-message>
            <div class="mb-20">
                <label class="d-block mb-10">{{ __('profile.ip_restriction.allowed_ip_addresses_label') }}</label>
                <textarea name="ip_restrictions" class="input-h-57" rows="5" placeholder="{{ __('profile.ip_restriction.allowed_ip_addresses_placeholder') }}"></textarea>
                @error('ip_restrictions')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <div class="mb-20">
                <label class="d-block mb-10">{{ __('profile.ip_restriction.your_password_label') }}</label>
                <input type="password" name="password" class="input-h-57" autocomplete="current-password">
                @error('password')
                    <span class="text-danger">{{ $message }}</span>
                @enderror
            </div>
            <x-profile.success-message 
                status="ip-restriction-updated" 
                :message="__('profile.ip_restriction.update_success')" 
            />
            <x-profile.submit-button :label="__('profile.save_button')" />
        </form>
    </div>
@endsection 