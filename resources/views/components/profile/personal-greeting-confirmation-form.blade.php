@props(['user', 'confirmationMethod', 'personalGreetingUpdatePending', 'authenticatorEnabled'])

<div id="personal-greeting-form-container">
    <form id="personal-greeting-form" action="{{ route('api.profile.confirm-personal-greeting-update') }}" method="POST"
        class="pt-3">
        @csrf
        <div class="row _offset20 mb-20 ">
            @if($confirmationMethod === 'authenticator')
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
        <div class="d-flex gap-3 confirmation-method__btn">
            <x-profile.submit-button formId="personal-greeting-form"
                :label="__('profile.security_settings.confirm_button')" />
            <div class="mb-20">
                <a href="{{ route('api.profile.cancel-personal-greeting-update') }}" class="btn _flex _red _big"
                    data-action="cancel-personal-greeting">
                    {{ __('profile.security_settings.cancel_button') }}
                </a>
            </div>
        </div>
    </form>
</div>