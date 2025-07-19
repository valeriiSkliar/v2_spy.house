@extends('layouts.main-app')

@section('page-content')
<x-tariffs.payment-header />
<x-tariffs.payment-info :tariff="$tariff" :billingType="$billingType" :isRenewal="$isRenewal" />
<div id="subscription-payment-container" class="section">
    <x-tariffs.payment-methods :paymentMethods="$paymentMethods" />
    <x-tariffs.payment-form :tariff="$tariff" :billingType="$billingType" :isRenewal="$isRenewal" />
</div>
@endsection

@push('scripts')
@vite(['resources/js/tariffs-payments.js', 'resources/js/tariffs.js'])
@endpush