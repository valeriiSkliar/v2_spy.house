@extends('layouts.authorized')

@section('page-content')
<h1 class="mb-25">Finances</h1>

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
    <h3 class="mb-15">Choose a convenient payment method:</h3>

    <x-payment-methods :methods="[
            ['name' => 'Tether', 'img' => 'img/pay/tether.svg'],
            ['name' => 'Capitalist', 'img' => 'img/pay/capitalist.svg'],
            ['name' => 'Bitcoin', 'img' => 'img/pay/bitcoin.svg'],
            ['name' => 'Ethereum', 'img' => 'img/pay/ethereum.svg'],
            ['name' => 'Litecoin', 'img' => 'img/pay/litecoin.png'],
            ['name' => 'Pay2', 'img' => 'img/pay/pay2.svg'],
        ]" />

    <x-deposit-form />
</div>

<x-separator height="50" />

<h2>Deposit History</h2>

<x-deposit-history-table :transactions="[
        [
            'date' => '08.04.2025 / 11:10',
            'transactionNumber' => 'TN5780107516',
            'paymentMethod' => 'Tether',
            'amount' => '60',
            'status' => 'Payment expected',
            'statusClass' => ''
        ],
        [
            'date' => '08.04.2025 / 11:10',
            'transactionNumber' => 'TN5780107516',
            'paymentMethod' => 'Tether',
            'amount' => '60',
            'status' => 'Successful',
            'statusClass' => '_successful'
        ],
        [
            'date' => '08.04.2025 / 11:10',
            'transactionNumber' => 'TN5780107516',
            'paymentMethod' => 'Tether',
            'amount' => '60',
            'status' => 'Rejected',
            'statusClass' => '_rejected'
        ]
    ]" />

<x-pagination :currentPage="1" :totalPages="3" />
@endsection