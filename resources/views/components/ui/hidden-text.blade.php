<div class="hidden-txt _creative">
    <div class="hidden-txt__content">
        <p class="font-roboto font-16">
            {{ $text }}
        </p>
    </div>
    <div class="row align-items-center justify-content-between pt-2">
        <div class="col-auto">
            <a class="link _gray js-toggle-txt" data-show="{{ __('creatives.details.show-all-text') }}"
                data-hide="{{ __('creatives.details.hide-text') }}">{{ __('creatives.details.show-all-text') }}</a>
        </div>
        @if(isset($showTranslate) && $showTranslate)
        <div class="col-auto">
            <button class="btn _flex _gray _medium"><span class="icon-translate font-18 mr-2"></span>{{
                __('creatives.details.translate') }}</button>
        </div>
        @endif
    </div>
</div>