<div class="creative-details__group">
    <h3 class="mb-20">Similar creatives</h3>
    <div class="promo-premium">
        <p>Similar ads are available in the <strong>Premium plan</strong></p>
        <a href="#" class="btn _flex _green _medium">Go</a>
    </div>
    <div class="similar-creatives {{ isset($socialClass) && $socialClass ? '_social' : '' }}">
        <div class="similar-creative-empty {{ isset($inpageClass) && $inpageClass ? '_inpage' : '' }}"><img
                src="/img/empty.svg" alt=""></div>
        <div class="similar-creative-empty {{ isset($inpageClass) && $inpageClass ? '_inpage' : '' }}"><img
                src="/img/empty.svg" alt=""></div>
        @if(isset($creativeComponent))
        {!! $creativeComponent !!}
        @endif
        @if(isset($hasSecondCreative) && $hasSecondCreative && isset($secondCreativeComponent))
        {!! $secondCreativeComponent !!}
        @endif
    </div>
    <div class="d-flex justify-content-center pt-3">
        <button class="btn _gray _flex _medium w-mob-100"><span class="icon-load-more font-16 mr-2"></span>Load
            more</button>
    </div>
</div>