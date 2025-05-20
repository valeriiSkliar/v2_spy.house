@props(['google_2fa_secret'])

<div class="mb-30">
    <div class="step-2fa__key">
        <p class="mb-20 font-16">Токен аккаунта (ключ): <br> <strong>{{ $google_2fa_secret }}</strong></p>
        <button class="btn _flex _border-green mb-20 w-100">Generate another</button>
        <p class="mb-0 txt-gray-2">This token will no longer be shown after you confirm activation of the signature tool.</p>
    </div>
</div>
