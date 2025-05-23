@extends('layouts.main')

@section('page-content')
<h1 class="mb-25">{{ __('profile.2fa.disable_title') }}</h1>

<div id="disable-2fa-section" class="section mb-20">
    {{-- <div class="confirm-operation"> --}}
        {{-- <div class="confirm-operation__figure"><img src="/img/2fa-figure.svg" alt=""></div> --}}
        <h2 id="warning-title">Отключение двухфакторной аутентификации</h2>
        <p id="warning-text" class="mb-25 txt-gray">Отключение двухфакторной аутентификации сделает ваш аккаунт менее
            защищенным. Вы
            уверены, что хотите продолжить?</p>

        {{-- <div class="alert alert-warning mb-25">
            <strong>Внимание!</strong> После отключения двухфакторной аутентификации для входа в аккаунт будет
            достаточно только логина и пароля.
        </div> --}}

        <div id="warning-section">
            <div class="confirm-operation__btn">
                {{-- <a href="{{ route('profile.settings', ['tab' => 'security']) }}" class="btn _flex _big _gray">
                    <span class="font-weight-500">Отмена</span>
                </a> --}}
                <button type="button" id="disableBtn" class="btn _flex _big _red">
                    <span class="font-weight-500">Отключить 2FA</span>
                </button>
            </div>
        </div>

        <div id="form-section" style="display: none;">
            <!-- Форма будет загружена асинхронно -->
        </div>
        {{--
    </div> --}}
</div>
@endsection

@push('scripts')
@vite('resources/js/pages/profile/disable-2fa.js')
@endpush