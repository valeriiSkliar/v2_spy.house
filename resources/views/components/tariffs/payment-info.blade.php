{{-- Уведомления о типе операции --}}
{{-- @if($isRenewal ?? false) --}}
{{-- <div class="alert alert-info mb-15">
    <div class="d-flex align-items-center">
        <svg class="me-2" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
            <path
                d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z" />
        </svg>
        <span>Продление подписки "{{ $tariff->name }}" @if(isset($currentEndDate) && $currentEndDate)до {{
            $currentEndDate->copy()->add($billingType === 'year' ? '1 year' : '1 month')->format('d.m.Y')
            }}@endif</span>
    </div>
</div> --}}
@if($isUpgrade ?? false)
<div class="alert alert-warning mb-15">
    <div class="d-flex align-items-center">
        <svg class="me-2" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
            <path
                d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
        </svg>
        <span>Смена тарифа на "{{ $tariff->name }}" начнется после окончания текущей подписки @if(isset($currentEndDate)
            && $currentEndDate)({{ $currentEndDate->format('d.m.Y') }})@endif</span>
    </div>
</div>
@endif

<div class="tariff-pay mb-15">
    <div class="row align-items-center _offset30">
        <div class="col-12 col-md-auto">
            <div class="tariff-pay__name">
                <div class="tariff-name _{{ strtolower($tariff->name) }}">{{ $tariff->name }}</div>
            </div>
        </div>
        <div class="col-auto">
            <div class="tariff-pay__info">
                @if($isRenewal ?? false)
                {{ __('tariffs.payment_info.renewal_on') }} <strong>1 {{ $tariff->getBillingPeriodName($billingType)
                    }}</strong>
                @else
                {{ __('tariffs.payment_info.subscription_period') }} <strong>1 {{
                    $tariff->getBillingPeriodName($billingType) }}</strong>
                @endif
            </div>
        </div>
        <div class="col-auto">
            <div class="tariff-pay__info">{{ __('tariffs.payment_info.price') }} <strong>{{
                    $tariff->getFormattedAmountByBillingType($billingType)
                    }}</strong></div>
        </div>
        @if($billingType === 'year' && $tariff->early_discount > 0)
        <div class="col-auto">
            <div class="tariff-pay__info">{{ __('tariffs.payment_info.discount') }} <strong>{{ $tariff->early_discount
                    }}%</strong></div>
        </div>
        @endif
    </div>
</div>