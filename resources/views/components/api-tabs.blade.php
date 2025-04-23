@props(['tabsGroup' => 'default', 'tabs' => []])

<div class="api-tabs">
    @foreach($tabs as $tab)
    <a
        data-tub="{{ $tab['id'] }}"
        data-group="{{ $tabsGroup }}"
        class="api-tab {{ $tab['active'] ? 'active' : '' }}">
        {{ $tab['name'] }}
    </a>
    @endforeach
</div>
<div class="tubs-content">
    {{ $slot }}
</div>