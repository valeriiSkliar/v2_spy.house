<div class="creative-social mb-25">
    <div class="creative-social__item">
        <span>{{ __('creatives.card.likes') }}</span> <span class="icon-like remore_margin"></span>
        <div class="creative-social__val">{{ $likes ?? '12 285' }}</div>
    </div>
    <div class="creative-social__item">
        <span>{{ __('creatives.card.comments') }}</span> <span class="icon-comment remore_margin"></span>
        <div class="creative-social__val">{{ $comments ?? '76' }}</div>
    </div>
    <div class="creative-social__item">
        <span>{{ __('creatives.card.shares') }}</span> <span class="icon-shared remore_margin"></span>
        <div class="creative-social__val">{{ $shares ?? '145' }}</div>
    </div>
</div>