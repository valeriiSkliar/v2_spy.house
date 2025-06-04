<div class="creatives-list">
    <div class="creatives-list__items">
        @include('components.creatives.creative-item-inpage', [
        'icon' => '/img/th-2.jpg',
        'activeText' => 'Active: 3 day'
        ])

        @include('components.creatives.creative-item-inpage', [
        'icon' => '/img/th-1.jpg',
        'activeText' => 'Active: 3 day'
        ])
    </div>

    @include('components.creatives.inpage-details')
</div>