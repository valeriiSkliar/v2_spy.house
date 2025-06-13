@props(['payments'])

@php
$status_classes = [
'PENDING' => 'warning',
'SUCCESS' => 'successful',
'FAILED' => 'rejected',
];
@endphp

@foreach($payments as $payment)
<tr>
    <td>{{ Carbon\Carbon::parse($payment->created_at)->format('d.m.Y / H:i') }}</td>
    <td>
        <div class="tariff-name _{{ strtolower($payment->subscription?->name) }} _small">{{
            $payment->subscription?->name }}</div>
    </td>
    <td><span class="font-weight-600">{{ $payment->external_number }}</span></td>
    <td>{{ $payment->payment_type->translatedLabel() }}</td>
    <td>{{ $payment->payment_method->translatedLabel() }}</td>
    <td><span class="font-weight-bold">${{ $payment->amount }} </span></td>
    <td><span class="table-status _{{ $status_classes[$payment->status->value] }}">{{
            $payment->status->translatedLabel()
            }}</span>
    </td>
</tr>
@endforeach