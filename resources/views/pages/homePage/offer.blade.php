<section class="offer">
    <div class="offer__bg">
        <video autoplay muted playsinline preload="auto" loop>
            <source src="{{ Vite::asset('resources/scss/img/main/1.mp4') }}" type="video/mp4">
        </video>
    </div>
    <div class="container" data-aos-delay="600" data-aos="fade-up">
        <div class="row flex-row-reverse _offset30">
            <div class="col-12 col-md-5 align-self-end">
                <div class="offer-phone">
                    <div class="offer-phone__content">
                        <video autoplay muted playsinline preload="auto" loop>
                            <source src="{{ Vite::asset('resources/scss/img/main/screen.mp4') }}" type="video/mp4">
                        </video>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-7">
                <div class="offer__content">
                    <h1 class="offer__title">All competitors creatives are here</h1>
                    <script>
                        let points = [
                            '<span style="color: rgba(117, 175, 229, 0.70);">Facebook Ads</span>',
                            '<span style="color: rgba(233, 68, 90, 0.90);">TikTok Ads</span>',
                            '<span style="color: rgba(182, 229, 117, 0.70);">Push Ads</span>',
                            '<span style="color: rgba(229, 188, 117, 0.70);">In-Page Ads</span>',
                        ];
                    </script>
                    <div class="offer__desc">Spy House — advertising spy service for popular advertising formats
                        <br><span id="typeit"></span>
                    </div>
                    <div class="offer__row">
                        <div class="offer__btn">
                            <a href="#" class="btn _flex _green _large min-170">Get started</a>
                        </div>
                        <div class="offer__winner">
                            <div class="best-affiliate">
                                <img src="{{ Vite::asset('resources/scss/img/main/winner-2021.svg') }}" alt="">
                                <div class="best-affiliate__title">Best Affiliate Spy Tool</div>
                                <div class="best-affiliate__desc">Conversion club awards 2021</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>