<form method="POST" action="{{ route('profile.update-email') }}" class="profile-form">
    @csrf
    @method('PUT')
    <div class="row _offset20 mb-10">
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.form-field 
                name="current_email" 
                :type="'email'" 
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
            <x-profile.select-field 
                name="confirmation_method" 
                :label="__('profile.security_settings.confirmation_method_label')"
                value="{{ __('profile.security_settings.confirmation_methods.' . $confirmationMethod) }}" 
                :options="[
                    __('profile.security_settings.confirmation_methods.authenticator'),
                    __('profile.security_settings.confirmation_methods.email'),
                ]" 
                data-confirmation="true"
            />
        </div>
    </div>
    <x-profile.submit-button :label="__('profile.security_settings.next_button')" />
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
    <x-profile.submit-button :label="__('profile.security_settings.confirm_button')" />

</form>
