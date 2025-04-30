@props(['selected' => ['value' => '', 'order' => '', 'label' => ''], 'options' => [], 'id' => '', 'withFlag' => false])

<div class="base-select" id="{{ $id }}">
    <div class="base-select__trigger">
        <span class="base-select__value">
            @if($withFlag)
            <img src="/img/flags/{{ $selected['code'] ?? 'US' }}.svg" alt="">
            @endif
            {{ $selected['label'] }}
        </span>
        <span class="base-select__arrow"></span>
    </div>
    <ul class="base-select__dropdown" style="display: none;">
        @foreach($options as $option)
        <li
            data-value="{{ $option['value'] }}"
            data-order="{{ $option['order'] }}"
            class="base-select__option {{ $option['value'] == $selected['value'] && $option['order'] == $selected['order'] ? 'is-selected' : '' }}">
            @if($withFlag)
            <img src="/img/flags/{{ $option['code'] ?? 'US' }}.svg" alt="">
            @endif
            {{ $option['label'] }}
        </li>
        @endforeach
    </ul>
</div>