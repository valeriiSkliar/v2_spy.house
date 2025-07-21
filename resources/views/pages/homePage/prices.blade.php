<section id="prices" class="prices">
    <div class="container">
        <div class="row align-items-end _offset30">
            <div class="col-12 col-md-7 mb-md-4">
                <div class="title-label _white" data-aos-delay="200" data-aos="fade-up">{{ __('tariffs.prices_title') }}
                </div>
                <h2 class="title" data-aos-delay="200" data-aos="fade-up">{!! __('main_page.prices_blok.title') !!}</h2>
            </div>
            <div class="col-12 col-md-5 pb-2">
                <div class="section-desc icon-txt-dot mb-20" data-aos-delay="200" data-aos="fade-up">{!!
                    __('main_page.prices_blok.description') !!}</div>
                <div class="prices__tubs row justify-content-end mb-30 pt-4 pt-md-5" data-aos-delay="200"
                    data-aos="fade-up">
                    <div class="col-6 col-md-auto">
                        <button class="btn _flex _medium w-100 active" data-tub="month" data-group="pay">{!!
                            __('main_page.prices_blok.button') !!}</button>
                    </div>
                    <div class="col-6 col-md-auto">
                        <button class="btn _flex _medium w-100" data-tub="year" data-group="pay">{!!
                            __('main_page.prices_blok.button_year') !!}
                            @if(isset($subscriptions) && $subscriptions->first() &&
                            $subscriptions->first()->early_discount)
                            <span class="btn__count">-{{ number_format($subscriptions->first()->early_discount, 0)
                                }}%</span>
                            @else
                            <span class="btn__count">-{{ __('main_page.prices_blok.button_year_discount', ['discount' =>
                                $subscriptions->first()->early_discount]) }}</span>
                            @endif
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="prices-list" data-aos-delay="200" data-aos="fade-up">
            @if(isset($subscriptions) && $subscriptions->count() > 0)
            @foreach($subscriptions as $subscription)
            <div class="prices-list__item">
                <div class="price-item">
                    <div class="price-item__tariff _{{ App\Helpers\tariff_name_mapping($subscription->name) }}">{{
                        $subscription->name }}
                    </div>

                    {{-- Monthly pricing --}}
                    <div class="active" data-tub="month" data-group="pay">
                        <div class="price-item__price">${{ number_format($subscription->amount, 0) }}</div>
                        <div class="price-item__term">{{ __('tariffs.per_month') }}</div>
                    </div>

                    {{-- Yearly pricing with discount --}}
                    <div data-tub="year" data-group="pay">
                        @php
                        $yearlyAmount = $subscription->amount_yearly ?? $subscription->amount * 12;
                        if ($subscription->early_discount && !$subscription->amount_yearly) {
                        $yearlyAmount = $yearlyAmount * (1 - $subscription->early_discount / 100);
                        }
                        @endphp
                        <div class="price-item__price">${{ number_format($yearlyAmount, 0) }}</div>
                        <div class="price-item__term">{{ __('tariffs.per_year') }}</div>
                    </div>

                    <div class="price-item__info">
                        @if($subscription->search_request_count >= 10000)
                        <p><strong>{{ __('tariffs.unlimited') }}</strong> {{ __('tariffs.creative_downloads') }}</p>
                        @else
                        <p><strong>{{ number_format($subscription->search_request_count) }}</strong> {{
                            __('tariffs.creative_downloads') }}</p>
                        @endif

                        @if($subscription->api_request_count >= 10000)
                        <p><strong>{{ __('tariffs.unlimited') }}</strong> {{ __('tariffs.api_requests') }}</p>
                        @else
                        <p><strong>{{ number_format($subscription->api_request_count) }}</strong> {{
                            __('tariffs.api_requests') }}</p>
                        @endif
                    </div>

                    <div class="price-item__btn">
                        <a href="{{ route('tariffs.payment', ['slug' => $subscription->getSlug(), 'billingType' => 'month']) }}"
                            class="btn _flex _border-green2 _large min-170">{{ __('tariffs.get_started') }}</a>
                    </div>
                </div>
            </div>
            @endforeach
            @else
            {{-- Fallback if subscriptions are not loaded --}}
            <div class="prices-list__item">
                <div class="price-item">
                    <div class="price-item__tariff _starter">{{ __('tariffs.start') }}</div>
                    <div class="active" data-tub="month" data-group="pay">
                        <div class="price-item__price">${{ __('tariffs.start_price_month') }}</div>
                        <div class="price-item__term">{{ __('tariffs.per_month') }}</div>
                    </div>
                    <div data-tub="year" data-group="pay">
                        <div class="price-item__price">${{ __('tariffs.start_price_year') }}</div>
                        <div class="price-item__term">{{ __('tariffs.per_year') }}</div>
                    </div>
                    <div class="price-item__info">
                        <p><strong>{{ __('tariffs.start_search_request_count') }}</strong> {{
                            __('tariffs.creative_downloads') }}</p>
                        <p><strong>{{ __('tariffs.start_api_request_count') }}</strong> {{ __('tariffs.api_requests') }}
                        </p>
                    </div>
                    <div class="price-item__btn">
                        <a href="{{ route('register') }}" class="btn _flex _border-green2 _large min-170">{{
                            __('tariffs.get_started') }}</a>
                    </div>
                </div>
            </div>
            <div class="prices-list__item">
                <div class="price-item">
                    <div class="price-item__tariff _premium">{{ __('tariffs.premium') }}</div>
                    <div class="active" data-tub="month" data-group="pay">
                        <div class="price-item__price">${{ __('tariffs.premium_price_month') }}</div>
                        <div class="price-item__term">{{ __('tariffs.per_month') }}</div>
                    </div>
                    <div data-tub="year" data-group="pay">
                        <div class="price-item__price">${{ __('tariffs.premium_price_year') }}</div>
                        <div class="price-item__term">{{ __('tariffs.per_year') }}</div>
                    </div>
                    <div class="price-item__info">
                        <p><strong>{{ __('tariffs.unlimited') }}</strong> {{ __('tariffs.creative_downloads') }}</p>
                        <p><strong>{{ __('tariffs.unlimited') }}</strong> {{ __('tariffs.api_requests') }}</p>
                    </div>
                    <div class="price-item__btn">
                        <a href="{{ route('register') }}" class="btn _flex _border-green2 _large min-170">{{
                            __('tariffs.get_started') }}</a>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</section>