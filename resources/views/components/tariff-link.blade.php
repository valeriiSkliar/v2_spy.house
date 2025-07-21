@props(['type' => ''])

@php
$type = App\Helpers\tariff_name_mapping($type);
@endphp

<a href="#" {{ $attributes->merge(['class' => 'tariff-link ' . ($type ? '_'.$type : '')]) }}>
    {{ $slot }}
</a>