<div class="tariff-pay mb-15">
    <div class="row align-items-center _offset30">
        <div class="col-12 col-md-auto">
            <div class="tariff-pay__name">
                <div class="tariff-name _{{ strtolower($tariff->name) }}">{{ $tariff->name }}</div>
            </div>
        </div>
        <div class="col-auto">
            <div class="tariff-pay__info">Срок действия: <strong>1 {{ $tariff->getBillingPeriodName($billingType)
                    }}</strong></div>
        </div>
        <div class="col-auto">
            <div class="tariff-pay__info">Стоимость: <strong>{{ $tariff->getFormattedAmountByBillingType($billingType)
                    }} USD</strong></div>
        </div>
        @if($billingType === 'year' && $tariff->early_discount > 0)
        <div class="col-auto">
            <div class="tariff-pay__info">Скидка: <strong>{{ $tariff->early_discount }}%</strong></div>
        </div>
        @endif
    </div>
</div>