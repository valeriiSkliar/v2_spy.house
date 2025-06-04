<div class="creative-item _{{ $type ?? 'facebook' }}">
    @include('components.creatives.creative-video', [
    'image' => $image ?? '/img/facebook-2.jpg',
    'blurImage' => isset($hasVideo) && $hasVideo ? ($image ?? '/img/facebook-2.jpg') : null,
    'hasVideo' => $hasVideo ?? false,
    'duration' => $duration ?? '00:45',
    'videoSrc' => $videoSrc ?? '/img/video-3.mp4',
    'controls' => $controls ?? false,
    'showNewTab' => $showNewTab ?? false
    ])
    <div class="creative-item__row">
        <div class="creative-item__icon thumb"><img src="{{ $icon ?? '/img/icon-1.jpg' }}" alt=""></div>
        <div class="creative-item__title">{{ $title ?? 'Casino Slots' }}</div>
        <div class="creative-item__platform"><img src="/img/{{ $type ?? 'facebook' }}.svg" alt=""></div>
    </div>
    <div class="creative-item__row">
        <div class="creative-item__desc font-roboto">{{ $description ?? 'Play Crown Casino online and claim up to 100%
            bonus on your deposit and claim up to 100% bonus on your deposit' }}</div>
        <div class="creative-item__copy">
            <button class="btn-icon js-copy _border-gray">
                <span class="icon-copy remore_margin"></span>
                <span class="icon-check d-none"></span>
            </button>
        </div>
    </div>
    <div class="creative-item__social">
        <div class="creative-item__social-item"><strong>{{ $likes ?? '285' }}</strong> <span>Like</span></div>
        <div class="creative-item__social-item"><strong>{{ $comments ?? '2' }}</strong> <span>Comments</span></div>
        <div class="creative-item__social-item"><strong>{{ $shares ?? '7' }}</strong> <span>Shared</span></div>
    </div>
    <div class="creative-item__footer">
        <div class="creative-item__info">
            <div class="creative-status icon-dot font-roboto">{{ $activeText ?? 'Active: 3 day' }}</div>
        </div>
        <div class="creative-item__btns">
            <div class="creative-item-info"><img src="{{ $flagIcon ?? '/img/flags/KZ.svg' }}" alt=""></div>
            <button class="btn-icon btn-favorite"><span class="icon-favorite-empty remore_margin"></span></button>
            <button class="btn-icon _dark js-show-details"><span class="icon-info remore_margin"></span></button>
        </div>
    </div>
</div>