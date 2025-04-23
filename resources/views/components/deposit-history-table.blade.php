@props(['transactions' => []])

<div class="c-table">
    <div class="inner">
        <table class="table thead-transparent no-wrap-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Transaction Number</th>
                    <th>Payment Method</th>
                    <th>Amount</th>
                    <th>Status</th>
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
                        <span class="table-status {{ $transaction['statusClass'] }}">
                            {{ $transaction['status'] }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>