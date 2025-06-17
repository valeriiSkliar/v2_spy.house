<h3 class="mb-15">{{ __('tariffs.payment_methods.title') }}</h3>
<div class="payment-methods">
    <x-finances.payment-methods :methods="$paymentMethods" />
</div>