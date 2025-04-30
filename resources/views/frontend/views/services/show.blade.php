@extends('layouts.authorized')

@section('page-content')
<div class="mb-20">
    <a href="{{ route('services.index') }}" class="btn _flex _medium _gray"><span class="icon-prev mr-2 font-18"></span> To the list of services</a>
</div>
<div class="single-market">
    <div class="row _offset30">
        <div class="col-12 col-md-auto">
            <div class="single-market__thumb">
                <img src="{{ $service['logo'] }}" alt="{{ $service['name'] }}">
            </div>
        </div>
        <div class="col-12 col-md-auto w-1 flex-grow-1">
            <div class="row align-items-center _offset30">
                <div class="col-12 col-md-auto order-md-3">
                    <div class="single-market__link">
                        <a href="{{ route('services.redirect', $service['id']) }}" class="site-link" target="_blank"><span class="icon-link"></span>{{ $service['url'] }}</a>
                    </div>
                </div>
                <div class="col-12 col-md-auto">
                    <h1>{{ $service['name'] }}</h1>
                </div>
                <div class="col-12 col-md-auto">
                    <ul class="single-market__info">
                        <li class="icon-view">{{ number_format($service['views']) }}</li>
                        <li class="icon-link">{{ number_format($service['transitions']) }}</li>
                        <li class="icon-star">{{ $service['rating'] }}</li>
                    </ul>
                </div>
            </div>
            <div class="single-market__desc">
                <div class="hidden-txt">
                    <div class="hidden-txt__content">{{ $service['description'] }}</div>
                    <a class="js-toggle-txt" data-show="Читать больше" data-hide="Скрыть">Читать больше</a>
                </div>
            </div>
        </div>
    </div>
    <div class="row align-items-end _offset30">
        <div class="col-12 col-md-auto col-lg-auto pb-3">
            <a href="{{ route('services.redirect', $service['id']) }}" target="_blank" class="btn w-100 _flex _green">
                <span class="btn__text">Перейти</span>
                <span class="icon-next ml-3 font-18"></span>
            </a>
        </div>
        <div class="col-12 col-md-auto col-lg-auto pb-3 d-flex align-items-center justify-content-center">
            <div class="single-market__code">
                <button class="btn _code _flex w-100 js-toggle-code">Показать промокод</button>
                <div class="form-item pb-0">
                    <div class="form-item__field _copy mb-0">
                        <input type="text" readonly="" value="{{ $service['code'] }}">
                        <button type="button" class="btn-copy">
                            <span class="icon-copy"></span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="single-market__percent">-{{ $service['code_description'] }}</div>
        </div>
        <div class="col-12 col-lg-auto flex-grow-1 pb-3">
            <div class="rate-service">
                <div class="row align-items-center _offset30">
                    <div class="col-12 col-md-5">
                        <h4>Оцени сервис</h4>
                        <p class="mb-0">Поставьте оценку от 1 до 5</p>
                    </div>
                    <div class="col-12 col-md-7 d-flex align-items-center justify-content-center">
                        <div class="rate-service__rating" data-service-id="{{ $service['id'] }}"></div>
                        <div class="rate-service__sep"></div>
                        <div class="rate-service__value">{{ $service['rating'] }}/5</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<h2>Offers from other companies</h2>
<div class="market-list">
    <div class="row _offset15">
        @foreach($relatedServices as $relatedService)
        <div class="col-12 col-md-6 col-lg-4 col-xl-3 d-flex">
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
        </div>
        @endforeach
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle read more/less
        const toggleButtons = document.querySelectorAll('.js-toggle-txt');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const container = this.closest('.hidden-txt');
                container.classList.toggle('is-open');

                if (container.classList.contains('is-open')) {
                    this.textContent = this.dataset.hide || 'Скрыть';
                } else {
                    this.textContent = this.dataset.show || 'Читать больше';
                }
            });
        });

        // Toggle promo code
        const promoCodeButtons = document.querySelectorAll('.js-toggle-code');
        promoCodeButtons.forEach(button => {
            button.addEventListener('click', function() {
                const container = this.closest('.single-market__code');
                container.classList.toggle('is-open');

                if (container.classList.contains('is-open')) {
                    this.textContent = 'Скрыть промокод';
                } else {
                    this.textContent = 'Показать промокод';
                }
            });
        });

        // Copy to clipboard
        const copyButtons = document.querySelectorAll('.btn-copy');
        copyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = this.closest('.form-item__field').querySelector('input');
                input.select();
                document.execCommand('copy');

                // Show copied indicator
                this.classList.add('copied');
                setTimeout(() => {
                    this.classList.remove('copied');
                }, 2000);
            });
        });

        // Star rating functionality
        const ratingContainers = document.querySelectorAll('.rate-service__rating');
        // if (ratingContainers.length > 0) {
        //     ratingContainers.forEach(container => {
        //         const serviceId = container.dataset.serviceId;

        //         $(container).starRating({
        //             initialRating: {
        //                 {
        //                     $service['rating']
        //                 }
        //             },
        //             strokeColor: '#894A00',
        //             strokeWidth: 10,
        //             starSize: 25,
        //             disableAfterRate: false,
        //             useFullStars: true,
        //             hoverColor: '#ffb700',
        //             activeColor: '#ffb700',
        //             ratedColor: '#ffb700',
        //             useGradient: false,
        //             callback: function(currentRating, el) {
        //                 // Send rating to server via AJAX
        //                 $.ajax({
        //                     url: "{{ route('services.rate', $service['id']) }}",
        //                     type: "POST",
        //                     data: {
        //                         rating: currentRating,
        //                         _token: "{{ csrf_token() }}"
        //                     },
        //                     success: function(response) {
        //                         if (response.success) {
        //                             // Update rating display
        //                             $('.rate-service__value').text(currentRating + '/5');
        //                         }
        //                     },
        //                     error: function() {
        //                         alert("Error saving rating. Please try again.");
        //                     }
        //                 });
        //             }
        //         });
        //     });
        // }
    });
</script>
@endsection