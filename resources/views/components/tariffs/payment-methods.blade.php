<h3 class="mb-15">{{ __('tariffs.payment_methods.title') }}</h3>
<div class="payment-methods">
    {{-- @foreach($paymentMethods as $index => $method)
    <label class="payment-method">
        <input type="radio" name="payment" {{ $index===0 ? 'checked' : '' }}>
        <span class="payment-method__content"><img src="{{ $method['img'] }}" alt="{{ $method['name'] }}"> <span>{{
                $method['name'] }}</span></span>
    </label>
    @endforeach --}}

    <x-finances.payment-methods :methods="[
        ['name' => __('finances.payment_methods.tether'), 'img' => '/img/pay/tether.svg'],
        // ['name' => __('finances.payment_methods.capitalist'), 'img' => 'img/pay/capitalist.svg'],
        // ['name' => __('finances.payment_methods.bitcoin'), 'img' => 'img/pay/bitcoin.svg'],
        // ['name' => __('finances.payment_methods.ethereum'), 'img' => 'img/pay/ethereum.svg'],
        // ['name' => __('finances.payment_methods.litecoin'), 'img' => 'img/pay/litecoin.png'],
        ['name' => __('finances.payment_methods.pay2'), 'img' => '/img/pay/pay2.svg'],
    ]" />
</div>