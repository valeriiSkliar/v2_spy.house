@extends('layouts.authorized')

@section('page-content')
{{-- @dd($tariff) --}}
<x-tariffs.payment-header />
<x-tariffs.payment-info :tariff="$tariff" :billingType="$billingType" />
<div class="section">
    <x-tariffs.payment-methods :paymentMethods="$paymentMethods" />
    <x-tariffs.payment-form :tariff="$tariff" :billingType="$billingType" />
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle payment method selection
        const paymentMethods = document.querySelectorAll('input[name="payment"]');
        const hiddenInput = document.getElementById('selected_payment_method');

        paymentMethods.forEach(method => {
            method.addEventListener('change', function() {
                const methodName = this.closest('.payment-method').querySelector('span > span').textContent;
                hiddenInput.value = methodName;
            });
        });
    });
</script>
@endsection