@props([
        'serviceId', 
        'rating' => 0, 
        'isRated' => false, 
        'userRating' => null, 
        'header' => 'Оцени сервис', 
        'description' => 'Поставьте оценку от 1 до 5'
    ])
<div class="col-12 col-lg-auto flex-grow-1 pb-3">
    <div class="rate-service">
        <div class="row align-items-center _offset30">
            <div class="col-12 col-md-5">
                <h4>{{ $header }}</h4>
                <p class="mb-0">{{ $description }}</p>
            </div>
            <div class="col-12 col-md-7 d-flex align-items-center justify-content-center">
                <div class="rate-service__rating" data-service-id="{{ $serviceId }}"></div>
                <div class="rate-service__sep"></div>
                <div class="rate-service__value">{{ $rating }}/5</div>
            </div>
        </div>
    </div>
</div>