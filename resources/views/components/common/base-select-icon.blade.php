@props(['options' => [], 'selected' => '', 'icon' => ''])

<div class="base-select-icon">
    <div class="base-select">
        <div class="base-select__trigger">
            <span class="base-select__value">{{ $selected }}</span>
            <span class="base-select__arrow"></span>
        </div>
        <ul class="base-select__dropdown" style="display: none;">
            @foreach($options as $option)
            <li class="base-select__option {{ $option === $selected ? 'is-selected' : '' }}">{{ $option }}</li>
            @endforeach
        </ul>
    </div>
    <span class="icon-{{ $icon }}"></span>
</div>