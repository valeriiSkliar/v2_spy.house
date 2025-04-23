@props(['type' => ''])

<a href="#" {{ $attributes->merge(['class' => 'tariff-link ' . ($type ? '_'.$type : '')]) }}>
    {{ $slot }}
</a>