@props(['transactions'])

<div class="c-table">
    <div class="inner">
        <table class="table thead-transparent no-wrap-table">
            {{-- @dd($transactions) --}}
            <thead>
                <tr>
                    <th>{{ __('finances.history_table.date') }}</th>
                    <th>{{ __('finances.history_table.transaction_number') }}</th>
                    <th>{{ __('finances.history_table.payment_method') }}</th>
                    <th>{{ __('finances.history_table.amount') }}</th>
                    <th>{{ __('finances.history_table.status') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $transaction)
                <tr>
                    <td>{{ Carbon\Carbon::parse($transaction->created_at)->format('d.m.Y / H:i') }}</td>
                    <td><span class="font-weight-600">{{ $transaction->external_number }}</span></td>
                    <td>{{ $transaction->payment_method->translatedLabel() }}</td>
                    <td><span class="font-weight-bold">${{ $transaction->amount }} </span></td>
                    <td>
                        <x-finances.transaction-status :status="$transaction->status" />
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>