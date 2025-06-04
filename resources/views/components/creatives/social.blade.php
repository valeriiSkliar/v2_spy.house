<div class="creatives-list _social">
    <div class="creatives-list__items">
        @include('components.creatives.creative-item-social', [
        'type' => $type,
        'image' => '/img/facebook-2.jpg',
        'hasVideo' => true,
        'videoSrc' => '/img/video-3.mp4'
        ])

        @include('components.creatives.creative-item-social', [
        'type' => $type,
        'image' => '/img/facebook-1.jpg',
        'hasVideo' => false,
        'controls' => true,
        'showNewTab' => true
        ])
    </div>

    @include('components.creatives.social-details', ['type' => $type, 'isFavorite' => true])
</div>