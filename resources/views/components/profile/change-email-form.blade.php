@props(['user', 'confirmationMethod', 'emailUpdatePending', 'authenticatorEnabled'])
<form id="change-email-form" method="POST" action="{{ $emailUpdatePending ? route('profile.confirm-email-update') : route('profile.initiate-email-update') }}" class="profile-form">
    @csrf
    @if(!$emailUpdatePending)
        <div class="row _offset20 mb-10">
            <div class="col-12 col-md-6 col-lg-4">
                <input type="hidden" name="current_email" value="{{ $user->email }}">
                <x-profile.form-field 
                    name="current_email" 
                    type="email" 
                    :value="$user->email" 
                    :disabled="true" 
                    :label="__('profile.security_settings.current_email_label')" 
                />
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <x-profile.form-field name="new_email" type="email" :label="__('profile.security_settings.new_email_label')" />
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <x-profile.form-field name="password" type="password" :label="__('profile.security_settings.password_label')" />
            </div>
            <div data-confirmation-method="{{ $confirmationMethod }}" class="col-12 col-md-6 col-lg-4">
                <input type="hidden" name="confirmation_method" value="{{ $confirmationMethod }}">

            </div>
        </div>
        <x-profile.submit-button formId="change-email-form" :label="__('profile.security_settings.next_button')" />
    @else
        <div class="row _offset20 mb-20 pt-4">
            @if ( $authenticatorEnabled || $confirmationMethod === 'authenticator' )
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
            <x-profile.submit-button formId="change-email-form" :label="__('profile.security_settings.confirm_button')" />
            <div class="mb-20">
                <a href="{{ route('profile.cancel-email-update') }}" class="btn _flex _border-red _big">
                    {{ __('profile.security_settings.cancel_button') }}
                </a>
            </div>
        </div>
    @endif
    <x-profile.success-message status="email-updated" :message="__('profile.security_settings.email_updated')" />
</form>

<style>
.btn._border-red {
  color: #C93D3D;
  background: transparent;
  border: 1px solid #C93D3D;
}
.btn._border-red:hover {
  background: #C93D3D;
  color: #fff;
}
</style>