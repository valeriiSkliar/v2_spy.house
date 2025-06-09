@props(['status', 'statusClass' => ''])

@php
use App\Enums\Finance\PaymentStatus;

// Если status - это строка, пытаемся найти соответствующий enum
if (is_string($status)) {
$paymentStatus = match($status) {
'Payment expected', 'Ожидается оплата', 'PENDING' => PaymentStatus::PENDING,
'Successful', 'Успешно', 'SUCCESS' => PaymentStatus::SUCCESS,
'Rejected', 'Отклонено', 'FAILED' => PaymentStatus::FAILED,
default => PaymentStatus::PENDING
};
} else {
// Если уже enum, используем как есть
$paymentStatus = $status instanceof PaymentStatus ? $status : PaymentStatus::PENDING;
}

$statusKey = strtolower($paymentStatus->name);
@endphp

<span class="table-status {{ $statusClass }}">
    {{ $paymentStatus->translatedLabel() }}
</span>