@extends('layouts.main')

@section('page-content')
    <h1 class="mb-25">Change Email</h1>
    
    @php
        $user = auth()->user();
        $pendingUpdate = Cache::get('email_update_code:' . $user->id);
        $updateStep = $pendingUpdate ? 'confirmation' : 'initiation';
        $updateMethod = ($user->google_2fa_enabled ? 'authenticator' : 'email');
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

    <div class="section">
        <x-profile.change-email-form 
            :confirmation-method="$updateMethod"
            :user="$user"
            :email-update-pending="$updateStep === 'confirmation'"
        />
        @if (session('status'))
            <div class="message _bg _with-border font-weight-500">
                <span class="icon-warning font-18"></span>
                <div class="message__txt">
                    @switch(session('status'))
                        @case('email-code-sent')
                            {{ __('profile.security_settings.email_code_sent') }}
                        @break
                    @case('authenticator-required')
                        {{ __('profile.security_settings.authenticator_required') }}
                        @break
                    @case('email-updated')
                        {{ __('profile.security_settings.email_updated') }}
                        @break
                    @case('email-update-cancelled')
                        {{ __('profile.security_settings.email_update_cancelled') }}
                        @break
                    @default
                        {{ session('status') }}
                    @endswitch
                </div>
            </div>
        @endif
    </div>
@endsection

@section('scripts')
    <x-profile.scripts />
@endsection 