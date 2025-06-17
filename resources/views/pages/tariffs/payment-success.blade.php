@extends('layouts.app')

@section('title', 'Оплата успешна')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg p-8 text-center">
        <div class="mb-6">
            <svg class="w-16 h-16 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 mb-4">
            Оплата успешна!
        </h1>

        <p class="text-gray-600 mb-4">
            Ваша подписка была успешно активирована. Спасибо за покупку!
        </p>

        @if($invoice_number)
        <p class="text-sm text-gray-500 mb-6">
            Номер счета: {{ $invoice_number }}
        </p>
        @endif

        <div class="space-y-3">
            <a href="{{ route('tariffs.index') }}"
                class="block w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
                Перейти к тарифам
            </a>

            <a href="{{ route('home') }}"
                class="block w-full bg-gray-100 text-gray-700 py-2 px-4 rounded hover:bg-gray-200 transition">
                На главную
            </a>
        </div>
    </div>
</div>
@endsection