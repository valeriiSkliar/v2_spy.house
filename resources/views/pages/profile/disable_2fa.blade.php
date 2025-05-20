@extends('layouts.main')

@section('page-content')
<h1 class="mb-25">{{ __('profile.2fa.disable_title') }}</h1>

<div class="section profile-settings">
    <p class="mb-15 text-center">Для отключения двухфакторной аутентификации введите код из приложения</p>

    @error('error')
    <div class="message _bg _with-border font-weight-500">
        <span class="icon-warning font-18"></span>
        <div class="message__txt">
            <strong>{{ $message }}</strong>
        </div>
    </div>
    @enderror

    <form method="POST" action="{{ route('profile.confirm-disable-2fa') }}" class="mt-3" id="disableTwoFactorForm">
        @csrf
        <div class="col-12 col-md-6 col-lg-6">
            <x-profile.authenticator-code />
        </div>

        @error('verification_code')
        <div class="message _bg _with-border font-weight-500 mb-20">
            <span class="icon-warning font-18"></span>
            <div class="message__txt">
                <strong>{{ $message }}</strong>
            </div>
        </div>
        @enderror

        <div class="d-flex justify-content-center mt-4">
            <button type="submit" class="btn _flex _red _big min-200 mt-15 w-mob-100">Отключить 2FA</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<x-profile.scripts />
<script>
    document.addEventListener('DOMContentLoaded', function() {
            console.log('[DEBUG] 2FA Disable - DOM loaded');
            
            const otpInput = document.querySelector('input[name="verification_code"]');
            const form = document.getElementById('disableTwoFactorForm');
            
            if (!otpInput || !form) {
                console.error('[ERROR] 2FA Disable - Required elements not found:', {
                    otpInput: !!otpInput,
                    form: !!form
                });
                return;
            }
            
            console.log('[DEBUG] 2FA Disable - Form fields found successfully');
            
            // Обработка отправки формы
            form.addEventListener('submit', function(e) {
                // Проверяем, заполнено ли поле
                if (!otpInput.value || otpInput.value.length === 0) {
                    e.preventDefault();
                    alert('Пожалуйста, введите код подтверждения');
                    console.error('[ERROR] 2FA Disable - Empty verification code');
                    return false;
                }
                
                console.log('[DEBUG] 2FA Disable - Form submission with code, length:', otpInput.value.length);
            });
        });
</script>
@endsection