<div class="rate__current">
    <div class="rate-current">
        <div class="rate-current__title">{{ $currentTariff['name'] }}</div>
        @php
        $statusKey = match($currentTariff['status']) {
        'Активная' => 'active',
        'Неактивная' => 'inactive',
        'Истекла' => 'expired',
        default => 'inactive'
        };
        $isActive = $currentTariff['status'] === 'Активная';
        @endphp
        @if($isActive)
        <div class="rate-current__status">{{ __('tariffs.current_tariff.status.' . $statusKey) }}</div>
        @else
        <div class="rate-current__status _disabled">{{ __('tariffs.current_tariff.status.' . $statusKey) }}</div>
        @endif
        @if($currentTariff['expires_at'])
        <div class="rate-current__term">{!! __('tariffs.current_tariff.valid_until', ['date' =>
            $currentTariff['expires_at']]) !!}</div>
        @else
        <div class="rate-current__term">{{ __('tariffs.current_tariff.free_tariff') }}</div>
        @endif
    </div>
</div>