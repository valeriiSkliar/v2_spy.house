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
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link href="{{ asset('css/libs/aos.min.css') }}" rel="stylesheet">

    <!-- Scripts -->
    @vite([
    'resources/scss/app.scss',
    // 'resources/js/pages/mainPage/aos.js',
    'resources/js/pages/mainPage/ResizeSensor.js',
    'resources/js/pages/mainPage/jquery.sticky-sidebar.min.js',
    'resources/js/pages/mainPage/jquery.star-rating-svg.js',
    'resources/js/pages/mainPage/typeit.min.js',
    'resources/js/pages/mainPage/mqscroller.min.js',
    'resources/js/pages/mainPage/main.js',
    'resources/js/pages/mainPage/home.js',
    ])

    <!-- Frontend Translations -->
    <x-frontend-translations />
</head>

<body>
    @yield('content')

    <!-- External CDN Dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.js"></script>
    <script src="{{ asset('js/libs/aos.min.js') }}"></script>

</body>

</html>