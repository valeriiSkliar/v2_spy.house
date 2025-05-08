@extends('layouts.main')

@section('page-content')
    <h1 class="mb-25">{{ __('profile.personal_greeting_page_title') }}</h1>
    <div class="section profile-settings">
        <form id="personal-greeting-form" action="{{ route('profile.update-personal-greeting') }}" method="POST" class="pt-3">
            @csrf
            @method('PUT')
            <div class="mb-20">
                <x-profile.form-field 
                    name="personal_greeting" 
                    type="text" 
                    :label="__('profile.personal_greeting_label')" 
                    :value="old('personal_greeting', $user->personal_greeting)" 
                />
            </div>
            <x-profile.success-message 
                status="personal-greeting-updated" 
                :message="__('profile.personal_greeting_update_success')" 
            />
            <x-profile.submit-button formId="personal-greeting-form" :label="__('profile.save_button')" />
        </form>
    </div>
@endsection 