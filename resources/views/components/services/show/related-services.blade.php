@props(['relatedServices' => [], 'title' => 'Offers from other companies'])

<h2>{{ $title }}</h2>
<div class="market-list">
    <div class="row _offset15">
        @foreach($relatedServices as $relatedService)
        {{-- <div class="col-12 col-md-6 col-lg-4 col-xl-3 d-flex">
            <div class="market-list__item">
                <div class="market-list__thumb"><img src="{{ $relatedService['logo'] }}" alt="{{ $relatedService['name'] }}"></div>
                <h4>{{ $relatedService['name'] }}</h4>
                <div class="market-list__desc">{{ $relatedService['description'] }}</div>
                <ul class="market-list__info">
                    <li class="icon-view">{{ number_format($relatedService['views']) }}</li>
                    <li class="icon-link">{{ number_format($relatedService['transitions']) }}</li>
                    <li class="icon-star">{{ $relatedService['rating'] }}</li>
                </ul>
                <div class="market-list__btn">
                    <a href="{{ route('services.show', $relatedService['id']) }}" class="btn _flex _border-green w-100"><span>Подробнее <span class="icon-more"></span></span></a>
                </div>
            </div>
        </div> --}}
        <x-services.service-card :service="$relatedService" :route="'services.show'" :target="'_blank'" :buttonText="'More info'" />
        @endforeach
    </div>
</div>