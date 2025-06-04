<div class="creatives-list">
    <div class="creatives-list__items">
        @include('components.creatives.creative-item-push', [
        'isActive' => true,
        'activeText' => 'Active: 3 day',
        'icon' => '/img/th-2.jpg',
        'image' => '/img/th-3.jpg',
        'isFavorite' => true,
        'deviceType' => 'pc',
        'deviceText' => 'PC'
        ])

        @include('components.creatives.creative-item-push', [
        'isActive' => false,
        'activeText' => 'Was active: 12 day',
        'icon' => '/img/th-1.jpg',
        'image' => '/img/th-4.jpg',
        'isFavorite' => false,
        'deviceType' => 'phone',
        'deviceText' => 'Mob'
        ])
    </div>

    @include('components.creatives.push-details')
</div>