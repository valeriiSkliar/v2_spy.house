@props(['user', 'api_token', 'scopes', 'experiences'])
<form id="personal-settings-form" action="{{ route('api.profile.settings') }}" method="POST" class="pt-3">
    @csrf
    @method('PUT')
    <x-profile.user-info :user="$user" />
    <div class="col _offset20 mb-20">
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.form-field name="login" type="text" :label="__('profile.personal_info.login_label')"
                :value="$user->login ?? ''" />
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.socials-messanger-field :user="$user" :label="__('profile.personal_info.messanger_label')"
                :value="$user->messanger" />
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.select-field name="experience" :label="__('profile.personal_info.experience_label')"
                :value="$user->experience" :options="$experiences"
                :display-default-value="$displayDefaultValues['experience']" />
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.select-field name="scope_of_activity" :label="__('profile.personal_info.scope_label')"
                :value="$user->scope_of_activity" :options="$scopes"
                :display-default-value="$displayDefaultValues['scope_of_activity']" />
        </div>
    </div>

    {{-- here will be add validation messages for each field, on currend user locale, for using on client side --}}

    <x-profile.success-message status="profile-updated" :message="__('profile.personal_info.update_success')" />
    <x-profile.submit-button formId="personal-settings-form" :label="__('profile.save_button')" />
</form>