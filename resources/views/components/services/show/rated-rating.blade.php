@props(['userRating', 'formattedRating'])
<div class="rate-service__success">
    <div class="row align-items-center ">
        <div class="col-12 col-md-5">
            <h4>Thank you for rating!</h4>
            <p class="mb-0">Your rating: <strong>{{ $userRating }}</strong> stars</p>
        </div>
        <div class="col-12 col-md-7 d-flex align-items-center justify-content-center">
            <div class="rate-service__value"><span class="font-weight-600">{{ $formattedRating }}</span>/5</div>
        </div>
    </div>
</div>