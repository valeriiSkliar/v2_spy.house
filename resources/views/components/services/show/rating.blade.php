@props([
    'serviceId', 
    'rating' => 0, 
    'isRated' => false, 
    'userRating' => null, 
    'header' => 'Rate this service', 
    'description' => 'Rate from 1 to 5'
])

<div class="col-12 col-lg-auto flex-grow-1 pb-3">
    <div class="rate-service">
        @if (!$isRated)
        <div class="row align-items-center _offset30">
            <div class="col-12 col-md-5">
                <h4>{{ $header }}</h4>
                <p class="mb-0">{{ $description }}</p>
            </div>
            <div class="col-12 col-md-7 d-flex align-items-center justify-content-center">
                <div 
                    class="rate-service__rating" 
                    data-service-id="{{ $serviceId }}" 
                    data-rating="{{ $rating }}"
                    data-is-rated="{{ $isRated ? 'true' : 'false' }}"
                ></div>
                <div class="rate-service__sep"></div>
                <div class="rate-service__value"><span class="font-weight-600">{{ $rating }}</span>/5</div>
            </div>
        </div>
        @endif
        @if ($isRated)
            <x-services.show.rated-rating 
                :userRating="$userRating" 
                :formattedRating="$rating" 
            />
        @endif
    </div>
</div>
