@php
use Illuminate\Support\Facades\Auth;
@endphp
<!DOCTYPE html>
<html class="{{ Request::is('/') ? 'main-page' : '' }}" lang="{{ str_replace('_', '-', app()->getLocale()) }}">

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
    <link rel="stylesheet" href="{{ asset('css/main.css') }}">
    <link rel="stylesheet" href="{{ asset('css/slick.css') }}">
    <link rel="stylesheet" href="{{ asset('css-from-dev/MultiSelect-DLgF6PJh.css') }}">
    <link rel="stylesheet" href="{{ asset('css-from-dev/CreativesSideBarFilters-DWIhj8Ku.css') }}">
    <link rel="stylesheet" href="{{ asset('css-from-dev/BaseSelect-BMEJFstU.css') }}">
    <link rel="stylesheet" href="{{ asset('css-from-dev/FormatSelect-BzMMUbfP.css') }}">
    <link rel="stylesheet" href="{{ asset('css-from-dev/Pagination-kqHfmUMg.css') }}">
    <link rel="stylesheet" href="{{ asset('css-from-dev/DatePicker-D52j4JiE.css') }}">
    <link rel="stylesheet" href="{{ asset('css-new/aos-plugin.css') }}">
    <link rel="stylesheet" href="{{ asset('css-new/style.css') }}">
    <style>
        html,
        boby {
            scroll-behavior: smooth;
        }
    </style>
</head>

<body {{ Request::is('/') ? 'data-aos-easing="ease" data-aos-duration="1000" data-aos-delay="0"' : '' }}>
    @yield('content')

    @vite(['resources/js/components/contact-form.js'])

    {{-- scripts --}}
    @include('partials.modals')
    <!-- Frontend Translations -->
    <x-frontend-translations />
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
    <script src="{{ asset('js/ResizeSensor.js') }}"></script>
    <script src="{{ asset('js/jquery.sticky-sidebar.min.js') }}"></script>
    <script src="{{ asset('js/jquery.star-rating-svg.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.js"></script>
    <script src="{{ asset('js/typeit.min.js') }}"></script>
    <script src="{{ asset('js/mqscroller.min.js') }}"></script>
    <script src="{{ asset('js/aos.js') }}"></script>
    @if(Request::is('/'))
    <script src="{{ asset('js/main.js') }}"></script>
    <script src="{{ asset('js/home.js') }}"></script>
    @else
    @vite(['resources/js/app.js', 'resources/scss/app.scss'])
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    @stack('scripts')
</body>

</html>