@extends('layouts.main')

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

@if (isset($transactions))
<h2>{{ __('finances.deposit_history_title') }}</h2>

{{-- Контейнер для AJAX контента --}}
<div id="transactions-container" data-transactions-ajax-url="{{ route('api.finances.list') }}">
    <x-finances.transactions-list :transactions="$transactions" />
</div>

{{-- Контейнер для пагинации --}}
<div id="transactions-pagination-container" data-pagination-container>
    @if ($transactions->hasPages())
    {{ $transactions->links() }}
    @endif
</div>
@endif
@endsection

@push('scripts')
@vite(['resources/js/finances.js'])
@endpush