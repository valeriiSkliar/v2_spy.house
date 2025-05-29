@props(['transactions' => []])

<div class="c-table">
    <div class="inner">
        <table class="table thead-transparent no-wrap-table">
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
                    <td>{{ $transaction['date'] }}</td>
                    <td><span class="font-weight-600">{{ $transaction['transactionNumber'] }}</span></td>
                    <td>{{ $transaction['paymentMethod'] }}</td>
                    <td><span class="font-weight-bold">${{ $transaction['amount'] }} </span></td>
                    <td>
                        <x-finances.transaction-status :status="$transaction['status']"
                            :statusClass="$transaction['statusClass']" />
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>