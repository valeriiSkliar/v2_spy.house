@props(['user', 'confirmationMethod', 'emailUpdatePending', 'authenticatorEnabled'])
<form autocomplete="off" id="change-email-form" method="POST"
    data-action="{{ $emailUpdatePending ? route('api.profile.confirm-email-update') : route('api.profile.initiate-email-update') }}"
    action="{{ $emailUpdatePending ? route('api.profile.confirm-email-update') : route('api.profile.initiate-email-update') }}"
    class="profile-form">
    @csrf
    @if(!$emailUpdatePending)
    <div class="row-col _offset20 mb-30">
        <div class="col-12 col-md-6 col-lg-4 pl-0">
            <input type="hidden" name="current_email" value="{{ $user->email }}">
            <x-profile.form-field name="current_email" autocomplete="nope" type="email" :value="$user->email"
                :disabled="true" :label="__('profile.security_settings.current_email_label')" />
        </div>
        <div class="col-12 col-md-6 col-lg-4 pl-0">
            <x-profile.form-field tabindex="1" autofocus autocomplete="nope" name="new_email" type="email"
                :label="__('profile.security_settings.new_email_label')" />
        </div>
        <div class="col-12 col-md-6 col-lg-4 pl-0">
            <x-profile.form-field tabindex="2" autocomplete="nope" name="password" type="password"
                :label="__('profile.password_label')" />
        </div>
        <div data-confirmation-method="{{ $confirmationMethod }}" class="col-12 col-md-6 col-lg-6">
            <input type="hidden" name="confirmation_method" value="{{ $confirmationMethod }}">

        </div>
    </div>
    <x-profile.submit-button formId="change-email-form" :label="__('profile.next_button')" />
    @else
    <div class="row _offset20 mb-20 ">
        @if ( $authenticatorEnabled || $confirmationMethod === 'authenticator' )
        <div class="col-12 col-md-6 col-lg-4 pl-0">
            <x-profile.authenticator-code />
        </div>
        @else
        <div class="col-12 col-md-6 col-lg-4 pl-0">
            <x-profile.email-code />
        </div>
        @endif
        <div class="col-12 col-md-6 col-lg-4 pl-0">
            <x-profile.info-message :title="__('profile.2fa.info_message_title_authenticator')"
                :description="__('profile.2fa.info_message_description_authenticator')" />
        </div>
    </div>
    <div class="d-flex gap-3">
        <x-profile.submit-button class="mr-4" formId="change-email-form" :label="__('profile.confirm_button')" />
        <div class="mb-20">
            <button data-action="cancel-email-update" type="button" class="btn _flex _red _big">
                {{ __('profile.cancel_button') }}
            </button>
        </div>
    </div>
    @endif
    <x-profile.success-message status="email-updated" :message="__('profile.email_updated')" />
</form>