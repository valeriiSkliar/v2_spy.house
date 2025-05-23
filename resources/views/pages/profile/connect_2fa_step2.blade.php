@extends('layouts.main')

@section('page-content')
<x-profile.two-factor.activation-info />

<div class="section mb-20">
    <x-profile.two-factor.status-messages />
    <div class="step-2fa">
        <x-profile.two-factor.step-header number="2" title="Подтверждение" />
        <div class="step-2fa__content">
            @error('error')
            <div class="message _bg _with-border font-weight-500">
                <span class="icon-warning font-18"></span>
                <div class="message__txt">
                    <strong>{{ $message }}</strong>
                </div>
            </div>
            @enderror

            <p class="mb-30">Введите код из приложения для подтверждения активации двухфакторной аутентификации</p>

            <form method="POST" action="{{ route('profile.store-2fa') }}" class="mt-3" id="twoFactorForm">
                @csrf
                <div class="row _offset20 mb-20 ">
                    <div class="col-12 col-md-6 col-lg-4">
                        <x-profile.authenticator-code />
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <x-profile.info-message :title="__('profile.2fa.info_message_title_authenticator')"
                            :description="__('profile.2fa.info_message_description_authenticator')" />
                    </div>
                </div>
                {{--
                @error('verification_code')
                <div class="message _bg _with-border font-weight-500 mb-20">
                    <span class="icon-warning font-18"></span>
                    <div class="message__txt">
                        <strong>{{ $message }}</strong>
                    </div>
                </div>
                @enderror --}}

                <div class="d-flex justify-content-start mt-4">
                    <button type="submit" class="btn _flex _green _big min-200 mt-15 w-mob-100">{{
                        __('profile.2fa.confirm_button') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<x-profile.scripts />
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('[DEBUG] 2FA Step 2 - DOM loaded');
        
        const otpInput = document.querySelector('input[name="verification_code"]');
        const form = document.getElementById('twoFactorForm');
        
        if (!otpInput || !form) {
            console.error('[ERROR] 2FA Step 2 - Required elements not found:', {
                otpInput: !!otpInput,
                form: !!form
            });
            return;
        }
        
        console.log('[DEBUG] 2FA Step 2 - Form fields found successfully');
        
        // Обработка отправки формы
        form.addEventListener('submit', function(e) {
            // Проверяем, заполнено ли поле
            if (!otpInput.value || otpInput.value.length === 0) {
                e.preventDefault();
                alert('Пожалуйста, введите код подтверждения');
                console.error('[ERROR] 2FA Step 2 - Empty verification code');
                return false;
            }
            
            console.log('[DEBUG] 2FA Step 2 - Form submission with code, length:', otpInput.value.length);
        });
    });
</script>
@endsection