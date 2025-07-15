@props([
'selected' => '',
'options' => [],
'id' => '',
'icon' => 'list',
'placeholder' => '',
])

<div class="base-select base-select-icon" id="{{ $id }}">
    <div class="base-select__trigger">
        <span class="base-select__value">
            @if($placeholder)
            <span class="base-select__placeholder">{{ $placeholder }}</span>
            @endif
            <span class="base-select__selected-label">{{ $selected }}</span>
        </span>
        <span class="base-select__arrow"></span>
    </div>
    <ul class="base-select__dropdown" style="display: none;">
        @foreach($options as $option)
        <li data-value="{{ $option }}" data-label="{{ $option }}"
            class="base-select__option {{ $option == $selected ? 'is-selected' : '' }}">
            {{ $option }}
        </li>
        @endforeach
    </ul>
    <span class="icon-{{ $icon }}"></span>
</div>