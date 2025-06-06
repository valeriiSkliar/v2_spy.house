<div class="creative-details__head">
    <div class="row align-items-center">
        <div class="col-auto mr-auto">
            <h2 class="mb-0">{{ __('creatives.details.title') }}</h2>
        </div>
        @if(!isset($hideFavorite) || !$hideFavorite)
        <div class="col-auto {{ isset($mobileOnly) && $mobileOnly ? 'd-md-none' : '' }}">
            <button
                class="btn _flex _gray _small btn-favorite {{ isset($isFavorite) && $isFavorite ? 'active' : '' }} {{ isset($fullWidth) && $fullWidth ? 'w-100' : '' }}">
                <span
                    class=" icon-favorite {{ isset($isFavorite) && $isFavorite ? '' : '-empty' }} font-16 mr-2"></span>
                {{ isset($isFavorite) && $isFavorite ? __('creatives.details.remove-from-favorites') :
                __('creatives.details.add-to-favorites') }}
            </button>
        </div>
        @endif
        <div class="col-auto">
            <button class="btn-icon _dark js-hide-details"><span class="icon-x remore_margin font-18"></span></button>
        </div>
    </div>
</div>