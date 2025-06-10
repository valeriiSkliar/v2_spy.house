<div class="tariff-pay mb-15">
    <div class="row align-items-center _offset30">
        <div class="col-12 col-md-auto">
            <div class="tariff-pay__name">
                <div class="tariff-name _{{ $tariff['css_class'] }}">{{ $tariff['name'] }}</div>
            </div>
        </div>
        <div class="col-auto">
            <div class="tariff-pay__info">Expiration Date: <strong>1 month</strong></div>
        </div>
        <div class="col-auto">
            <div class="tariff-pay__info">Cost: <strong>${{ $tariff['monthly_price'] }} USD</strong></div>
        </div>
    </div>
</div>