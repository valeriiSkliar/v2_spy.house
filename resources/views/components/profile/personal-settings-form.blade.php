@props(['user', 'api_token', 'scopes'])
<form action="{{ route('profile.update-settings') }}" method="POST" enctype="multipart/form-data" class="pt-3">
    @csrf
    @method('PUT')
    <x-profile.user-info :user="$user" />
    <x-profile.api-token :api_token="$api_token" />
    <div class="row _offset20 mb-20">
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.form-field name="login" type="text" :label="__('profile.personal_info.login_label')" :value="$user->login ?? ''" />
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.form-field name="email" type="email" :label="__('profile.personal_info.email_label')" :value="$user->email" />
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.password-field />
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.phone-field :user="$user" />
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.form-field name="telegram" type="text" :label="__('profile.personal_info.telegram_label')" :value="$user->telegram ?? ''" />
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <x-profile.select-field name="scope" :label="__('profile.personal_info.scope_label')" :value="$user->scope ?? 'Arbitrage (solo)'" :options="$scopes" />
        </div>
    </div>
    <x-profile.success-message status="profile-updated" :message="__('profile.personal_info.update_success')" />
    <x-profile.submit-button :label="__('profile.save_button')" />
</form>