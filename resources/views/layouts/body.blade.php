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

    <!-- Local CSS Libraries -->
    <link href="{{ asset('css/libs/aos.min.css') }}" rel="stylesheet">

    <!-- External CSS Libraries (CDN) -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.18/css/bootstrap-select.min.css"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/swiper@8.4.7/swiper-bundle.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick-theme.min.css" rel="stylesheet">
    <style>
        .title-label {
            padding: 8px 10px;
        }
    </style>
    <!-- Scripts -->
    @vite([
    'resources/scss/app.scss',
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

    <!-- Local Main Page Libraries (порядок важен: библиотеки перед их использованием) -->
    <script src="{{ asset('js/libs/aos.min.js') }}"></script>
    <script src="{{ asset('js/libs/ui/ResizeSensor.min.js') }}"></script>
    <script src="{{ asset('js/libs/jquery-plugins/jquery.sticky-sidebar.min.js') }}"></script>
    <script src="{{ asset('js/libs/jquery-plugins/jquery.star-rating-svg.min.js') }}"></script>
    <script src="{{ asset('js/libs/ui/typeit.min.js') }}"></script>
    <script src="{{ asset('js/libs/ui/mqscroller.min.js') }}"></script>
    <script src="{{ asset('js/libs/bootstrap/bootstrap-datepicker.min.js') }}"></script>
    <script src="{{ asset('js/libs/bootstrap/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('js/libs/jquery-plugins/jquery.counterup.min.js') }}"></script>
    <script src="{{ asset('js/libs/jquery-plugins/jquery.marquee.min.js') }}"></script>
    <script src="{{ asset('js/libs/ui/select2.min.js') }}"></script>
    <script src="{{ asset('js/libs/ui/swiper-bundle.min.js') }}"></script>

</body>

</html>