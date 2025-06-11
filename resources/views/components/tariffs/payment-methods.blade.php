<h3 class="mb-15">{{ __('tariffs.payment_methods.title') }}</h3>
<div class="payment-methods">
    <x-finances.payment-methods :methods="[
        ['name' => App\Enums\Finance\PaymentMethod::USDT->translatedLabel(), 'id' => 'tether', 'value' => App\Enums\Finance\PaymentMethod::USDT, 'img' => '/img/pay/tether.svg'],
        // ['name' => __('finances.payment_methods.capitalist'), 'img' => 'img/pay/capitalist.svg'],
        // ['name' => __('finances.payment_methods.bitcoin'), 'img' => 'img/pay/bitcoin.svg'],
        // ['name' => __('finances.payment_methods.ethereum'), 'img' => 'img/pay/ethereum.svg'],
        // ['name' => __('finances.payment_methods.litecoin'), 'img' => 'img/pay/litecoin.png'],
        ['name' => App\Enums\Finance\PaymentMethod::PAY2_HOUSE->translatedLabel(), 'id' => 'pay2', 'value' => App\Enums\Finance\PaymentMethod::PAY2_HOUSE, 'img' => '/img/pay/pay2.svg'],
    ]" />
</div>