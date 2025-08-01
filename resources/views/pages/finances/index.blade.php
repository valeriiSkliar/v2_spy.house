@extends('layouts.main-app')

@section('page-content')
<h1 class="mb-25">{{ __('finances.title') }}</h1>


<div id="deposit-form-container" class="section">
    <h3 class="mb-15">{{ __('finances.payment_methods.title') }}</h3>

    <x-finances.payment-methods :methods="$paymentMethods" />

    <x-finances.deposit-form />
</div>

<x-separator height="50" />

@if (isset($transactions) && $transactions->isNotEmpty())
<h2>{{ __('finances.deposit_history_title') }}</h2>

{{-- AJAX content container --}}
<div id="transactions-container" data-transactions-ajax-url="{{ route('api.finances.list') }}">
    <x-finances.transactions-list :transactions="$transactions" />
</div>

{{-- Pagination container --}}
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