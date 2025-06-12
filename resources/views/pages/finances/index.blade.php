@extends('layouts.main')

@section('page-content')
<h1 class="mb-25">{{ __('finances.title') }}</h1>

{{-- Секция с текущим балансом --}}
<div class="section mb-4">
    <div class="balance-info-card p-4 border rounded">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h3 class="mb-2">Ваш баланс</h3>
                <div class="balance-amount mb-3">
                    ${{ number_format(auth()->user()->available_balance, 2) }}
                </div>
                <p class="text-muted mb-0">Доступно для покупки подписок</p>
            </div>
            {{-- <div class="col-md-6 text-md-end">
                <a href="{{ route('finances.deposit.form') }}" class="btn _green _big">
                    <i class="fas fa-plus me-2"></i>Пополнить баланс
                </a>
            </div> --}}
        </div>
    </div>
</div>

<div class="section">
    <h3 class="mb-15">{{ __('finances.payment_methods.title') }}</h3>

    <x-finances.payment-methods :methods="$paymentMethods" />

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