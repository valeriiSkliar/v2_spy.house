@props(['class' => ''])

<img src="{{ asset('img/logo.svg') }}" alt="{{ config('app.name') }}" {{ $attributes->merge(['class' => $class]) }}>
