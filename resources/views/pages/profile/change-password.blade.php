@extends('layouts.main')

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

    <div class="section">
        <x-profile.change-password-form
            :confirmation-method="$updateMethod"
            :user="$user"
            :password-update-pending="$updateStep === 'confirmation'"
        />
        
        @if (session('status'))
            <div class="alert alert-info mb-25">
                @switch(session('status'))
                    @case('password-code-sent')
                        {{ __('profile.security_settings.password_code_sent') }}
                        @break
                    @case('authenticator-required')
                        {{ __('profile.security_settings.authenticator_required') }}
                        @break
                    @case('password-updated')
                        {{ __('profile.security_settings.password_updated') }}
                        @break
                    @case('password-update-cancelled')
                        {{ __('profile.security_settings.password_update_cancelled') }}
                        @break
                    @default
                        {{ session('status') }}
                @endswitch
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <x-profile.scripts />
@endsection