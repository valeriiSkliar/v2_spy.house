@props(['tabsGroup' => 'default'])

<ul class="tubs-2">
    @foreach($tabs as $tab)
    <li>
        <a data-tub="{{ $tab['id'] }}" data-group="{{ $tabsGroup }}" class="{{ $tab['active'] ? 'active' : '' }}">
            {{ $tab['name'] }}
        </a>
    </li>
    @endforeach
</ul>
<div class="tubs-content">
    {{ $slot }}
</div>