@props(['user', 'confirmationMethod', 'personalGreetingUpdatePending', 'authenticatorEnabled'])

<div id="personal-greeting-form-container">
    <form id="personal-greeting-form" action="{{ route('api.profile.initiate-personal-greeting-update') }}"
        method="POST" class="pt-3">
        @csrf
        <input type="hidden" name="confirmation_method" value="{{ old('confirmation_method', $confirmationMethod) }}">
        <div class="row _offset20 mb-10">
            <div class="col-12 col-md-6">
                <x-profile.form-field :popover="false" :popoverText="__('profile.personal_greeting_popover_text')"
                    name="personal_greeting" type="text" :label="__('profile.personal_greeting_label')"
                    :value="old('personal_greeting', $user->personal_greeting)" />
            </div>
        </div>
        <x-profile.submit-button formId="personal-greeting-form" :label="__('profile.security_settings.next_button')" />
    </form>
</div>