<div class="col-12 col-md-6 col-lg-4 col-xl-3 d-flex">
    <div class="market-list__item">
        <div class="market-list__thumb"><a href="{{ route('services.show', $service['id']) }}"><img src="{{ $service['logo'] }}" alt="{{ $service['name'] }}"></a></div>
        <h4><a href="{{ route('services.show', $service['id']) }}">{{ $service['name'] }}</a></h4>
        <div class="market-list__desc"><a href="{{ route('services.show', $service['id']) }}">{{ $service['description'] }}</a></div>
        <ul class="market-list__info">
            <li class="icon-view">{{ number_format($service['views']) }}</li>
            <li class="icon-link">{{ number_format($service['transitions']) }}</li>
            <li class="icon-star">{{ number_format($service['rating'], 1) }}</li>
        </ul>
        <div class="market-list__btn">
            <a href="{{ route('services.redirect', $service['id']) }}" class="btn _flex _border-green w-100" target="_blank">
                <span>Visit Site <span class="icon-more"></span></span>
            </a>
        </div>
    </div>
</div>