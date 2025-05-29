@props(['status', 'statusClass' => ''])

@php
$statusKey = match($status) {
'Payment expected', 'Ожидается оплата' => 'pending',
'Successful', 'Успешно' => 'successful',
'Rejected', 'Отклонено' => 'rejected',
default => 'pending'
};
@endphp

<span class="table-status {{ $statusClass }}">
    {{ __('finances.history_table.statuses.' . $statusKey) }}
</span>