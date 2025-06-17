@extends('layouts.app')

@section('content')
<div class="finance-deposit-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Пополнение баланса</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="balance-info mb-4">
                                    <h5>Текущий баланс</h5>
                                    <div class="balance-amount">
                                        ${{ number_format(auth()->user()->available_balance, 2) }}
                                    </div>
                                </div>

                                <form action="{{ route('finances.deposit') }}" method="POST">
                                    @csrf

                                    <div class="form-group mb-3">
                                        <label for="amount" class="form-label">Сумма пополнения (USD)</label>
                                        <input type="number" class="form-control @error('amount') is-invalid @enderror"
                                            id="amount" name="amount" min="5" max="10000" step="0.01"
                                            value="{{ old('amount') }}" placeholder="Введите сумму от $5 до $10,000"
                                            required>
                                        @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group mb-3">
                                        <label for="payment_method" class="form-label">Способ оплаты</label>
                                        <select class="form-select @error('payment_method') is-invalid @enderror"
                                            id="payment_method" name="payment_method" required>
                                            <option value="">Выберите способ оплаты</option>
                                            @foreach($paymentMethods as $method)
                                            @if(in_array($method['value'], ['USDT', 'PAY2.HOUSE']))
                                            <option value="{{ $method['value'] }}" {{
                                                old('payment_method')==$method['value'] ? 'selected' : '' }}>
                                                {{ $method['name'] }}
                                            </option>
                                            @endif
                                            @endforeach
                                        </select>
                                        @error('payment_method')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary btn-lg w-100">
                                            Пополнить баланс
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="col-md-6">
                                <div class="deposit-info">
                                    <h5>Информация о пополнении</h5>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success"></i> Минимальная сумма: $5</li>
                                        <li><i class="fas fa-check text-success"></i> Максимальная сумма: $10,000</li>
                                        <li><i class="fas fa-check text-success"></i> Комиссия: 0%</li>
                                        <li><i class="fas fa-check text-success"></i> Зачисление: мгновенно</li>
                                    </ul>

                                    <div class="mt-4">
                                        <h6>Поддерживаемые способы оплаты:</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fab fa-bitcoin text-warning"></i> USDT</li>
                                            <li><i class="fas fa-credit-card text-primary"></i> Банковские карты</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <a href="{{ route('finances.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Назад к истории транзакций
                    </a>
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

    .deposit-info {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 0.5rem;
    }

    .deposit-info li {
        margin-bottom: 0.5rem;
    }

    .deposit-info i {
        margin-right: 0.5rem;
        width: 1rem;
    }
</style>
@endsection