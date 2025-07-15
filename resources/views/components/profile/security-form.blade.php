@props([
'formType' => 'password', // 'password', 'email', 'greeting'
'user',
'confirmationMethod',
'isPending' => false,
'authenticatorEnabled' => false
])

@php
$formConfig = [
'password' => [
'initiate_route' => 'profile.initiate-password-update',
'confirm_route' => 'profile.confirm-password-update',
'cancel_route' => 'profile.cancel-password-update',
'form_id' => 'change-password-form'
],
'email' => [
'initiate_route' => 'api.profile.initiate-email-update',
'confirm_route' => 'api.profile.confirm-email-update',
'cancel_route' => 'api.profile.cancel-email-update',
'form_id' => 'change-email-form'
],
'greeting' => [
'initiate_route' => 'api.profile.initiate-personal-greeting-update',
'confirm_route' => 'api.profile.confirm-personal-greeting-update',
'cancel_route' => 'api.profile.cancel-personal-greeting-update',
'form_id' => 'greeting-form'
]
];

$config = $formConfig[$formType];
$actionRoute = $isPending ? $config['confirm_route'] : $config['initiate_route'];
@endphp

<form id="{{ $config['form_id'] }}" method="POST" @if($formType==='email' || $formType==='greeting' )
    data-action="{{ route($actionRoute) }}" @endif action="{{ route($actionRoute) }}" class="profile-form"
    @if($formType==='email' ) autocomplete="off" @endif>
    @csrf

    @if(!$isPending)
    {{-- Initial form fields --}}
    <div class="col _offset20 mb-10">
        @if($formType === 'password')
        {{-- Password fields --}}
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.unified-input-field name="current_password" type="password"
                :label="__('profile.security_settings.current_password_label')" :showPasswordToggle="true" tabindex="1"
                autofocus />
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.unified-input-field name="password" type="password"
                :label="__('profile.security_settings.new_password_label')" :showPasswordToggle="true" tabindex="2" />
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.unified-input-field name="password_confirmation" type="password"
                :label="__('profile.security_settings.new_password_confirmation_label')" :showPasswordToggle="true"
                tabindex="3" />
        </div>
        @elseif($formType === 'email')
        {{-- Email fields --}}
        <div class="col-12 col-md-6 col-lg-4">
            <input type="hidden" name="current_email" value="{{ $user->email }}">
            <x-profile.unified-input-field name="current_email" type="email" :value="$user->email" :disabled="true"
                :label="__('profile.security_settings.current_email_label')" autocomplete="nope" />
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.unified-input-field name="new_email" type="email"
                :label="__('profile.security_settings.new_email_label')" autocomplete="nope" tabindex="1" autofocus />
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.unified-input-field name="password" type="password" :label="__('profile.password_label')"
                autocomplete="nope" tabindex="2" />
        </div>
        @elseif($formType === 'greeting')
        {{-- Personal greeting fields --}}
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.unified-input-field name="personal_greeting" type="text"
                :label="__('profile.personal_greeting_label')" :value="$user->personal_greeting ?? ''" tabindex="1"
                autofocus />
        </div>
        @endif

        <div data-confirmation-method="{{ $confirmationMethod }}" class="col-12 col-md-6 col-lg-4">
            <input type="hidden" name="confirmation_method" value="{{ $confirmationMethod }}">
        </div>
    </div>

    <x-profile.submit-button :formId="$config['form_id']" :label="__('profile.next_button')" />
    @else
    {{-- Confirmation form --}}
    <div class="row _offset20 mb-20">
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.verification-code
                :method="$confirmationMethod === 'authenticator' || $authenticatorEnabled ? 'authenticator' : 'email'" />
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.info-message :title="__('profile.2fa.info_message_title_authenticator')"
                :description="__('profile.2fa.info_message_description_authenticator')" />
        </div>
    </div>

    <div class="d-flex gap-3">
        <x-profile.submit-button class="mr-4" :formId="$config['form_id']" :label="__('profile.confirm_button')" />
        <div class="mb-20">
            <a href="{{ route($config['cancel_route']) }}" class="btn _flex _red _big">
                {{ __('profile.cancel_button') }}
            </a>
        </div>
    </div>
    @endif
</form>