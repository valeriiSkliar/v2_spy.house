<footer class="footer">
    <div class="container">
        <div class="footer__content">
            <div class="footer__left">
                <div class="footer__logo"><img src="img/logo.svg" alt="" width="167" height="43"></div>
                <div class="footer__copy">{{ __('main_page.footer.copyright', ['year' => date('Y')]) }}</div>
                <div class="footer__lang">
                    {{-- <div class="lang-menu">
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
                    </div> --}}
                    <x-frontend.language-selector />
                </div>
            </div>
            <div class="footer__center">
                <div class="footer-nav _mob-two-col">
                    <p>{{ __('main_page.footer.creatives_spy_title') }}</p>
                    <ul>
                        <li><a href="{{ route('creatives.index') }}?cr_activeTab=push">{{ __('main_page.footer.push')
                                }}</a></li>
                        <li><a href="{{ route('creatives.index') }}?cr_activeTab=inpage">{{
                                __('main_page.footer.inpage') }}</a></li>
                        <li><a href="{{ route('creatives.index') }}?cr_activeTab=facebook">{{
                                __('main_page.footer.facebook') }}</a></li>
                        <li><a href="{{ route('creatives.index') }}?cr_activeTab=tiktok">{{
                                __('main_page.footer.tiktok') }}</a></li>
                    </ul>
                </div>
                <div class="footer-nav">
                    <p>{{ __('main_page.footer.useful_title') }}</p>
                    <ul>
                        <li><a href="{{ route('landings.index') }}">{{ __('main_page.footer.offers') }}</a></li>
                        <li><a href="{{ route('services.index') }}">{{ __('main_page.footer.services') }}</a></li>
                        <li><a href="{{ route('profile.settings') }}#referrals">{{
                                __('main_page.footer.affiliate_program') }}</a></li>
                        <li><a href="{{ route('blog.index') }}">{{ __('main_page.footer.blog') }}</a></li>
                    </ul>
                </div>
                <div class="footer-nav">
                    <p>{{ __('main_page.footer.other_title') }}</p>
                    <ul>
                        <li><a href="mailto:advertising@spy.house">{{ __('main_page.footer.advertising') }}</a></li>
                        <li><a href="{{ route('terms') }}#privacy">{{ __('main_page.footer.privacy_policy') }}</a></li>
                        <li><a href="{{ route('terms') }}#agreement">{{ __('main_page.footer.user_agreement') }}</a>
                        </li>
                        <li><a href="{{ route('terms') }}#faq">{{ __('main_page.footer.faq') }}</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer__right">
                <div class="footer__telegram">
                    <a href="https://t.me/spyhouse_help" target="_blank" class="telegram-link">
                        <span class="icon-telegram2"></span>
                        <span class="telegram-link__label">{{ __('main_page.footer.telegram_label') }}</span>
                        <span class="telegram-link__nickname">{{ __('main_page.footer.telegram_nickname') }}</span>
                    </a>
                </div>
                <div class="footer__chat">
                    <a href="https://t.me/spyhouse_chat" target="_blank"
                        class="btn _flex _medium _blue min-200 w-100"><span class="icon-chat font-16 mr-2"></span>{{
                        __('main_page.footer.telegram_chat') }}</a>
                </div>
            </div>
        </div>
    </div>
</footer>