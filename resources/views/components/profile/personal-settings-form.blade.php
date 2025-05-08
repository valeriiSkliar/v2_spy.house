@props(['user', 'api_token', 'scopes', 'experiences'])
<form 
    id="personal-settings-form"
    action="{{ route('profile.update-settings') }}"
     method="POST" 
     enctype="multipart/form-data" 
     class="pt-3"
    >
        @csrf
        @method('PUT')
    <x-profile.user-info 
        :user="$user" 
    />
    <x-profile.api-token 
        :api_token="$api_token" 
    />
    <div class="row _offset20 mb-20">
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.form-field 
                name="login" 
                type="text" 
                :label="__('profile.personal_info.login_label')" 
                :value="$user->login ?? ''" 
            />
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.form-field 
                name="name" 
                type="text" 
                :label="__('profile.personal_info.first_name_label')" 
                :value="$user->name" 
                />
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.form-field 
                name="surname" 
                type="text" 
                :label="__('profile.personal_info.last_name_label')" 
                :value="$user->surname" 
            />
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.date-picker-form-field 
                name="date_of_birth" 
                type="date" 
                :label="__('profile.personal_info.birth_date_label')" 
                :value="$user->date_of_birth" 
            />
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.socials-messanger-field 
                :user="$user" 
                :label="__('profile.personal_info.messanger_label')" 
                :value="$user->messanger"
            />
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.select-field 
                name="experience" 
                :label="__('profile.personal_info.experience_label')" 
                :value="$user->experience ?? \App\Enums\Frontend\UserExperience::BEGINNER->translatedLabel()" 
                :options="$experiences" 
            />
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.select-field 
                name="scope_of_activity" 
                :label="__('profile.personal_info.scope_label')" 
                :value="$user->scope_of_activity ?? \App\Enums\Frontend\UserScopeOfActivity::GAMBLING->translatedLabel()" 
                :options="$scopes" 
            />
        </div>
    </div>
    <x-profile.success-message 
        status="profile-updated" 
        :message="__('profile.personal_info.update_success')" 
    />
    <x-profile.submit-button 
        formId="personal-settings-form"
        :label="__('profile.save_button')" 
    />
</form>