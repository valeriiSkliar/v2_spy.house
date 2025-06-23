<div class="creatives-list">
    <div class="creatives-list__items">
        @forelse($creatives as $creative)
        @include('components.creatives.creative-item-inpage', [
        'activeText' => $creative['status'] === 'Active'
        ? trans_choice('creatives.card.active', $creative['active_days'], ['count' => $creative['active_days']])
        : trans_choice('creatives.card.was-active', $creative['active_days'], ['count' => $creative['active_days']]),
        'title' => $creative['title'] ?? '',
        'description' => $creative['description'] ?? '',
        'icon' => $creative['icon'] ?? '/img/th-2.jpg',
        'network' => $creative['network'] ?? 'Push.house',
        'country' => $creative['country_code'] ?? 'KZ',
        'flagIcon' => '/img/flags/' . ($creative['country_code'] ?? 'KZ') . '.svg',
        'deviceType' => strtolower($creative['platform']) === 'mob' ? 'phone' : 'pc',
        'deviceText' => $creative['platform'] ?? 'PC'
        ])
        @empty
        <p>{{ __('creatives.no-data') }}</p>
        @endforelse
    </div>

    @if(isset($creatives[0]))
    @include('components.creatives.inpage-details', [
    'creative' => $creatives[0]
    ])
    @endif
</div>