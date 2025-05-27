<footer class="footer">
    <div class="container">
        <div class="footer__content">
            <div class="footer__left">
                <div class="footer__logo"><img src="/img/logo.svg" alt="" width="167" height="43"></div>
                <div class="footer__copy">{{ __('footer.copyright', ['year' => date('Y')]) }}</div>
                <div class="footer__lang">
                    <x-frontend.language-selector />
                </div>
            </div>
            <div class="footer__center">
                <div class="footer-nav _mob-two-col">
                    <p>{{ __('footer.creatives_spy_title') }}</p>
                    <ul>
                        <li><a href="#">{{ __('footer.push') }}</a></li>
                        <li><a href="#">{{ __('footer.in_page') }}</a></li>
                        <li><a href="#">{{ __('footer.facebook') }}</a></li>
                        <li><a href="#">{{ __('footer.tiktok') }}</a></li>
                    </ul>
                </div>
                <div class="footer-nav">
                    <p>{{ __('footer.useful_title') }}</p>
                    <ul>
                        <li><a href="#">{{ __('footer.offers') }}</a></li>
                        <li><a href="#">{{ __('footer.services') }}</a></li>
                        <li><a href="#">{{ __('footer.affiliate_program') }}</a></li>
                        <li><a href="#">{{ __('footer.blog') }}</a></li>
                    </ul>
                </div>
                <div class="footer-nav">
                    <p>{{ __('footer.other_title') }}</p>
                    <ul>
                        <li><a href="#">{{ __('footer.advertising') }}</a></li>
                        <li><a href="#">{{ __('footer.privacy_policy') }}</a></li>
                        <li><a href="#">{{ __('footer.user_agreement') }}</a></li>
                        <li><a href="#">{{ __('footer.faq') }}</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer__right">
                <div class="footer__telegram">
                    <a href="#" target="_blank" class="telegram-link">
                        <span class="icon-telegram2"></span>
                        <span class="telegram-link__label">{{ __('footer.telegram_label') }}</span>
                        <span class="telegram-link__nickname">{{ __('footer.telegram_nickname') }}</span>
                    </a>
                </div>
                <div class="footer__chat">
                    <a href="#" target="_blank" class="btn _flex _medium _blue min-200 w-100"><span
                            class="icon-chat font-16 mr-2"></span>{{ __('footer.telegram_chat_button') }}</a>
                </div>
            </div>
        </div>
    </div>
</footer>