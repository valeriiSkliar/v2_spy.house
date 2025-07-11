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
                {{-- <div class="header__login">
                    <a href="#" class="btn _flex _orange font-16 font-weight-bold">Login</a>
                </div> --}}
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
    <section class="features">
        <div class="container">
            <div class="row align-items-end _offset30">
                <div class="col-12 col-md-6">
                    <div class="title-label" data-aos-delay="200" data-aos="fade-up">Features</div>
                    <h2 class="title" data-aos-delay="200" data-aos="fade-up">Find effective ads</h2>
                </div>
                <div class="col-12 col-md-6 pb-2">
                    <div class="section-desc icon-txt-dot mb-30" data-aos-delay="200" data-aos="fade-up">If you want to
                        save time - Use Spy.house analytics and launch profitable campaigns based on the successful
                        experience of competitors</div>
                </div>
            </div>
            <div class="features__list row _offset20">
                <div class="col-12 col-lg-7 d-flex" data-aos-delay="200" data-aos="fade-up">
                    <div class="feature-item _bg">
                        <h3>185+ countries of the world</h3>
                        <p>We collect creatives from almost all over the world and constantly increase this number. Rest
                            assured, no one will go unnoticed</p>
                    </div>
                </div>
                <div class="col-12 col-md-7 col-lg-5 d-flex" data-aos-delay="200" data-aos="fade-up">
                    <div class="feature-item">
                        <h3>12M daily creatives</h3>
                        <p>All your competitors' creatives can be found with us and save money and time</p>
                    </div>
                </div>
                <div class="col-12 col-md-5 col-lg-4 d-flex" data-aos-delay="200" data-aos="fade-up">
                    <div class="feature-item">
                        <h3>100% positive</h3>
                        <p>All users are satisfied with our service</p>
                    </div>
                </div>
                <div class="col-12 col-lg-8 d-flex" data-aos-delay="200" data-aos="fade-up">
                    <div class="feature-item">
                        <h3>16+ different filters to find the right ads</h3>
                        <p>Advanced search and filtering settings allow you to find exactly the creatives you need for
                            effective work</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <section class="creatives">
        <div class="container">
            <h2 class="title text-center" data-aos-delay="200" data-aos="fade-up">Creatives from all popular verticals
            </h2>
            <div class="after-title text-center" data-aos-delay="200" data-aos="fade-up">You will definitely find
                creatives in your vertical</div>
        </div>
        <div class="creatives-content-marquee" data-aos-delay="200" data-aos="fade-up">
            <div class="creatives-marquee mqscroller">
                <div class="creatives-item _width mqs-item">
                    <div class="creative-item">
                        <div class="creative-item__head">
                            <div class="creative-item__txt">
                                <div class="creative-item__active icon-dot">Active: 3 day</div>
                                <div class="text-with-copy">
                                    <div class="text-with-copy__btn">
                                        <button class="btn copy-btn _flex _dark js-copy"><span
                                                class="icon-copy"></span>Copy<span
                                                class="copy-btn__copied">Copied</span></button>
                                    </div>
                                    <div class="creative-item__title">⚡ What are the pensions the increase? 💰</div>
                                </div>
                                <div class="text-with-copy">
                                    <div class="text-with-copy__btn">
                                        <button class="btn copy-btn _flex _dark js-copy"><span
                                                class="icon-copy"></span>Copy<span
                                                class="copy-btn__copied">Copied</span></button>
                                    </div>
                                    <div class="creative-item__desc">How much did Kazakhstanis begin to receive</div>
                                </div>
                            </div>
                            <div class="creative-item__icon thumb thumb-with-controls-small">
                                <img src="img/th-2.jpg" alt="">
                                <div class="thumb-controls">
                                    <a href="#" class="btn-icon _black"><span class="icon-download2"></span></a>
                                </div>
                            </div>
                        </div>
                        <div class="creative-item__image thumb thumb-with-controls">
                            <img src="img/th-3.jpg" alt="">
                            <div class="thumb-controls">
                                <a href="#" class="btn-icon _black"><span class="icon-download2"></span></a>
                                <a href="#" class="btn-icon _black"><span class="icon-new-tab"></span></a>
                            </div>
                        </div>
                        <div class="creative-item__footer">
                            <div class="creative-item__info">
                                <div class="creative-item-info"><span class="creative-item-info__txt">Push.house</span>
                                </div>
                                <div class="creative-item-info"><img src="img/flags/KZ.svg" alt="">KZ</div>
                                <div class="creative-item-info">
                                    <div class="icon-pc"></div>PC
                                </div>
                            </div>
                            <div class="creative-item__btns">
                                <button class="btn-icon btn-favorite"><span class="icon-favorite-empty"></span></button>
                                <button class="btn-icon _dark js-show-details"><span class="icon-info"></span></button>
                            </div>
                        </div>
                    </div>
                    <div class="creative-item">
                        <div class="creative-item__head">
                            <div class="creative-item__icon thumb thumb-with-controls-small mr-2">
                                <img src="img/th-2.jpg" alt="">
                                <div class="thumb-controls">
                                    <a href="#" class="btn-icon _black"><span class="icon-download2"></span></a>
                                </div>
                            </div>
                            <div class="creative-item__txt">
                                <div class="creative-item__active icon-dot">Active: 3 day</div>
                                <div class="text-with-copy">
                                    <div class="text-with-copy__btn">
                                        <button class="btn copy-btn _flex _dark js-copy"><span
                                                class="icon-copy"></span>Copy<span
                                                class="copy-btn__copied">Copied</span></button>
                                    </div>
                                    <div class="creative-item__title">⚡ What are the pensions the increase? 💰</div>
                                </div>
                                <div class="text-with-copy">
                                    <div class="text-with-copy__btn">
                                        <button class="btn copy-btn _flex _dark js-copy"><span
                                                class="icon-copy"></span>Copy<span
                                                class="copy-btn__copied">Copied</span></button>
                                    </div>
                                    <div class="creative-item__desc">How much did Kazakhstanis begin to receive</div>
                                </div>
                            </div>
                        </div>
                        <div class="creative-item__footer">
                            <div class="creative-item__info">
                                <div class="creative-item-info"><span class="creative-item-info__txt">Push.house</span>
                                </div>
                                <div class="creative-item-info"><img src="img/flags/KZ.svg" alt="">KZ</div>
                                <div class="creative-item-info">
                                    <div class="icon-pc"></div>PC
                                </div>
                            </div>
                            <div class="creative-item__btns">
                                <button class="btn-icon btn-favorite "><span
                                        class="icon-favorite-empty"></span></button>
                                <button class="btn-icon _dark js-show-details"><span class="icon-info"></span></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="creatives-item mqs-item">
                    <div class="creative-item _facebook">
                        <div class="creative-video">
                            <div class="thumb">
                                <img src="img/facebook-2.jpg" alt="" class="thumb-blur">
                                <img src="img/facebook-2.jpg" alt="" class="thumb-contain">
                            </div>
                            <span class="icon-play"></span>
                            <div class="creative-video__time">00:45</div>
                            <div class="creative-video__content" data-video="img/video-3.mp4"> </div>
                        </div>
                        <div class="creative-item__row">
                            <div class="creative-item__icon thumb"><img src="img/icon-1.jpg" alt=""></div>
                            <div class="creative-item__title">Casino Slots</div>
                            <div class="creative-item__platform"><img src="img/facebook.svg" alt=""></div>
                        </div>
                        <div class="creative-item__row">
                            <div class="creative-item__desc font-roboto">Play Crown Casino online and claim up to 100%
                                bonus on your deposit and claim up to 100% bonus on your deposit</div>
                            <div class="creative-item__copy">
                                <button class="btn-icon js-copy _border-gray">
                                    <span class="icon-copy"></span>
                                    <span class="icon-check d-none"></span>
                                </button>
                            </div>
                        </div>
                        <div class="creative-item__social">
                            <div class="creative-item__social-item"><strong>285</strong> <span>Like</span></div>
                            <div class="creative-item__social-item"><strong>2</strong> <span>Comments</span></div>
                            <div class="creative-item__social-item"><strong>7</strong> <span>Shared</span></div>
                        </div>
                        <div class="creative-item__footer">
                            <div class="creative-item__info">
                                <div class="creative-status icon-dot font-roboto">Active: 3 day</div>
                            </div>
                            <div class="creative-item__btns">
                                <div class="creative-item-info"><img src="img/flags/KZ.svg" alt=""></div>
                                <button class="btn-icon btn-favorite"><span class="icon-favorite-empty"></span></button>
                                <button class="btn-icon _dark js-show-details"><span class="icon-info"></span></button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="creatives-item mqs-item">
                    <div class="creative-item _facebook">
                        <div class="creative-video">
                            <div class="thumb thumb-with-controls">
                                <img src="img/facebook-1.jpg" alt="">
                                <div class="thumb-controls">
                                    <a href="#" class="btn-icon _black"><span class="icon-download2"></span></a>
                                    <a href="#" class="btn-icon _black"><span class="icon-new-tab"></span></a>
                                </div>
                            </div>
                        </div>
                        <div class="creative-item__row">
                            <div class="creative-item__icon thumb"><img src="img/icon-1.jpg" alt=""></div>
                            <div class="creative-item__title">Casino Slots</div>
                            <div class="creative-item__platform"><img src="img/facebook.svg" alt=""></div>
                        </div>
                        <div class="creative-item__row">
                            <div class="creative-item__desc font-roboto">Play Crown Casino online and claim up to 100%
                                bonus on your deposit and claim up to 100% bonus on your deposit</div>
                            <div class="creative-item__copy">
                                <button class="btn-icon js-copy _border-gray">
                                    <span class="icon-copy"></span>
                                    <span class="icon-check d-none"></span>
                                </button>
                            </div>
                        </div>
                        <div class="creative-item__social">
                            <div class="creative-item__social-item"><strong>285</strong> <span>Like</span></div>
                            <div class="creative-item__social-item"><strong>2</strong> <span>Comments</span></div>
                            <div class="creative-item__social-item"><strong>7</strong> <span>Shared</span></div>
                        </div>
                        <div class="creative-item__footer">
                            <div class="creative-item__info">
                                <div class="creative-status icon-dot font-roboto">Active: 3 day</div>
                            </div>
                            <div class="creative-item__btns">
                                <div class="creative-item-info"><img src="img/flags/KZ.svg" alt=""></div>
                                <button class="btn-icon btn-favorite"><span class="icon-favorite-empty"></span></button>
                                <button class="btn-icon _dark js-show-details"><span class="icon-info"></span></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="creatives-tags-marquee mqscroller">
                <div class="creatives-tags-item mqs-item">E-commerce</div>
                <div class="creatives-tags-item mqs-item">Gambling</div>
                <div class="creatives-tags-item mqs-item">Dropshipping & CoD</div>
                <div class="creatives-tags-item mqs-item">Nutra</div>
                <div class="creatives-tags-item mqs-item">Swipstakes</div>
                <div class="creatives-tags-item mqs-item">Apps</div>
            </div>
        </div>
        <div class="container">
            <div class="text-center" data-aos-delay="200" data-aos="fade-up">
                <a href="#" class="btn _flex _green _large min-170">Get started</a>
            </div>
        </div>
    </section>
    @include('pages.homePage.prices')
    @include('pages.homePage.reviews')
    <section class="download-creatives">
        <div class="container">
            <div class="download-creatives__content" data-aos-delay="200" data-aos="fade-up">
                <div class="download-creatives__val">25<span>%</span> <span class="blick"></span></div>
                <h2 class="title">Download your competitors <br>creatives with a 25% discount</h2>
                <a href="#" class="btn _flex _green _large min-170">Get started</a>
                <div class="download-creatives-figure">
                    <div class="download-creatives-figure__content">Download</div>
                </div>
            </div>
        </div>
    </section>
    @include('pages.homePage.blogs')
    <footer class="footer">
        <div class="container">
            <div class="footer__content">
                <div class="footer__left">
                    <div class="footer__logo"><img src="img/logo.svg" alt="" width="167" height="43"></div>
                    <div class="footer__copy">© 2012 - 2022 Spy.House</div>
                    <div class="footer__lang">
                        <div class="lang-menu">
                            <div class="base-select">
                                <div class="base-select__trigger">
                                    <span class="base-select__value"><img src="img/flags/US.svg" alt="">Eng</span>
                                    <span class="base-select__arrow"></span>
                                </div>
                                <ul class="base-select__dropdown" style="display: none;">
                                    <li class="base-select__option is-selected"><img src="img/flags/US.svg" alt="">Eng
                                    </li>
                                    <li class="base-select__option"><img src="img/flags/UA.svg" alt="">Uk</li>
                                    <li class="base-select__option"><img src="img/flags/ES.svg" alt="">Esp</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="footer__center">
                    <div class="footer-nav _mob-two-col">
                        <p>Creatives Spy</p>
                        <ul>
                            <li><a href="#">Push</a></li>
                            <li><a href="#">In-page</a></li>
                            <li><a href="#">Facebook</a></li>
                            <li><a href="#">Tik Tok</a></li>
                        </ul>
                    </div>
                    <div class="footer-nav">
                        <p>Useful</p>
                        <ul>
                            <li><a href="#">Offers</a></li>
                            <li><a href="#">Services</a></li>
                            <li><a href="#">Affiliate program</a></li>
                            <li><a href="#">Blog</a></li>
                        </ul>
                    </div>
                    <div class="footer-nav">
                        <p>Other</p>
                        <ul>
                            <li><a href="#">Advertising</a></li>
                            <li><a href="#">Privacy Policy</a></li>
                            <li><a href="#">User Agreement</a></li>
                            <li><a href="#">FAQ</a></li>
                        </ul>
                    </div>
                </div>
                <div class="footer__right">
                    <div class="footer__telegram">
                        <a href="#" target="_blank" class="telegram-link">
                            <span class="icon-telegram2"></span>
                            <span class="telegram-link__label">Telegram</span>
                            <span class="telegram-link__nickname">@spyhouse_help</span>
                        </a>
                    </div>
                    <div class="footer__chat">
                        <a href="#" target="_blank" class="btn _flex _medium _blue min-200 w-100"><span
                                class="icon-chat font-16 mr-2"></span>Telegram chat</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <aside class="aside _home ">
        <div class="aside__content">
            <div class="aside__btn">
                <div class="aside__lang">
                    <div class="lang-menu mb-10">
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
                <a href="#" class="btn _flex w-100 mb-10 _dark">Login</a>
                <a href="#" class="btn _flex w-100 mb-10 _green">Registration</a>
            </div>
            <nav class="aside-menu">
                <ul>
                    <li><a href="#"><span class="aside-menu__txt">Features</span></a></li>
                    <li><a href="#"><span class="aside-menu__txt">Prices</span></a></li>
                    <li><a href="#"><span class="aside-menu__txt">Reviews</span></a></li>
                    <li><a href="#"><span class="aside-menu__txt">Blog</span></a></li>
                </ul>
            </nav>
            <div class="aside__contacts">
                <a data-toggle="modal" data-target="#modal-contacts" class="link">Contacts</a>
            </div>
        </div>
    </aside>
</div>
@endsection