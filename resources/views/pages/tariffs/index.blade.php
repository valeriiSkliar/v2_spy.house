@extends('layouts.main-app')

@section('page-content')
<x-tariffs.header />
<div class="sep _h10"></div>
<div class="rate">
    <div class="rate__fixed">
        <x-tariffs.current-tariff :currentTariff="$currentTariff" />
        <x-tariffs.features />
    </div>
    <div class="rate__scroll">
        <div class="rate__list">
            @foreach($tariffs as $tariff)
            <x-tariffs.card :tariff="$tariff" :currentTariff="$currentTariff" />
            @endforeach
        </div>
    </div>
</div>
<div class="sep _h60"></div>
@if (isset($payments) && $payments->isNotEmpty())
<x-tariffs.payments-table :payments="$payments" />

{{-- Pagination container --}}
<div id="payments-pagination-container" data-pagination-container>
    @if ($payments->hasPages())
    <x-tariffs.payments-pagination :currentPage="$payments->currentPage()" :totalPages="$payments->lastPage()"
        :pagination="$payments" />
    @endif
</div>
@endif
@endsection

@push('scripts')
@vite(['resources/js/tariffs-payments.js', 'resources/js/tariffs.js'])
@endpush