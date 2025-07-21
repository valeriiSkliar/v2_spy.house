<div class="modal fade modal-contacts" id="modal-contacts" style="z-index: 10005;">
    <div class="modal-dialog">
        <div class="modal-content">
            <button type="button" class="btn-icon _gray btn-close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><span class="icon-x"></span></span>
            </button>
            <div class="modal-head">
                <h2 class="mb-2">{{ __('main_page.modals.contact.title') }}</h2>
                <p>{{ __('main_page.modals.contact.description') }}</p>
            </div>
            <div class="row">
                <div class="col-12 col-md-6 mb-10">
                    <a href="#" target="_blank" class="manager">
                        <span class="icon-telegram"></span>
                        <span class="manager__thumb"><img src="/img/manager-1.png" alt=""></span>
                        <span class="manager__content">
                            <span class="manager__name">{{ __('main_page.modals.contact.manager_maksim') }}</span>
                            <span class="manager__link">@Max_spy_house</span>
                        </span>
                    </a>
                </div>
                <div class="col-12 col-md-6 mb-10">
                    <a href="#" target="_blank" class="manager">
                        <span class="icon-telegram"></span>
                        <span class="manager__thumb"><img src="/img/manager-2.svg" alt=""></span>
                        <span class="manager__content">
                            <span class="manager__name">{{ __('main_page.modals.contact.telegram_chat') }}</span>
                            <span class="manager__link">@spy_house_chat</span>
                        </span>
                    </a>
                </div>
            </div>
            <div class="sep"></div>
            <h3 class="mb-2">{{ __('main_page.modals.contact.form_title') }}</h3>
            <p class="mb-20">{{ __('main_page.modals.contact.form_description') }}</p>
            <form action="">
                <div class="row _offset20">
                    <div class="col-12 col-md-6 mb-15">
                        <input type="text" placeholder="{{ __('main_page.modals.contact.name_placeholder') }}">
                    </div>
                    <div class="col-12 col-md-6 mb-15">
                        <input type="email" placeholder="{{ __('main_page.modals.contact.email_placeholder') }}">
                    </div>
                    <div class="col-12 mb-15">
                        <textarea placeholder="{{ __('main_page.modals.contact.message_placeholder') }}"></textarea>
                    </div>
                    <div class="col-12 mb-15">
                        <button type="submit" class="btn _flex _green _medium min-120 w-mob-100">{{
                            __('main_page.modals.contact.send_button') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade modal-contacts" id="add-review" style="z-index: 10005;">
    <div class="modal-dialog">
        <div class="modal-content">
            <button type="button" class="btn-icon _gray btn-close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true"><span class="icon-x"></span></span>
            </button>
            <div class="modal-head">
                <h2 class="mb-2">{{ __('main_page.modals.review.title') }}</h2>
            </div>
            <form action="">
                <div class="row _offset20">
                    <div class="col-12 mb-15">
                        <textarea placeholder="{{ __('main_page.modals.review.review_placeholder') }}"></textarea>
                    </div>
                    <div class="col-12 mb-15">
                        <div class="rate-service max-w-full">
                            <div class="row align-items-center _offset30">
                                <div class="col-12 col-md-8 pt-2 pb-2">
                                    <h4>{{ __('main_page.modals.review.rating_title') }}</h4>
                                    <p class="mb-0">{{ __('main_page.modals.review.rating_description') }}</p>
                                </div>
                                <div class="col-12 col-md-4 pt-2 pb-2">
                                    <div class="rate-service__rating"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 mb-15">
                        <button type="submit" class="btn _flex _green min-120 w-mob-100">{{
                            __('main_page.modals.review.send_button') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Push modal to global stack --}}
@if(auth()->check())
@php
$user = auth()->user();
$currentTariff = $user->currentTariff();
@endphp
<x-modals.subscribtion-activated :currentTariff="$currentTariff" />
@endif

{{-- Global modal container for dynamic modals --}}
<div id="global-modal-container"></div>