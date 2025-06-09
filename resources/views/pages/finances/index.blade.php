@extends('layouts.authorized')

@section('page-content')
<h1 class="mb-25">{{ __('finances.title') }}</h1>
<div class="section">
    <h3 class="mb-15">{{ __('finances.payment_methods.title') }}</h3>

    <x-finances.payment-methods :methods="[
            ['name' => __('finances.payment_methods.tether'), 'img' => 'img/pay/tether.svg'],
            // ['name' => __('finances.payment_methods.capitalist'), 'img' => 'img/pay/capitalist.svg'],
            // ['name' => __('finances.payment_methods.bitcoin'), 'img' => 'img/pay/bitcoin.svg'],
            // ['name' => __('finances.payment_methods.ethereum'), 'img' => 'img/pay/ethereum.svg'],
            // ['name' => __('finances.payment_methods.litecoin'), 'img' => 'img/pay/litecoin.png'],
            ['name' => __('finances.payment_methods.pay2'), 'img' => 'img/pay/pay2.svg'],
        ]" />

    <x-finances.deposit-form />
</div>

<x-separator height="50" />

<h2>{{ __('finances.deposit_history_title') }}</h2>
@if ($transactions->isNotEmpty())
<x-finances.deposit-history-table :transactions="$transactions" />
@if ($transactions->hasPages())
{{ $transactions->links('components.pagination') }}
@endif
@else
<p>{{ __('finances.deposit_history_empty') }}</p>
@endif
@endsection