@props(['transactions'])

@if ($transactions->isNotEmpty())
<x-finances.deposit-history-table :transactions="$transactions" />
@else
<p>{{ __('finances.deposit_history_empty') }}</p>
@endif