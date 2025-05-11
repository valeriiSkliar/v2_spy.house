@props(['user', 'confirmationMethod', 'passwordUpdatePending' => false])
<form id="change-password-form" method="POST" action="{{ $passwordUpdatePending ? route('profile.confirm-password-update') : route('profile.initiate-password-update') }}" class="profile-form">
    @csrf
    @if(!$passwordUpdatePending)
        <div class="row _offset20 mb-10">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="form-item mb-20">
                    <label class="d-block mb-10">{{ __('profile.security_settings.current_password_label') }}</label>
                    <div class="form-password">
                        <input type="password" name="current_password" class="input-h-57" data-pass="pass-1" value="">
                        <button type="button" class="btn-icon switch-password" data-pass-switch="pass-1">
                            <span class="icon-view-off"></span>
                            <span class="icon-view-on"></span>
                        </button>
                    </div>
                    @error('current_password')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <x-profile.form-field name="password" type="password" :label="__('profile.security_settings.new_password_label')" />
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <x-profile.form-field name="password_confirmation" type="password" :label="__('profile.security_settings.new_password_confirmation_label')" />
            </div>
            <div data-confirmation-method="{{ $confirmationMethod }}" class="col-12 col-md-6 col-lg-4">
                <input type="hidden" name="confirmation_method" value="{{ $confirmationMethod }}">
                {{-- @if ( !$authenticatorEnabled )
                    <x-profile.select-field 
                        name="confirmation" 
                        :label="__('profile.security_settings.confirmation_method_label')"
                        value="{{ __('profile.security_settings.confirmation_methods.' . $confirmationMethod) }}" 
                    :options="[
                        __('profile.security_settings.confirmation_methods.authenticator'),
                        __('profile.security_settings.confirmation_methods.email'),
                    ]" 
                        data-confirmation="true"
                    />
                @endif --}}
            </div>
        </div>
        <x-profile.submit-button formId="change-password-form" :label="__('profile.security_settings.next_button')" />
    @else
        <div class="row _offset20 mb-20 pt-4">
            @if ($confirmationMethod === 'authenticator')
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
            <x-profile.submit-button formId="change-password-form" :label="__('profile.security_settings.confirm_button')" />
            <a href="{{ route('profile.cancel-password-update') }}" class="btn btn-outline-danger">
                {{ __('profile.security_settings.cancel_button') }}
            </a>
        </div>
    @endif
    <x-profile.success-message status="password-updated" :message="__('profile.security_settings.password_updated')" />
</form>