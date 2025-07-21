@extends('layouts.main-app')

@section('page-content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<div class="verify-account">
    <div class="verify-account__figure"><img src="img/figure-verify-acc.svg" alt=""></div>
    <h1 class="mb-25">Подтвердите свой аккаунт</h1>
    <p class="mb-25">Мы отправили вам 6-значный код на ваш email, введите его и ваш аккаунт будет активирован.</p>
    <p class="mb-30"><strong>{{ \App\Helpers\mask_string(auth()->user()->email) }}</strong></p>
    <form
        action="{{ route('verification.verify.post', ['id' => auth()->user()->id, 'hash' => sha1(auth()->user()->email)]) }}"
        method="POST" id="verify-account-form">
        @csrf
        <div class="verify-account-code mb-30">
            <input type="text" inputmode="numeric" class="input-h-57" name="code[]" maxlength="1" pattern="[0-9]"
                required>
            <input type="text" inputmode="numeric" class="input-h-57" name="code[]" maxlength="1" pattern="[0-9]"
                required>
            <input type="text" inputmode="numeric" class="input-h-57" name="code[]" maxlength="1" pattern="[0-9]"
                required>
            <input type="text" inputmode="numeric" class="input-h-57" name="code[]" maxlength="1" pattern="[0-9]"
                required>
            <input type="text" inputmode="numeric" class="input-h-57" name="code[]" maxlength="1" pattern="[0-9]"
                required>
            <input type="text" inputmode="numeric" class="input-h-57" name="code[]" maxlength="1" pattern="[0-9]"
                required>
        </div>
        <div class="verify-account-controls mb-30">
            <div class="verify-account-controls__btn">
                <button type="button" class="btn _flex _link" {{ !$canResend ? 'disabled' : '' }}
                    data-action="resend-verification" @if($unblockTime) data-unblock-time="{{ $unblockTime }}" @endif
                    data-server-time="{{ time() * 1000 }}" data-block-duration="300000">
                    <span class="icon-resend mr-2"></span>
                    Отправить снова
                </button>
            </div>
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

@push('scripts')
@vite('resources/js/pages/verify-email.js')
@endpush