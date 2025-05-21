@props(['service', 'route' => 'services.show', 'target' => '_self', 'buttonText' => 'More info'])
<div class="col-12 col-md-6 col-lg-4 col-xl-3 d-flex">
    <div class="market-list__item">
        <div class="market-list__thumb">
            <img src="{{ $service['logo'] }}" alt="{{ $service['name'] }}" />
        </div>
        <h4>
            {{ $service['name'] }}
        </h4>
        <div class="market-list__desc">
            {{ $service['description'] }}
        </div>
        <ul class="market-list__info">
            <li class="icon-view">{{ number_format($service['views']) }}</li>
            <li class="icon-link">{{ number_format($service['transitions']) }}</li>
            <li class="icon-star">{{ number_format($service['rating'], 1) }}</li>
        </ul>
        <div class="market-list__btn">
            <a href="{{ route($route, $service['id']) }}" class="btn _flex _border-green w-100" target="{{ $target }}">
                <span>{{ $buttonText }} <span class="icon-more"></span></span>
            </a>
        </div>
    </div>
</div>