@extends('layouts.authorized')

@section('page-content')
<x-tariffs.payment-header />
<x-tariffs.payment-info :tariff="$tariff" :billingType="$billingType" :isRenewal="$isRenewal" />
<div class="section">
    <x-tariffs.payment-methods :paymentMethods="$paymentMethods" />
    <x-tariffs.payment-form :tariff="$tariff" :billingType="$billingType" :isRenewal="$isRenewal" />
</div>
@endsection