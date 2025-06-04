<div class="creative-item">
    <div class="creative-item__head">
        <div class="creative-item__txt">
            <div class="creative-item__active {{ isset($isActive) && $isActive ? 'icon-dot' : '' }}">{{ $activeText ??
                'Active: 3 day' }}</div>
            <div class="text-with-copy">
                <div class="text-with-copy__btn">
                    @include('components.ui.copy-button')
                </div>
                <div class="creative-item__title">{{ $title ?? '⚡ What are the pensions the increase? 💰' }}</div>
            </div>
            <div class="text-with-copy">
                <div class="text-with-copy__btn">
                    @include('components.ui.copy-button')
                </div>
                <div class="creative-item__desc">{{ $description ?? 'How much did Kazakhstanis begin to receive' }}
                </div>
            </div>
        </div>
        <div class="creative-item__icon thumb thumb-with-controls-small">
            <img src="{{ $icon ?? '/img/th-2.jpg' }}" alt="">
            <div class="thumb-controls">
                <a href="#" class="btn-icon _black"><span class="icon-download2"></span></a>
            </div>
        </div>
    </div>
    <div class="creative-item__image thumb thumb-with-controls">
        <img src="{{ $image ?? '/img/th-3.jpg' }}" alt="">
        <div class="thumb-controls">
            <a href="#" class="btn-icon _black"><span class="icon-download2"></span></a>
            <a href="#" class="btn-icon _black"><span class="icon-new-tab"></span></a>
        </div>
    </div>
    <div class="creative-item__footer">
        <div class="creative-item__info">
            <div class="creative-item-info"><span class="creative-item-info__txt">{{ $network ?? 'Push.house' }}</span>
            </div>
            <div class="creative-item-info"><img src="{{ $flagIcon ?? '/img/flags/KZ.svg' }}" alt="">{{ $country ?? 'KZ'
                }}</div>
            <div class="creative-item-info">
                <div class="icon-{{ $deviceType ?? 'pc' }}"></div>{{ $deviceText ?? 'PC' }}
            </div>
        </div>
        <div class="creative-item__btns">
            <button class="btn-icon btn-favorite {{ isset($isFavorite) && $isFavorite ? 'active' : '' }}">
                <span class="icon-favorite{{ isset($isFavorite) && $isFavorite ? '' : '-empty' }} remore_margin"></span>
            </button>
            <button class="btn-icon _dark js-show-details"><span class="icon-info remore_margin"></span></button>
        </div>
    </div>
</div>