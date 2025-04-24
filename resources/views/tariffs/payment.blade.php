@extends('layouts.authorized')

@section('page-content')
<h1>Tariffs</h1>
<div class="tariff-pay mb-15">
    <div class="row align-items-center _offset30">
        <div class="col-12 col-md-auto">
            <div class="tariff-pay__name">
                <div class="tariff-name _{{ $tariff['css_class'] }}">{{ $tariff['name'] }}</div>
            </div>
        </div>
        <div class="col-auto">
            <div class="tariff-pay__info">Expiration Date: <strong>1 month</strong></div>
        </div>
        <div class="col-auto">
            <div class="tariff-pay__info">Cost: <strong>${{ $tariff['monthly_price'] }} USD</strong></div>
        </div>
    </div>
</div>
<div class="section">
    <h3 class="mb-15">Choose a convenient payment method:</h3>
    <div class="payment-methods">
        @foreach($paymentMethods as $index => $method)
        <label class="payment-method">
            <input type="radio" name="payment" {{ $index === 0 ? 'checked' : '' }}>
            <span class="payment-method__content"><img src="{{ $method['img'] }}" alt="{{ $method['name'] }}"> <span>{{ $method['name'] }}</span></span>
        </label>
        @endforeach
    </div>
    <div class="row flex-row-reverse _offset80 justify-content-end">
        <div class="col-12 col-md-12 col-lg-6">
            <div class="message mb-25">
                <span class="icon-i"></span>
                <div class="message__txt">Your account will be activated after payment confirmation. <br>This usually takes <strong>5 minutes</strong>.</div>
            </div>
            <div class="message _bg _red mb-25">
                <span class="icon-i"></span>
                <div class="message__txt">Please be careful and transfer the exact amount specified in the instructions to ensure your payment is processed successfully</div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-5">
            <form action="{{ route('tariffs.process-payment') }}" method="POST">
                @csrf
                <input type="hidden" name="tariff_id" value="{{ $tariff['id'] }}">
                <input type="hidden" name="payment_method" id="selected_payment_method" value="Tether">

                <div class="form-item mb-25">
                    <label class="d-block mb-10 font-weight-600">Promo Code</label>
                    <input type="text" name="promo_code" class="input-h-57" value="">
                </div>
                <div class="mb-20">
                    <button type="submit" class="btn _flex _green _big min-200 w-mob-100">Proceed to payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle payment method selection
        const paymentMethods = document.querySelectorAll('input[name="payment"]');
        const hiddenInput = document.getElementById('selected_payment_method');

        paymentMethods.forEach(method => {
            method.addEventListener('change', function() {
                const methodName = this.closest('.payment-method').querySelector('span > span').textContent;
                hiddenInput.value = methodName;
            });
        });
    });
</script>
@endsection