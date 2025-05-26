@extends('layouts.main')

@section('page-content')
<div class="verify-account">
    <div class="verify-account__figure"><img src="img/figure-verify-acc.svg" alt=""></div>
    <h1 class="mb-25">Подтвердите свой аккаунт</h1>
    <p class="mb-25">Мы отправили вам 6-значный код на ваш email, введите его и ваш аккаунт будет активирован.</p>
    <p class="mb-30"><strong>{{ substr(auth()->user()->email, 0, 4) . '...' . strstr(auth()->user()->email, '@')
            }}</strong></p>
    <form action="">
        <div class="verify-account-code mb-30">
            <input type="text" inputmode="numeric" class="input-h-57">
            <input type="text" inputmode="numeric" class="input-h-57">
            <input type="text" inputmode="numeric" class="input-h-57">
            <input type="text" inputmode="numeric" class="input-h-57">
            <input type="text" inputmode="numeric" class="input-h-57">
            <input type="text" inputmode="numeric" class="input-h-57">
        </div>
        <div class="verify-account-controls mb-30">
            <div class="verify-account-controls__btn">
                <button class="btn _flex _link" disabled>
                    <span class="icon-resend mr-2"></span>
                    Отправить снова
                </button>
            </div>
            {{-- <div class="verify-account-controls__timer">In 00:05:17</div> --}}
        </div>
        <button type="submit" class="btn _flex _green _big w-100">Активировать аккаунт</button>
    </form>
</div>
<div class="write-telegram mb-5">
    <div class="write-telegram__txt">Напишите нам в наш Telegram канал, мы поможем с любым вопросом!</div>
    <div class="write-telegram__btn">
        <a href="#" class="btn _flex" target="_blank"><span class="icon-telegram font-18 mr-2"></span>Чат</a>
    </div>
</div>
@endsection