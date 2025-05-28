<div class="promo-tariff">
    <img src="/img/premium.svg" alt="">
    <div class="promo-tariff__title">@lang('sidebar.upgrade_title')</div>
    <div class="promo-tariff__desc">@lang('sidebar.upgrade_desc')</div>
    <div class="promo-tariff__row">
        <a href="#" class="btn _flex _green _medium">@lang('sidebar.go_button')</a>
        <div class="promo-tariff__timer">{{ Carbon\Carbon::now()->addDays(30)->format('H:i:s') }}</div>
    </div>
</div>