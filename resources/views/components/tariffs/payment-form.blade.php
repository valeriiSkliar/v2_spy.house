<div class="row flex-row-reverse _offset80 justify-content-end">
    <div id="subscription-payment-form-message" class="col-12 col-md-12 col-lg-6" style="display: none;">
        <div class="message mb-25">
            <span class="icon-i"></span>
            <div class="message__txt">{{ __('tariffs.payment_form.account_activation_message') }} <br>
                <strong>{{ __('tariffs.payment_form.payment_processing_time') }}</strong>.
            </div>
        </div>
        <div class="message _bg _red mb-25">
            <span class="icon-i"></span>
            <div class="message__txt">{{ __('tariffs.payment_form.payment_processing_message') }}</div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-lg-5">
        <form id="subscription-payment-form" action="{{ route('tariffs.process-payment') }}" method="POST">
            @csrf
            <input type="hidden" name="is_renewal" value="{{ $isRenewal ? '1' : '0' }}">
            <input type="hidden" name="is_upgrade" value="{{ ($isUpgrade ?? false) ? '1' : '0' }}">
            <input type="hidden" name="payment_method" id="selected_payment_method"
                value="{{  App\Enums\Finance\PaymentMethod::USDT }}">

            @if($billingType === 'month')
            <div class="form-item mb-25">
                <label class="d-block mb-10 font-weight-600">{{ __('tariffs.promo_code.title') }}</label>
                <input type="text" name="promo_code" class="input-h-57" value="">
            </div>
            @endif
            <div class="mb-20">
                <button type="submit" class="btn _flex _green _big min-200 w-mob-100">{{
                    __('tariffs.payment_form.proceed_to_payment') }}</button>
            </div>
        </form>
    </div>
</div>