@extends('layouts.auth')

@section('form-content')
<form method="POST" action="{{ route('register') }}" novalidate>
    @csrf
    <div class="d-flex align-items-center justify-content-between mb-30">
        <h1 class="mb-0 font-24">{{ __('auth.registration') }}</h1>
        <a href="{{ route('login') }}" class="btn _flex _black font-16 font-weight-bold">{{ __('auth.log_in') }}</a>
    </div>

    <div class="form-item mb-3">
        <input type="text" name="login" class="input-h-57 @error('login') error @enderror" readonly
            onfocus="this.removeAttribute('readonly');" placeholder="{{ __('auth.login_field') }}"
            value="{{ old('login') }}">
        @error('login')
        <span class="error-message">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-item mb-3">
        <input type="text" name="email" class="input-h-57 @error('email') error @enderror" readonly
            onfocus="this.removeAttribute('readonly');" placeholder="{{ __('auth.email') }}" value="{{ old('email') }}">
        @error('email')
        <span class="error-message">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-item mb-3">
        <div class="form-password">
            <input type="password" name="password" readonly onfocus="this.removeAttribute('readonly');"
                class="input-h-57 @error('password') error @enderror" data-pass="pass-1"
                placeholder="{{ __('auth.password') }}">
            <button type="button" class="btn-icon switch-password" data-pass-switch="pass-1">
                <span class="icon-view-off"></span>
                <span class="icon-view-on"></span>
            </button>
        </div>
        @error('password')
        <span class="error-message">{{ $message }}</span>
        @enderror
    </div>

    <div class="form-item mb-3">
        <div class="form-password">
            <input type="password" name="password_confirmation" readonly onfocus="this.removeAttribute('readonly');"
                class="input-h-57" data-pass="pass-2" placeholder="{{ __('auth.confirm_password') }}">
            <button type="button" class="btn-icon switch-password" data-pass-switch="pass-2">
                <span class="icon-view-off"></span>
                <span class="icon-view-on"></span>
            </button>
        </div>
    </div>

    {{-- <div class="form-item mb-3">
        <div class="form-messenger">
            <input readonly onfocus="this.removeAttribute('readonly');" type="text" name="messenger_contact"
                class="input-h-57 @error('messenger_contact') error @enderror" value="{{ old('messenger_contact') }}"
                placeholder="@UserName">
            <input type="hidden" name="messenger_type" value="telegram">
            <div class="base-select" data-target="messenger_type">
                <div class="base-select__trigger">
                    <span class="base-select__value">Telegram</span>
                    <span class="base-select__arrow"></span>
                </div>
                <ul class="base-select__dropdown" style="display: none;">
                    <li class="base-select__option is-selected" data-value="telegram">Telegram</li>
                    <li class="base-select__option" data-value="whatsapp">WhatsApp</li>
                    <li class="base-select__option" data-value="viber">Viber</li>
                </ul>
            </div>
        </div>
    </div> --}}

    <x-common.messenger-field-component name="messenger_contact" messenger-type-name="messenger_type"
        :show-label="false" messenger-type="telegram" select-id="register-messenger-select" />

    <div class="form-item mb-3">
        <input type="hidden" name="experience" value="">
        <div class="base-select base-select_big is-empty" data-target="experience">
            <div class="base-select__trigger">
                <span class="base-select__value">{{ __('auth.your_experience') }}</span>
                <span class="base-select__arrow"></span>
            </div>
            <ul class="base-select__dropdown" style="display: none;">
                @foreach(\App\Enums\Frontend\UserExperience::cases() as $experience)
                <li class="base-select__option" data-value="{{ $experience->name }}">{{
                    $experience->translatedLabel() }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="form-item mb-3">
        <input type="hidden" name="scope_of_activity" value="">
        <div class="base-select base-select_big is-empty" data-target="scope_of_activity">
            <div class="base-select__trigger">
                <span class="base-select__value">{{ __('auth.vertical') }}</span>
                <span class="base-select__arrow"></span>
            </div>
            <ul class="base-select__dropdown" style="display: none;">
                @foreach(\App\Enums\Frontend\UserScopeOfActivity::cases() as $scope)
                <li class="base-select__option" data-value="{{ $scope->name }}">{{
                    $scope->translatedLabel() }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <!-- reCAPTCHA -->
    <div class="form-item mb-25 pt-2 d-flex justify-content-center">
        <div id="recaptcha-register" class="g-recaptcha" data-sitekey="{{ config('captcha.sitekey') }}"></div>
    </div>
    @error('g-recaptcha-response')
    <div class="form-item mb-3">
        <span class="error-message">{{ $message }}</span>
    </div>
    @enderror

    <div class="form-item mb-30">
        <button type="submit" class="btn _flex _green _big w-100">{{ __('auth.registration') }}</button>
    </div>

    <div class="form-item mb-30">
        <div class="form-text text-center">{{ __('auth.by_clicking_button_accept') }} <br>{{
            __('auth.the') }} <a href="{{ route('terms') }}" target="_blank">{{ __('auth.terms_of_service') }}</a></div>
    </div>
</form>
@endsection

@push('vite-scripts')
@vite(['resources/js/pages/register.js'])
@endpush