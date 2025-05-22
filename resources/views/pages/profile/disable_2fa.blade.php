@extends('layouts.main')

@section('page-content')
<h1 class="mb-25">{{ __('profile.2fa.disable_title') }}</h1>

<div class="section mb-20">
    <div class="confirm-operation">
        <div class="confirm-operation__figure"><img src="/img/2fa-figure.svg" alt=""></div>
        <h2 class="mb-20 font-24">Для отключения двухфакторной аутентификации</h2>
        <p class="mb-25"><span class="txt-gray">Введите 6-значный код из</span> <strong>приложения
                Authenticator</strong>:</p>

        @error('error')
        <div class="message _bg _with-border font-weight-500 mb-20">
            <span class="icon-warning font-18"></span>
            <div class="message__txt">
                <strong>{{ $message }}</strong>
            </div>
        </div>
        @enderror

        <form method="POST" action="{{ route('profile.confirm-disable-2fa') }}" id="disableTwoFactorForm">
            @csrf
            <div class="confirm-operation__code mb-20">
                <input type="text" inputmode="numeric" class="input-h-57" maxlength="1" data-code-input>
                <input type="text" inputmode="numeric" class="input-h-57" maxlength="1" data-code-input>
                <input type="text" inputmode="numeric" class="input-h-57" maxlength="1" data-code-input>
                <input type="text" inputmode="numeric" class="input-h-57" maxlength="1" data-code-input>
                <input type="text" inputmode="numeric" class="input-h-57" maxlength="1" data-code-input>
                <input type="text" inputmode="numeric" class="input-h-57" maxlength="1" data-code-input>
                <input type="hidden" name="verification_code" id="verificationCodeField">
            </div>

            @error('verification_code')
            <div class="message _bg _with-border font-weight-500 mb-20">
                <span class="icon-warning font-18"></span>
                <div class="message__txt">
                    <strong>{{ $message }}</strong>
                </div>
            </div>
            @enderror

            <div class="confirm-operation__btn">
                {{-- <a href="{{ route('profile.index') }}" class="btn _flex _big _gray"><span
                        class="font-weight-500">Отмена</span></a> --}}
                <button type="submit" class="btn _flex _big _red">Отключить 2FA</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<x-profile.scripts />
<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('[DEBUG] 2FA Disable - DOM loaded');
        
        const codeInputs = document.querySelectorAll('[data-code-input]');
        const verificationCodeField = document.getElementById('verificationCodeField');
        const form = document.getElementById('disableTwoFactorForm');
        
        if (!codeInputs.length || !verificationCodeField || !form) {
            console.error('[ERROR] 2FA Disable - Required elements not found:', {
                codeInputs: !!codeInputs.length,
                verificationCodeField: !!verificationCodeField,
                form: !!form
            });
            return;
        }
        
        console.log('[DEBUG] 2FA Disable - Form fields found successfully');
        
        // Обработка ввода цифр и автоматического перехода между полями
        codeInputs.forEach((input, index) => {
            input.addEventListener('input', function(e) {
                // Позволяем вводить только цифры
                this.value = this.value.replace(/[^0-9]/g, '');
                
                // Если введена цифра, переходим к следующему полю
                if (this.value && index < codeInputs.length - 1) {
                    codeInputs[index + 1].focus();
                }
                
                // Обновляем скрытое поле с полным кодом
                updateVerificationCode();
            });
            
            // Обработка клавиши Backspace для перехода к предыдущему полю
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && index > 0) {
                    codeInputs[index - 1].focus();
                }
            });
        });
        
        // Функция обновления скрытого поля с полным кодом
        function updateVerificationCode() {
            let code = '';
            codeInputs.forEach(input => {
                code += input.value;
            });
            verificationCodeField.value = code;
        }
        
        // Обработка отправки формы
        form.addEventListener('submit', function(e) {
            // Обновляем код перед отправкой
            updateVerificationCode();
            
            // Проверяем, что код полный (6 цифр)
            if (!verificationCodeField.value || verificationCodeField.value.length !== 6) {
                e.preventDefault();
                alert('Пожалуйста, введите полный 6-значный код подтверждения');
                console.error('[ERROR] 2FA Disable - Incomplete verification code');
                return false;
            }
            
            console.log('[DEBUG] 2FA Disable - Form submission with code, length:', verificationCodeField.value.length);
        });
    });
</script>
@endsection