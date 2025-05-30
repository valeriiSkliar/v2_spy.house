@props(['user'])

<div class="step-2fa">
    <x-profile.two-factor.step-header number="2" title="Подтверждение" />
    <div class="step-2fa__content">
        <div id="step2-errors" class="d-none">
            <div class="message _bg _with-border font-weight-500 mb-20">
                <span class="icon-warning font-18"></span>
                <div class="message__txt">
                    <strong id="step2-error-text"></strong>
                </div>
            </div>
        </div>

        {{-- <p class="mb-30">Введите код из приложения для подтверждения активации двухфакторной аутентификации</p>
        --}}

        <form class="mt-3" id="twoFactorFormAjax" data-ajax-url="{{ route('profile.store-2fa-ajax') }}">
            @csrf
            <div class="row _offset20 mb-20">
                <div class="col-12 col-md-6 col-lg-4">
                    <x-profile.authenticator-code />
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <x-profile.info-message :title="__('profile.2fa.info_message_title_authenticator')"
                        :description="__('profile.2fa.info_message_description_authenticator')" />
                </div>
            </div>

            <div class="d-flex justify-content-start mt-4 gap-3">
                <button type="button"
                    class="btn _flex _red _big min-200 mt-15 w-mob-100 js-back-to-step1">Назад</button>
                <button type="submit" class="btn _flex _green _big min-200 mt-15 w-mob-100 js-submit-2fa">{{
                    __('profile.confirm_button') }}</button>
            </div>
        </form>
    </div>
</div>