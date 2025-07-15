@props(['user', 'confirmationMethod', 'passwordUpdatePending' => false])
<form id="change-password-form" method="POST"
    action="{{ $passwordUpdatePending ? route('profile.confirm-password-update') : route('profile.initiate-password-update') }}"
    class="profile-form">
    @csrf
    @if(!$passwordUpdatePending)
    <div class="col _offset20 mb-10">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="form-item mb-20">
                <label class="d-block mb-10">{{ __('profile.security_settings.current_password_label') }}</label>
                <div class="form-password">
                    <input autofocus tabindex="1" readonly onfocus="this.removeAttribute('readonly');" type="password"
                        name="current_password" class="input-h-57" data-pass="pass-1" value="">
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
            <x-profile.password-field tabindex="2" name="password" type="password"
                :label="__('profile.security_settings.new_password_label')" data-pass-switch="pass-2" />
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.password-field tabindex="3" name="password_confirmation" type="password"
                :label="__('profile.security_settings.new_password_confirmation_label')" data-pass-switch="pass-3"
                :value="old('password_confirmation')" />
        </div>
        <div data-confirmation-method="{{ $confirmationMethod }}" class="col-12 col-md-6 col-lg-4">
            <input type="hidden" name="confirmation_method" value="{{ $confirmationMethod }}">
        </div>
    </div>
    <x-profile.submit-button :action="'cansel-email-update'" formId="change-password-form"
        :label="__('profile.next_button')" />
    @else
    <div class="row _offset20 mb-20 ">
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
            <x-profile.info-message :title="__('profile.2fa.info_message_title_authenticator')"
                :description="__('profile.2fa.info_message_description_authenticator')" />
        </div>
    </div>
    <div class="d-flex gap-3">
        <x-profile.submit-button class="mr-4" formId="change-password-form" :label="__('profile.confirm_button')" />
        <div class="mb-20">

            <button data-action="cancel-password" type="button" class="btn _flex _red _big">
                {{ __('profile.cancel_button') }}
            </button>
        </div>
    </div>
    @endif
    <x-profile.success-message status="password-updated" :message="__('profile.password_updated')" />
</form>