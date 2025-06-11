@extends('layouts.main')

@section('page-content')
<div class="deposit-cancel-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card text-center">
                    <div class="card-body p-5">
                        <div class="cancel-icon mb-4">
                            <i class="fas fa-times-circle fa-5x text-warning"></i>
                        </div>

                        <h2 class="text-warning mb-3">Пополнение отменено</h2>

                        <p class="mb-4">
                            Платеж был отменен. Если это произошло по ошибке, вы можете попробовать снова.
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
                            <a href="{{ route('finances.deposit.form') }}" class="btn btn-primary btn-lg me-2">
                                <i class="fas fa-redo"></i> Попробовать снова
                            </a>
                            <a href="{{ route('finances.index') }}" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-history"></i> История транзакций
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
        color: #6c757d;
    }
</style>
@endsection