<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Spy.House</title>

    <!-- Fonts -->
    <!-- <link rel="preconnect" href="https://fonts.bunny.net">  
    <link href="https://fonts.bunny.net/css?family=Montserrat:100,200,300,400,500,600,700,800,900" rel="stylesheet" /> -->

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite([ 'resources/js/app.js', 'resources/scss/app.scss'])
    @else
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @endif
</head>

<body class=" text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col">
    <div class="navigation-bg"></div>
    <div class="wrapper">
        <header class="header">
            <div class="header__burger">
                <button class="btn-icon _dark js-menu">
                    <span class="menu-burger"><span></span><span></span><span></span><span></span></span>
                </button>
            </div>
            <div class="header__left">
                <a href="/" class="header__logo"><img src="img/logo.svg" alt="" width="142" height="36"></a>
            </div>
            <div class="header__right">
                <div class="header__tariff">
                    <a href="#" class="tariff-link">Trial</a>
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
                <div class="user-preview">
                    <div class="user-preview__trigger">
                        <div class="user-preview__avatar thumb"><span>LV</span></div>
                        <div class="user-preview__name">Lysenko V.</div>
                        <div class="btn-icon _dark">
                            <span class="icon-settings"></span>
                            <span class="has-notification"></span>
                        </div>
                    </div>
                    <div class="user-preview__dropdown" style="display: none">
                        <nav class="user-menu">
                            <ul>
                                <li><a href="#"><span class="icon-notification"><span class="has-notification"></span></span> <span>Notifications</span></a></li>
                                <li><a href="#"><span class="icon-settings"></span> <span>Profile Settings</span></a></li>
                                <li><a href="#"><span class="icon-tariffs"></span> <span>Tariffs</span></a></li>
                            </ul>
                            <button class="btn _flex _gray w-100 font-16"><span class="icon-logout mr-3"></span>Log out</button>
                        </nav>
                    </div>
                </div>
            </div>
        </header>
        <main class="main">
            <div class="content">
                <h1>Pages</h1>
                <ul class="menu-demo">
                    <li><a href="creatives_push.html">Creatives Push</a></li>
                    <li><a href="creatives_inpage.html">Creatives InPage</a></li>
                    <li><a href="creatives_fb.html">Creatives Facebook/TikTok</a></li>
                    <li><a href="services_1.html">Services 1</a></li>
                    <li><a href="services_2.html">Services 2</a></li>
                    <li><a href="landings_1.html">Landings 1</a></li>
                    <li><a href="landings_2.html">Landings 2</a></li>
                    <li><a href="profile_1.html">Profile </a></li>
                    <li><a href="profile_change-password.html">Profile - Change password</a></li>
                    <li><a href="notifications.html">Notifications</a></li>
                    <li><a href="tariffs.html">Tariffs</a></li>
                    <li><a href="tariffs_pay.html">Tariffs - Pay</a></li>
                    <li><a data-toggle="modal" data-target="#modal-subscription-activated">Subscription activated</a></li>
                    <li><a href="finances.html">Finances</a></li>
                    <li><a data-toggle="modal" data-target="#modal-contacts">Contacts</a></li>
                    <li><a href="blog.html">Blog</a></li>
                    <li><a href="blog_single.html">Blog Single</a></li>
                    <li><a href="api.html">API</a></li>
                </ul>

                <div class="modal fade modal-subscription-activated" id="modal-subscription-activated" style="z-index: 10005;">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <button type="button" class="btn-icon _gray btn-close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true"><span class="icon-x"></span></span>
                            </button>
                            <div class="subscription-activated">
                                <div class="subscription-activated-icon icon-check-circle">
                                    <div class="tariff-name _start">Start</div>
                                </div>
                                <h2 class="font-20 mb-20">Subscription activated</h2>
                                <p class="mb-30">
                                    Your <strong>"Start"</strong> subscription is active. <br>
                                    Valid until: <span class="icon-clock"></span> <strong>02.05.25</strong>
                                </p>
                                <div class="row justify-content-center">
                                    <div class="col-auto">
                                        <button class="btn _flex _green _medium min-120">Ok</button>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn _flex _gray _medium min-120">Change tariff</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade modal-contacts" id="modal-contacts" style="z-index: 10005;">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <button type="button" class="btn-icon _gray btn-close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true"><span class="icon-x"></span></span>
                            </button>
                            <div class="modal-head">
                                <h2 class="mb-2">Сontacts</h2>
                                <p>If you have any questions, you can write to any of our managers</p>
                            </div>
                            <div class="row">
                                <div class="col-12 col-md-6 mb-10">
                                    <a href="#" target="_blank" class="manager">
                                        <span class="icon-telegram"></span>
                                        <span class="manager__thumb"><img src="img/manager-1.png" alt=""></span>
                                        <span class="manager__content">
                                            <span class="manager__name">Maksim</span>
                                            <span class="manager__link">@Max_spy_house</span>
                                        </span>
                                    </a>
                                </div>
                                <div class="col-12 col-md-6 mb-10">
                                    <a href="#" target="_blank" class="manager">
                                        <span class="icon-telegram"></span>
                                        <span class="manager__thumb"><img src="img/manager-2.svg" alt=""></span>
                                        <span class="manager__content">
                                            <span class="manager__name">Telegram chat</span>
                                            <span class="manager__link">@spy_house_chat</span>
                                        </span>
                                    </a>
                                </div>
                            </div>
                            <div class="sep"></div>
                            <h3 class="mb-2">Or use the form below</h3>
                            <p class="mb-20">If you have any suggestions or wishes, please write to us.</p>
                            <form action="">
                                <div class="row _offset20">
                                    <div class="col-12 col-md-6 mb-15">
                                        <input type="text" placeholder="Name">
                                    </div>
                                    <div class="col-12 col-md-6 mb-15">
                                        <input type="email" placeholder="G-mail">
                                    </div>
                                    <div class="col-12 mb-15">
                                        <textarea placeholder="Message"></textarea>
                                    </div>
                                    <div class="col-6 mb-15">
                                        <img src="img/reCAPTCHA%20v2%20checkbox.png" alt="" class="w-100">
                                    </div>
                                    <div class="col-12 mb-15">
                                        <button type="submit" class="btn _flex _green _medium min-120 w-mob-100">Send</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>


                <div style="background: #243035; padding: 10px 10px 5px; border-radius: 10px; margin-bottom: 20px; margin-top: 20px;">
                    <div class="row">
                        <div class="col-auto mb-2"><a href="#" class="tariff-link">Free</a></div>
                        <div class="col-auto mb-2"><a href="#" class="tariff-link _start">Start</a></div>
                        <div class="col-auto mb-2"><a href="#" class="tariff-link _basic">Basic</a></div>
                        <div class="col-auto mb-2"><a href="#" class="tariff-link _premium">Premium</a></div>
                        <div class="col-auto mb-2"><a href="#" class="tariff-link _enterprise">Enterprise</a></div>
                    </div>
                </div>
                <h2 class="mb-10">Base select</h2>
                <div class="section mb-20">
                    <div class="base-select">
                        <div class="base-select__trigger">
                            <span class="base-select__value">Eng</span>
                            <span class="base-select__arrow"></span>
                        </div>
                        <ul class="base-select__dropdown" style="display: none;">
                            <li class="base-select__option is-selected">Eng</li>
                            <li class="base-select__option">Uk</li>
                            <li class="base-select__option">Esp</li>
                        </ul>
                    </div>
                </div>
                <h2 class="mb-10">Multi select</h2>
                <div class="section mb-20">
                    <div class="row">
                        <div class="col-12 col-md-6 mb-10">
                            <div class="filter-section">
                                <div class="multi-select" disabled="false">
                                    <div class="is-empty multi-select__tags"><span class="multi-select__placeholder">Select OS</span></div>
                                    <div class="multi-select__dropdown" style="display: none;">
                                        <div class="multi-select__search"><input type="text" placeholder="Search" class="multi-select__search-input"></div>
                                        <ul class="multi-select__options">
                                            <li class=""><!----> android</li>
                                            <li class=""><!----> blackberry</li>
                                            <li class=""><!----> bot</li>
                                            <li class=""><!----> chromeos</li>
                                            <li class=""><!----> ios</li>
                                            <li class=""><!----> kindle</li>
                                            <li class=""><!----> linux</li>
                                            <li class=""><!----> macosx</li>
                                            <li class=""><!----> other</li>
                                            <li class=""><!----> playstation</li>
                                            <li class=""><!----> unknown</li>
                                            <li class=""><!----> webos</li>
                                            <li class=""><!----> windows</li>
                                            <li class=""><!----> windowsphone</li>
                                            <li class=""><!----> xbox</li>
                                        </ul>
                                    </div><span class="multi-select__arrow"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 mb-10">
                            <div class="filter-section">
                                <div class="multi-select" disabled="false">
                                    <div class="multi-select__tags">
                                        <span class="multi-select__tag">webos <button type="button" class="multi-select__remove"> × </button></span>
                                        <span class="multi-select__tag">windowsphone <button type="button" class="multi-select__remove"> × </button></span>
                                        <span class="multi-select__tag">xbox <button type="button" class="multi-select__remove"> × </button></span>
                                    </div>
                                    <div class="multi-select__dropdown" style="display: none;">
                                        <div class="multi-select__search"><input type="text" placeholder="Search" class="multi-select__search-input"></div>
                                        <ul class="multi-select__options">
                                            <li class=""><!----> android</li>
                                            <li class=""><!----> blackberry</li>
                                            <li class=""><!----> bot</li>
                                            <li class=""><!----> chromeos</li>
                                            <li class=""><!----> ios</li>
                                            <li class=""><!----> kindle</li>
                                            <li class=""><!----> linux</li>
                                            <li class=""><!----> macosx</li>
                                            <li class=""><!----> other</li>
                                            <li class=""><!----> playstation</li>
                                            <li class=""><!----> unknown</li>
                                            <li class="selected"><!----> webos</li>
                                            <li class=""><!----> windows</li>
                                            <li class="selected"><!----> windowsphone</li>
                                            <li class="selected"><!----> xbox</li>
                                        </ul>
                                    </div><span class="multi-select__arrow"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <aside class="aside">
                <div class="aside__head">
                    <div class="aside__lang">
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
                    <div class="aside__tariff">
                        <a href="#" class="tariff-link _enterprise">Enterprise</a>
                    </div>
                </div>
                <div class="aside__content">
                    <nav class="aside-menu">
                        <ul>
                            <li><a href="#" class="active"><span class="icon-creatives"></span> <span class="aside-menu__txt">Creatives</span></a></li>
                            <li><a href="#"><span class="icon-landings"></span> <span class="aside-menu__txt">Landings</span></a></li>
                            <li><a href="#"><span class="icon-offers"></span> <span class="aside-menu__txt">Offers</span></a></li>
                            <li><a href="#"><span class="icon-ai"></span> <span class="aside-menu__txt">Creative AI</span></a></li>
                            <li><a href="#"><span class="icon-services"></span> <span class="aside-menu__txt">Services</span></a></li>
                            <li><a href="#"><span class="icon-finance"></span> <span class="aside-menu__txt">Finance</span></a></li>
                            <li><a href="#"><span class="icon-program"></span> <span class="aside-menu__txt">Referrals</span></a></li>
                            <li><a href="#"><span class="icon-blog"></span> <span class="aside-menu__txt">Blog</span> <span class="aside-menu__count">100</span></a></li>
                            <li><a href="#"><span class="icon-faq"></span> <span class="aside-menu__txt">FAQ</span></a></li>
                        </ul>
                    </nav>
                    <div class="promo-tariff">
                        <img src="img/premium.svg" alt="">
                        <div class="promo-tariff__title">Upgrade to Premium</div>
                        <div class="promo-tariff__desc"><span>-50%</span> In the first month</div>
                        <div class="promo-tariff__row">
                            <a href="#" class="btn _flex _green _medium">Go</a>
                            <div class="promo-tariff__timer">08:45:27</div>
                        </div>
                    </div>
                    <div class="aside__copyright">© 2012 - 2025 Spy.House</div>
                </div>
                <div class="sep _h20"></div>
                <a href="#" target="_blank" class="banner-item"><img src="img/52400c8bd4719323579fd1a074fff985.gif" alt="" class="d-block w-100 rounded-10"></a>
            </aside>
        </main>
    </div>


    @if (Route::has('login'))
    <div class="h-14.5 hidden lg:block"></div>
    @endif
</body>

</html>