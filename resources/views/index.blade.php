@extends('layouts.body')

@section('content')
<div class="wrapper _page pt-0">
    @include('pages.homePage.header')
    @include('pages.homePage.offer')
    @include('pages.homePage.features')
    @include('pages.homePage.creatives')
    @include('pages.homePage.prices')
    @include('pages.homePage.reviews')
    @include('pages.homePage.download-creatives')
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
    @include('pages.homePage.aside')
</div>
@endsection