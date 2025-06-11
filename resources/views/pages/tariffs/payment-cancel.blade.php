@extends('layouts.app')

@section('title', 'Оплата отменена')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg p-8 text-center">
        <div class="mb-6">
            <svg class="w-16 h-16 text-orange-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.996-.833-2.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z">
                </path>
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-gray-900 mb-4">
            Оплата отменена
        </h1>

        <p class="text-gray-600 mb-4">
            Операция была отменена. Вы можете попробовать снова или выбрать другой способ оплаты.
        </p>

        @if($invoice_number)
        <p class="text-sm text-gray-500 mb-6">
            Номер счета: {{ $invoice_number }}
        </p>
        @endif

        <div class="space-y-3">
            <a href="{{ route('tariffs.index') }}"
                class="block w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
                Попробовать снова
            </a>

            <a href="{{ route('home') }}"
                class="block w-full bg-gray-100 text-gray-700 py-2 px-4 rounded hover:bg-gray-200 transition">
                На главную
            </a>
        </div>
    </div>
</div>
@endsection