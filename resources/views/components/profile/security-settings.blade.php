<div class="pt-3">
    <h2 class="mb-30">{{ __('profile.security_settings.access_settings_title') }}</h2>
    <div class="row _offset30">
        <x-profile.access-link route="{{ route('profile.change-password') }}" icon="icon-security"
            :title="__('profile.security_settings.change_password_title')"
            :description="__('profile.security_settings.login_description')" />
        <x-profile.access-link route="{{ route('profile.change-email') }}" icon="icon-email"
            :title="__('profile.security_settings.change_email_title')"
            :description="__('profile.security_settings.login_description')" />
        <x-profile.access-link route="{{ route('profile.personal-greeting') }}" icon="icon-personal"
            :title="__('profile.security_settings.personal_greeting_title')"
            :description="__('profile.security_settings.phishing_description')" />
        <x-profile.access-link route="{{ route('profile.ip-restriction') }}" icon="icon-ip"
            :title="__('profile.security_settings.ip_restriction_title')"
            :description="__('profile.security_settings.ip_description')" />
    </div>
    <h2 class="mb-30">{{ __('profile.security_settings.confirmation_methods_title') }}</h2>
    <div class="row _offset30">
        <x-profile.confirmation-method :isEnabled="auth()->user()->google_2fa_enabled"
            routeEnable="{{ route('profile.connect-2fa') }}" routeDisable="{{ route('profile.disable-2fa') }}"
            icon="/img/google-authenticator.svg" width="42" height="42"
            :title="__('profile.security_settings.google_2fa_title')"
            :description="__('profile.security_settings.google_2fa_description')" />
    </div>
</div>