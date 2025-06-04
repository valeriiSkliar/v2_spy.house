<div class="creatives-list">
    <div class="creatives-list__items">
        @include('components.creatives.creative-item-push', [
        'isActive' => true,
        'activeText' => trans_choice('creatives.card.active', $activeDays ?? 3, ['count' => $activeDays ?? 3]),
        'icon' => '/img/th-2.jpg',
        'image' => '/img/th-3.jpg',
        'isFavorite' => true,
        'deviceType' => 'pc',
        'deviceText' => 'PC'
        ])

        @include('components.creatives.creative-item-push', [
        'isActive' => false,
        'activeText' => trans_choice('creatives.card.was-active', $activeDays ?? 12, ['count' => $activeDays ?? 12]),
        'icon' => '/img/th-1.jpg',
        'image' => '/img/th-4.jpg',
        'isFavorite' => false,
        'deviceType' => 'phone',
        'deviceText' => 'Mob'
        ])
    </div>

    @include('components.creatives.push-details')
</div>