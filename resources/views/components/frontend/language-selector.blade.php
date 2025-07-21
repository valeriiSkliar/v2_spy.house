@props(['class' => '', 'id' => ''])
<div class="lang-menu {{ $class }}" id="{{ $id }}">
    <div class="base-select">
        <div class="base-select__trigger">
            @php
            $currentLocale = app()->getLocale();
            $currentLanguage = config('languages.' . $currentLocale);
            @endphp
            <span class="base-select__value">
                <img src="/img/flags/{{ $currentLanguage['flag'] }}.svg" alt="">
                {{ strtoupper($currentLocale) }}
            </span>
            <span class="base-select__arrow"></span>
        </div>
        <ul class="base-select__dropdown" style="display: none;">
            @foreach (config('languages') as $locale => $properties)
            <li class="base-select__option{{ $locale == $currentLocale ? ' is-selected' : '' }}"
                data-lang="{{ $locale }}">
                <a href="{{ route('language.switch', $locale) }}">
                    <img src="/img/flags/{{ $properties['flag'] }}.svg" alt="">
                    {{ strtoupper($locale) }}

                </a>
            </li>
            @endforeach
        </ul>
    </div>
</div>