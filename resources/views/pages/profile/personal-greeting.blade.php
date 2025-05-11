@extends('layouts.main')

@section('page-content')
    <h1 class="mb-25">{{ __('profile.personal_greeting_page_title') }}</h1>
    
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
            <form id="personal-greeting-form" action="{{ route('profile.initiate-personal-greeting-update') }}" method="POST" class="pt-3">
                @csrf
                <div class="row _offset20 mb-10">
                    <div class="col-12 col-md-6">
                        <x-profile.form-field 
                            name="personal_greeting" 
                            type="text" 
                            :label="__('profile.personal_greeting_label')" 
                            :value="old('personal_greeting', $user->personal_greeting)" 
                        />
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="form-item mb-20">
                            <label for="confirmation_method" class="d-block mb-15">{{ __('profile.security_settings.confirmation_method_label') }}</label>
                            <select name="confirmation_method" id="confirmation_method" class="form-control input-h-57">
                                @if($user->google_2fa_enabled)
                                <option value="authenticator">{{ __('profile.security_settings.confirmation_methods.authenticator') }}</option>
                                @endif
                                <option value="email">{{ __('profile.security_settings.confirmation_methods.email') }}</option>
                            </select>
                            @error('confirmation_method')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
                <x-profile.submit-button formId="personal-greeting-form" :label="__('profile.security_settings.next_button')" />
            </form>
        @else
            <form id="confirmation-form" action="{{ route('profile.confirm-personal-greeting-update') }}" method="POST" class="pt-3">
                @csrf
                <div class="row _offset20 mb-20 pt-4">
                    @if($updateMethod === 'authenticator')
                        <div class="col-12 col-md-6 col-lg-4">
                            <x-profile.authenticator-code />
                        </div>
                    @else
                        <div class="col-12 col-md-6 col-lg-4">
                            <x-profile.email-code />
                        </div>
                    @endif
                    <div class="col-12 col-md-6 col-lg-4">
                        <x-profile.info-message
                            :title="__('profile.2fa.info_message_title_authenticator')"
                            :description="__('profile.2fa.info_message_description_authenticator')"
                        />
                    </div>
                </div>
                <div class="d-flex gap-3">
                    <x-profile.submit-button formId="confirmation-form" :label="__('profile.security_settings.confirm_button')" />
                    <a href="{{ route('profile.cancel-personal-greeting-update') }}" class="btn btn-outline-danger">
                        {{ __('profile.security_settings.cancel_button') }}
                    </a>
                </div>
            </form>
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
                            {{ __('profile.personal_greeting_update_success') }}
                        @break
                        @case('personal-greeting-update-cancelled')
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