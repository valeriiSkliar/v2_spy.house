@props([
'activeTab' => '',
'counts' => []
])
<div class="filter-push">
    <a href="{{ route('creatives.index', ['type' => 'push']) }}"
        class="filter-push__item {{ $activeTab == 'push' ? 'active' : '' }}">
        {{ __('creatives.filter.tabs.push') }} <span class="filter-push__count">{{ $counts['push'] ?? 0 }}</span>
    </a>
    <a href="{{ route('creatives.index', ['type' => 'inpage']) }}"
        class="filter-push__item {{ $activeTab == 'inpage' ? 'active' : '' }}">
        {{ __('creatives.filter.tabs.inpage') }} <span class="filter-push__count">{{ $counts['inpage'] ?? 0 }}</span>
    </a>
    <a href="{{ route('creatives.index', ['type' => 'facebook']) }}"
        class="filter-push__item {{ $activeTab == 'facebook' ? 'active' : '' }}">
        {{ __('creatives.filter.tabs.facebook') }} <span class="filter-push__count">{{ $counts['facebook'] ?? 0
            }}</span>
    </a>
    <a href="{{ route('creatives.index', ['type' => 'tiktok']) }}"
        class="filter-push__item {{ $activeTab == 'tiktok' ? 'active' : '' }}">
        {{ __('creatives.filter.tabs.tiktok') }} <span class="filter-push__count">{{ $counts['tiktok'] ?? 0 }}</span>
    </a>
</div>