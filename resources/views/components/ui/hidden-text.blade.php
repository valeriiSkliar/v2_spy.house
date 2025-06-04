<div class="hidden-txt _creative">
    <div class="hidden-txt__content">
        <p class="font-roboto font-16">
            {{ $text }}
        </p>
    </div>
    <div class="row align-items-center justify-content-between pt-2">
        <div class="col-auto">
            <a class="link _gray js-toggle-txt" data-show="Show all text" data-hide="Hide">Show all text</a>
        </div>
        @if(isset($showTranslate) && $showTranslate)
        <div class="col-auto">
            <button class="btn _flex _gray _medium"><span class="icon-translate font-18 mr-2"></span>Translate
                text</button>
        </div>
        @endif
    </div>
</div>