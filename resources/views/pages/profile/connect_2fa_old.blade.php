@extends('layouts.main-app')

@section('page-content')
<h1 class="mb-25">{{ __('profile.2fa.setup_title') }}</h1>

<div class="section profile-settings mb-20">
    <!-- Status messages -->
    @if (session('status') == '2fa-enabled')
    <div class="message _bg _with-border font-weight-500">
        <span class="icon-warning font-18"></span>
        <div class="message__txt">
            {{ __('profile.2fa.status_enabled') }}
        </div>
    </div>
    @endif
    @if (session('status') == '2fa-disabled')
    <div class="message _bg _with-border font-weight-500">
        <span class="icon-warning font-18"></span>
        <div class="message__txt">
            {{ __('profile.2fa.status_disabled') }}
        </div>
    </div>
    @endif

    <!-- Main content -->
    @if ($user->google_2fa_enabled)
    <p>{{ __('profile.2fa.current_status_enabled') }}</p>
    <form method="POST" action="{{ route('profile.disable-2fa') }}">
        @csrf
        <button type="submit" class="btn _flex _border-green _big min-200 w-mob-100">
            {{ __('profile.2fa.disable_button') }}
        </button>
    </form>
    @else
    <div class="section mb-20">
        <h2>{{ __('profile.2fa.activation_title') }}</h2>
        <p>{{ __('profile.2fa.setup_instructions_1') }}</p>
        <p>{{ __('profile.2fa.app_download_instructions') }}</p>
    </div>

    <div class="section mb-20">
        <div class="step-2fa">
            <div class="step-2fa__head">
                {{-- <div class="step-2fa__number">1</div> --}}
                <h2>{{ __('profile.2fa.google_authenticator_title') }}</h2>
            </div>
            <div class="step-2fa__content">
                <p class="mb-30">{{ __('profile.2fa.scan_qr_instructions') }}</p>
                <div class="row _offset20 pt-2 align-items-center">
                    <!-- QR code -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="mb-30">
                            <div class="step-2fa__qr">
                                <img src="{{ $qrCodeInline }}" alt="{{ __('profile.2fa.qr_code_alt') }}">
                            </div>
                        </div>
                    </div>
                    <!-- Account token -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="mb-30">
                            <div class="step-2fa__key">
                                <p class="mb-20 font-16">
                                    {{ __('profile.2fa.account_token_label') }}: <br>
                                    <strong>{{ $google_2fa_secret }}</strong>
                                </p>
                                <button type="button" class="btn _flex _border-green mb-20 w-100"
                                    onclick="generateNewToken()">
                                    {{ __('profile.2fa.generate_another_button') }}
                                </button>
                                <p class="mb-0 txt-gray-2">
                                    {{ __('profile.2fa.token_warning') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confirmation form -->
                <form method="POST" action="{{ route('profile.store-2fa') }}">
                    @csrf
                    {{-- <div class="row _offset20 pt-2">
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="form-item mb-30">
                                <label class="d-block mb-10">
                                    {{ __('profile.2fa.confirmation_method_label') }}
                                    <span class="popover-icon icon-i ml-1"></span>
                                </label>
                                <div class="base-select base-select_big">
                                    <div class="base-select__trigger">
                                        <span class="base-select__value">{{ __('profile.2fa.authenticator_app')
                                            }}</span>
                                        <span class="base-select__arrow"></span>
                                    </div>
                                    <ul class="base-select__dropdown" style="display: none;">
                                        <li class="base-select__option is-selected">{{
                                            __('profile.2fa.authenticator_app') }}</li>
                                        <li class="base-select__option">{{ __('profile.2fa.sms_code') }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div> --}}

                    <!-- OTP field -->
                    {{-- <div class="form-item mb-30">
                        <label class="d-block mb-10" for="one_time_password">
                            {{ __('profile.2fa.otp_label') }}
                        </label>
                        <input id="one_time_password" type="text"
                            class="form-control input-h-57 input-h-57-lg text-center @error('one_time_password') is-invalid @enderror"
                            name="one_time_password" required autofocus>
                        @error('one_time_password')
                        <div class="message _bg _with-border font-weight-500">
                            <span class="icon-warning font-18"></span>
                            <div class="message__txt">
                                <strong>{{ $message }}</strong>
                            </div>
                        </div>
                        @enderror
                    </div> --}}

                    <!-- Confirmation button -->
                    <div class="mb-20">
                        <button type="submit" class="btn _flex _green _big min-200 w-mob-100">
                            {{ __('profile.2fa.enable_button') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<x-profile.scripts />
<script>
    function generateNewToken() {
            // Здесь нужно реализовать AJAX-запрос для генерации нового секретного ключа
            // Например, запрос к маршруту, который обновит $google_2fa_secret и $qrCodeInline
            alert('Функция генерации нового токена будет реализована позже.');
        }
</script>
@endsection