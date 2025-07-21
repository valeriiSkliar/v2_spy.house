<div class="rate-item _{{ App\Helpers\tariff_name_mapping($tariff->name) }}">
    <div class="rate-item-head">
        <div class="rate-item-head__title">{{ $tariff->name }}</div>
        <div class="active" data-tub="month" data-group="pay">
            <div class="rate-item-head__price">${{ $tariff->amount }}</div>
            <div class="rate-item-head__term">{{ __('tariffs.per_month') }}</div>
        </div>
        <div data-tub="year" data-group="pay">
            <div class="rate-item-head__price">${{ number_format($tariff->amount_yearly ) }}</div>
            <div class="rate-item-head__term">{{ __('tariffs.per_year') }}</div>
            @if($tariff->early_discount > 0)
            <div class="rate-item-head__discount">{{ __('tariffs.discount_percent', ['percent' =>
                $tariff->early_discount]) }}</div>
            @endif
        </div>
    </div>
    <div class="rate-item-body">
        <div class="rate-item-body__desc">
            <p><strong>{{ $tariff->search_request_count }}</strong> {{ __('tariffs.search_requests') }}</p>
            {{-- <p><strong>{{ $tariff->api_request_count }}</strong> запросов API</p> --}}
        </div>
        @for($i = 0; $i < 7; $i++) <div class="rate-item-body__row"><span class="icon-check"></span>
    </div>
    @endfor
    <div class="rate-item-body__hidden">
        @for($i = 0; $i < 5; $i++) <div class="rate-item-body__row">
            @if($i === 4 && (strtolower($tariff->name) === 'premium' || strtolower($tariff->name) ===
            'enterprise'))
            <span>{{ __('tariffs.priority_support') }}</span>
            @else
            <span class="icon-check"></span>
            @endif
    </div>
    @endfor
</div>
</div>
<div class="rate-item-bottom">
    @if($currentTariff['id'] === $tariff->id)
    <a href="{{ $tariff->getPaymentUrl('month') }}" class="btn w-100 _flex _medium _border-green tariff-renew-btn"
        data-tariff-slug="{{ $tariff->getSlug() }}" data-billing-type="month">{{ __('tariffs.extend') }}</a>
    @else
    <a href="{{ $tariff->getPaymentUrl('month') }}" class="btn w-100 _flex _medium _green tariff-select-btn"
        data-tariff-slug="{{ $tariff->getSlug() }}" data-billing-type="month">{{ __('tariffs.select') }}</a>
    @endif
</div>
</div>