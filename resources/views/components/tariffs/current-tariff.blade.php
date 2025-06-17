<div class="rate__current">
    <div class="rate-current">
        <div class="rate-current__title">{{ $currentTariff['name'] }}</div>
        @if($currentTariff['status'] === 'Активная')
        <div class="rate-current__status">{{ $currentTariff['status'] }}</div>
        @else
        <div class="rate-current__status _disabled">{{ $currentTariff['status'] }}</div>
        @endif
        @if($currentTariff['expires_at'])
        <div class="rate-current__term">Действительна до <span>{{ $currentTariff['expires_at'] }}</span></div>
        @else
        <div class="rate-current__term">Бесплатный тариф</div>
        @endif
    </div>
</div>