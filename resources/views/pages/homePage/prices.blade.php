<section class="prices">
    <div class="container">
        <div class="row align-items-end _offset30">
            <div class="col-12 col-md-7 mb-md-4">
                <div class="title-label _white" data-aos-delay="200" data-aos="fade-up">Prices</div>
                <h2 class="title" data-aos-delay="200" data-aos="fade-up">Big investment for <br>little money</h2>
            </div>
            <div class="col-12 col-md-5 pb-2">
                <div class="section-desc icon-txt-dot mb-20" data-aos-delay="200" data-aos="fade-up">Expand your
                    capabilities with a Pro plan with an annual subscription</div>
                <div class="prices__tubs row justify-content-end mb-30 pt-4 pt-md-5" data-aos-delay="200"
                    data-aos="fade-up">
                    <div class="col-6 col-md-auto">
                        <button class="btn _flex _medium w-100 active" data-tub="month" data-group="pay">For a
                            Month</button>
                    </div>
                    <div class="col-6 col-md-auto">
                        <button class="btn _flex _medium w-100" data-tub="year" data-group="pay">For a year
                            @if(isset($subscriptions) && $subscriptions->first() &&
                            $subscriptions->first()->early_discount)
                            <span class="btn__count">-{{ number_format($subscriptions->first()->early_discount, 0)
                                }}%</span>
                            @else
                            <span class="btn__count">-20%</span>
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
                    <div class="price-item__tariff _{{ strtolower($subscription->name) }}">{{ $subscription->name }}
                    </div>

                    {{-- Monthly pricing --}}
                    <div class="active" data-tub="month" data-group="pay">
                        <div class="price-item__price">${{ number_format($subscription->amount, 0) }}</div>
                        <div class="price-item__term">per month</div>
                    </div>

                    {{-- Yearly pricing with discount --}}
                    <div data-tub="year" data-group="pay">
                        @php
                        $yearlyAmount = $subscription->amount * 12;
                        if ($subscription->early_discount) {
                        $yearlyAmount = $yearlyAmount * (1 - $subscription->early_discount / 100);
                        }
                        @endphp
                        <div class="price-item__price">${{ number_format($yearlyAmount, 0) }}</div>
                        <div class="price-item__term">per year</div>
                    </div>

                    <div class="price-item__info">
                        @if($subscription->search_request_count >= 10000)
                        <p><strong>Unlimited</strong> creative downloads</p>
                        @else
                        <p><strong>{{ number_format($subscription->search_request_count) }}</strong> creative downloads
                        </p>
                        @endif

                        @if($subscription->api_request_count >= 10000)
                        <p><strong>Unlimited</strong> API requests</p>
                        @else
                        <p><strong>{{ number_format($subscription->api_request_count) }}</strong> API requests</p>
                        @endif
                    </div>

                    <div class="price-item__btn">
                        <a href="{{ route('tariffs.payment', ['slug' => $subscription->getSlug(), 'billingType' => 'month']) }}"
                            class="btn _flex _border-green2 _large min-170">Get Started</a>
                    </div>
                </div>
            </div>
            @endforeach
            @else
            {{-- Fallback если подписки не загружены --}}
            <div class="prices-list__item">
                <div class="price-item">
                    <div class="price-item__tariff _starter">Starter</div>
                    <div class="active" data-tub="month" data-group="pay">
                        <div class="price-item__price">$30</div>
                        <div class="price-item__term">per month</div>
                    </div>
                    <div data-tub="year" data-group="pay">
                        <div class="price-item__price">$288</div>
                        <div class="price-item__term">per year</div>
                    </div>
                    <div class="price-item__info">
                        <p><strong>1,000</strong> creative downloads</p>
                        <p><strong>500</strong> API requests</p>
                    </div>
                    <div class="price-item__btn">
                        <a href="{{ route('register') }}" class="btn _flex _border-green2 _large min-170">Get
                            Started</a>
                    </div>
                </div>
            </div>
            <div class="prices-list__item">
                <div class="price-item">
                    <div class="price-item__tariff _premium">Premium</div>
                    <div class="active" data-tub="month" data-group="pay">
                        <div class="price-item__price">$100</div>
                        <div class="price-item__term">per month</div>
                    </div>
                    <div data-tub="year" data-group="pay">
                        <div class="price-item__price">$960</div>
                        <div class="price-item__term">per year</div>
                    </div>
                    <div class="price-item__info">
                        <p><strong>Unlimited</strong> creative downloads</p>
                        <p><strong>Unlimited</strong> API requests</p>
                    </div>
                    <div class="price-item__btn">
                        <a href="{{ route('register') }}" class="btn _flex _border-green2 _large min-170">Get
                            Started</a>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</section>