<div class="base-select">
    <div class="base-select__trigger">
        <span class="base-select__value">
            @if(isset($withFlag) && $withFlag)
            <img src="img/flags/{{ $selected['code'] }}.svg" alt="">
            @endif
            {{ $selected['label'] }}
        </span>
        <span class="base-select__arrow"></span>
    </div>
    <ul class="base-select__dropdown" style="display: none;">
        @foreach($options as $option)
        <li class="base-select__option {{ $option['value'] == $selected['value'] ? 'is-selected' : '' }}">
            @if(isset($withFlag) && $withFlag)
            <img src="img/flags/{{ $option['code'] }}.svg" alt="">
            @endif
            {{ $option['label'] }}
        </li>
        @endforeach
    </ul>
</div>