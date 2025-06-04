<div class="creatives-list">
    <div class="creatives-list__items">
        @include('components.creatives.creative-item-inpage', [
        'icon' => '/img/th-2.jpg',
        'activeText' => trans_choice('creatives.card.active', $activeDays ?? 3, ['count' => $activeDays ?? 3]),
        ])

        @include('components.creatives.creative-item-inpage', [
        'icon' => '/img/th-1.jpg',
        'activeText' => trans_choice('creatives.card.was-active', $activeDays ?? 12, ['count' => $activeDays ?? 12]),
        ])
    </div>

    @include('components.creatives.inpage-details')
</div>