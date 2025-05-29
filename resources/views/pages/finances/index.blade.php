@extends('layouts.authorized')

@section('page-content')
<h1 class="mb-25">{{ __('finances.title') }}</h1>

@if(session('success'))
<x-alert type="success">
    {{ session('success') }}
</x-alert>
@endif

@if(session('error'))
<x-alert type="danger">
    {{ session('error') }}
</x-alert>
@endif

<div class="section">
    <h3 class="mb-15">{{ __('finances.payment_methods.title') }}</h3>

    <x-finances.payment-methods :methods="[
            ['name' => __('finances.payment_methods.tether'), 'img' => 'img/pay/tether.svg'],
            ['name' => __('finances.payment_methods.capitalist'), 'img' => 'img/pay/capitalist.svg'],
            ['name' => __('finances.payment_methods.bitcoin'), 'img' => 'img/pay/bitcoin.svg'],
            ['name' => __('finances.payment_methods.ethereum'), 'img' => 'img/pay/ethereum.svg'],
            ['name' => __('finances.payment_methods.litecoin'), 'img' => 'img/pay/litecoin.png'],
            ['name' => __('finances.payment_methods.pay2'), 'img' => 'img/pay/pay2.svg'],
        ]" />

    <x-finances.deposit-form />
</div>

<x-separator height="50" />

<h2>{{ __('finances.deposit_history_title') }}</h2>

<x-finances.deposit-history-table :transactions="$transactions ?? []" />

<x-pagination :currentPage="1" :totalPages="3" />
@endsection