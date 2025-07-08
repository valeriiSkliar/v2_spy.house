@extends('layouts.body')

@section('content')
<div class="wrapper _page pt-0">
    <header class="header _home">
        <div class="container" data-aos-delay="200" data-aos="fade-down">
            <div class="header__burger">
                <button class="btn-icon _dark js-menu">
                    <span class="menu-burger"><span></span><span></span><span></span><span></span></span>
                </button>
            </div>
            <div class="header__left">
                <a href="/" class="header__logo"><img src="img/logo.svg?v=2" alt=""></a>
            </div>
            <nav class="header__nav">
                <ul>
                    <li><a href="#">Features</a></li>
                    <li><a href="#">Prices</a></li>
                    <li><a href="#">Reviews</a></li>
                    <li><a href="{{ route('blog.index') }}">Blog</a></li>
                </ul>
            </nav>
            <div class="header__right">
                <div class="header__contacts">
                    <a data-toggle="modal" data-target="#modal-contacts" class="link">Contacts</a>
                </div>
                <div class="header__lang">
                    <div class="lang-menu">
                        <div class="base-select">
                            <div class="base-select__trigger">
                                <span class="base-select__value"><img src="img/flags/US.svg" alt="">Eng</span>
                                <span class="base-select__arrow"></span>
                            </div>
                            <ul class="base-select__dropdown" style="display: none;">
                                <li class="base-select__option is-selected"><img src="img/flags/US.svg" alt="">Eng</li>
                                <li class="base-select__option"><img src="img/flags/UA.svg" alt="">Uk</li>
                                <li class="base-select__option"><img src="img/flags/ES.svg" alt="">Esp</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="header__login">
                    <a href="#" class="btn _flex _orange font-16 font-weight-bold">Login</a>
                </div>
                <div class="header__login-mobile">
                    <a href="#" class="btn-icon _dark"><span class="icon-login font-20"></span></a>
                </div>
                <!-- User Login
                <div class="user-preview">
                    <a href="#" class="user-preview__trigger">
                        <div class="user-preview__avatar thumb"><span>LV</span></div>
                        <div class="user-preview__name">Lysenko V.</div>
                    </a>
                </div>
                -->
            </div>
        </div>
    </header>
    <section class="offer">
        <div class="offer__bg">
            <video autoplay muted playsinline preload="auto" loop>
                <source src="img/main/1.mp4" type="video/mp4">
            </video>
        </div>
        <div class="container" data-aos-delay="600" data-aos="fade-up">
            <div class="row flex-row-reverse _offset30">
                <div class="col-12 col-md-5 align-self-end">
                    <div class="offer-phone">
                        <div class="offer-phone__content">
                            <video autoplay muted playsinline preload="auto" loop>
                                <source src="img/main/screen.mp4" type="video/mp4">
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
                                    <img src="img/main/winner-2021.svg" alt="">
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
</div>
@endsection