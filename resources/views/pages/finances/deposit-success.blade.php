@extends('layouts.main')

@section('page-content')
<div class="deposit-success-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card text-center">
                    <div class="card-body p-5">
                        <div class="success-icon mb-4">
                            <i class="fas fa-check-circle fa-5x text-success"></i>
                        </div>

                        <h2 class="text-success mb-3">Пополнение прошло успешно!</h2>

                        <p class="mb-4">
                            Ваш баланс будет пополнен в течение нескольких минут после подтверждения платежа.
                        </p>

                        @if($invoice_number)
                        <div class="payment-details mb-4">
                            <small class="text-muted">
                                Номер платежа: <code>{{ $invoice_number }}</code>
                            </small>
                        </div>
                        @endif

                        <div class="current-balance mb-4">
                            <h5>Текущий баланс</h5>
                            <div class="balance-amount">
                                ${{ number_format(auth()->user()->available_balance, 2) }}
                            </div>
                        </div>

                        <div class="action-buttons">
                            <a href="{{ route('finances.index') }}" class="btn btn-primary btn-lg me-2">
                                <i class="fas fa-history"></i> История транзакций
                            </a>
                            <a href="{{ route('finances.deposit.form') }}" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-plus"></i> Пополнить ещё
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .balance-amount {
        font-size: 2rem;
        font-weight: bold;
        color: #28a745;
    }

    .success-icon {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }

        100% {
            transform: scale(1);
        }
    }
</style>
@endsection