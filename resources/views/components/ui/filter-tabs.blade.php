@props([
'activeTab' => '',
'counts' => []
])
<div class="filter-push" x-data="creativeTabs">
    <a href="{{ route('creatives.index', ['tab' => 'push']) }}" class="filter-push__item"
        :class="{ 'active': isActiveTab('push') }" @click.prevent="setActiveTab('push')"
        x-text="`{{ __('creatives.filter.tabs.push') }} ${getTabCount('push')}`">
    </a>
    <a href="{{ route('creatives.index', ['tab' => 'inpage']) }}" class="filter-push__item"
        :class="{ 'active': isActiveTab('inpage') }" @click.prevent="setActiveTab('inpage')"
        x-text="`{{ __('creatives.filter.tabs.inpage') }} ${getTabCount('inpage')}`">
    </a>
    <a href="{{ route('creatives.index', ['tab' => 'facebook']) }}" class="filter-push__item"
        :class="{ 'active': isActiveTab('facebook') }" @click.prevent="setActiveTab('facebook')"
        x-text="`{{ __('creatives.filter.tabs.facebook') }} ${getTabCount('facebook')}`">
    </a>
    <a href="{{ route('creatives.index', ['tab' => 'tiktok']) }}" class="filter-push__item"
        :class="{ 'active': isActiveTab('tiktok') }" @click.prevent="setActiveTab('tiktok')"
        x-text="`{{ __('creatives.filter.tabs.tiktok') }} ${getTabCount('tiktok')}`">
    </a>
</div>