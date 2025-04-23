@props(['id' => '', 'group' => 'default', 'active' => false])

<div class="tubs-content__item {{ $active ? 'active' : '' }}" data-tub="{{ $id }}" data-group="{{ $group }}">
    {{ $slot }}
</div>