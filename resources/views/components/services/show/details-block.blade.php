@props(['service', 'isPromo' => false, 'userRating' => null])
<div data-service-id="{{ $service['id'] }}" class="single-market">
    <div class="row _offset30">
        <div class="col-12 col-md-auto">
            <div class="single-market__thumb">
                <img src="{{ $service['logo'] }}" alt="{{ $service['name'] }}">
            </div>
        </div>
        <div class="col-12 col-md-auto w-1 flex-grow-1">
            <div class="row align-items-center _offset30">
                <div class="col-12 col-md-auto order-md-3">
                    <x-services.show.follow-button
                        :service="$service"
                        :route="'services.redirect'"
                        :target="'_blank'"
                        :buttonText="'Follow'"
                        :variant="'link'"
                    />
                </div>
                <div class="col-12 col-md-auto">
                    <h1>{{ $service['name'] }}</h1>
                </div>
                <div class="col-12 col-md-auto">
                    <x-services.show.meta-data 
                        :service="$service" 
                    />
                </div>
            </div>
            <x-services.show.description 
                :service="$service" 
                :buttonText="'Read more'"
                :showText="'Read more'"
                :hideText="'Hide'"
            />
        </div>
    </div>
    <div class="row align-items-end _offset30">
        <div class="col-12 col-md-auto col-lg-auto pb-3">
            <x-services.show.follow-button
                :service="$service"
                :route="'services.redirect'"
                :target="'_blank'"
                :buttonText="'Follow'"
            />
        </div>
        <div class="col-12 col-md-auto col-lg-auto pb-3 d-flex align-items-center justify-content-center">
            @if ($isPromo)
                <x-services.show.promo-code
                    :service="$service"
                    :buttonText="'Show promo code'"
                />
            @endif
        </div>
        @php
            $isRated = $userRating ? true : false;
        @endphp
            <x-services.show.rating 
                :serviceId="$service['id']" 
                :rating="$service['rating']" 
                :isRated="$isRated" 
                :userRating="$userRating" 
                :header="'Rate this service'" 
                :description="'Rate from 1 to 5'" 
            />

    </div>
</div>


{{-- {
    "message": "Rating submitted successfully",
    "rating": {
        "user_id": 1,
        "rating": "4",
        "service_id": 3,
        "updated_at": "2025-05-05T11:33:58.000000Z",
        "created_at": "2025-05-05T11:33:58.000000Z",
        "id": 342
    },
    "averageRating": "4.0000"
} --}}