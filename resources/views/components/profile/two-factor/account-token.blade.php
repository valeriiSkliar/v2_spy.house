@props(['google_2fa_secret'])

<div class="mb-30">
    <div class="step-2fa__key">
        <p class="mb-20 font-16">Токен аккаунта (ключ): <br> <strong class="js-2fa-secret">{{ $google_2fa_secret
                }}</strong></p>
        <button type="button" class="btn _flex _border-green mb-20 w-100 js-regenerate-2fa-secret">Сгенерировать
            другой</button>
        <p class="mb-0 txt-gray-2">Этот токен больше не будет показан после подтверждения активации инструмента
            подписи.</p>
    </div>
</div>