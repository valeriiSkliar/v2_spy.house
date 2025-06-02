@props(['class' => ''])

<div class="lang-menu {{ $class }}">
    <div class="base-select">
        <div class="base-select__trigger">
            <span class="base-select__value">
                <img src="{{ asset('img/flags/' . app()->getLocale() . '.svg') }}" alt="">
                {{-- {{ strtoupper(app()->getLocale()) }} --}}
            </span>
            <span class="base-select__arrow"></span>
        </div>
        <ul class="base-select__dropdown" style="display: none;">
            @foreach(config('app.available_locales', ['en', 'ru', 'es']) as $locale)
            <li class="base-select__option {{ app()->getLocale() === $locale ? 'is-selected' : '' }}">
                <a href="{{ route('language.switch', $locale) }}" class="d-flex align-items-center">
                    <img src="{{ asset('img/flags/' . $locale . '.svg') }}" alt="">
                    {{ strtoupper($locale) }}
                </a>
            </li>
            @endforeach
        </ul>
    </div>
</div>