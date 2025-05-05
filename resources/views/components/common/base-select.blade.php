@props([
    'selected' => ['value' => '', 'order' => '', 'label' => ''], 
    'options' => [], 
    'id' => '', 
    'withFlag' => false, 
    'icon' => null,
    'placeholder' => '',
])

<div class="base-select {{ $icon ? 'base-select-icon' : '' }}" id="{{ $id }}">
    <div class="base-select__trigger">
        <span class="base-select__value">
            @if($withFlag)
            <img src="/img/flags/{{ $selected['code'] ?? 'US' }}.svg" alt="">
            @endif
            {{-- Combine placeholder with selected value --}}
            @if($placeholder)
                <span class="base-select__placeholder">{{ $placeholder }}</span>
            @endif
            <span class="base-select__selected-label">{{ $selected['label'] }}</span>
        </span>
        <span class="base-select__arrow"></span>
    </div>
    <ul class="base-select__dropdown" style="display: none;">
        @foreach($options as $option)
        <li
            data-value="{{ $option['value'] }}"
            data-order="{{ $option['order'] }}"
            data-label="{{ $option['label'] }}"
            data-placeholder="{{ $placeholder }}"
            class="base-select__option {{ $option['value'] == $selected['value'] && $option['order'] == $selected['order'] ? 'is-selected' : '' }}">
            @if($withFlag)
            <img src="/img/flags/{{ $option['code'] ?? 'US' }}.svg" alt="">
            @endif
            {{ $option['label'] }}
        </li>
        @endforeach
    </ul>
    @if($icon)
    <span class="icon-{{ $icon }}"></span>
    @endif
</div>