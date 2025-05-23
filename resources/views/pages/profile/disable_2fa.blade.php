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
            <input type="text" inputmode="numeric" class="input-h-57" data-code-input>
            <input type="text" inputmode="numeric" class="input-h-57" data-code-input>
            <input type="text" inputmode="numeric" class="input-h-57" data-code-input>
            <input type="text" inputmode="numeric" class="input-h-57" data-code-input>
            <input type="text" inputmode="numeric" class="input-h-57" data-code-input>
            <input type="text" inputmode="numeric" class="input-h-57" data-code-input>
            <input type="hidden" name="verification_code" id="verificationCodeField">
        </div>

        <div class="confirm-operation__btn">
            <button type="button" id="cancelBtn" class="btn _flex _big _gray">
                <span class="font-weight-500">Отмена</span>
            </button>
            <button type="submit" class="btn _flex _big _red">Отключить 2FA</button>
        </div>
    </form>
</div>