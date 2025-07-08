@php
use Illuminate\Support\Facades\Auth;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @if(Auth::check() && isset($api_token))
    <meta name="api-token" content="{{ $api_token }}">
    @if(isset($api_token_expires_at))
    <meta name="api-token-expires-at" content="{{ $api_token_expires_at }}">
    @endif
    @endif

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    @vite(['resources/js/app.js', 'resources/scss/app.scss'])

    <!-- Frontend Translations -->
    <x-frontend-translations />
</head>
<body>
    @yield('content')
</body>
</html>