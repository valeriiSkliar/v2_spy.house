<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    @vite([ 'resources/js/app.js', 'resources/scss/app.scss'])
</head>

<body class="font-sans antialiased">
    <!-- Page Content -->
    <main>
        @yield('content')
    </main>
    @include('partials.footer')

    @if(session('success') && session('success') === 'Subscription activated successfully')
    @php
    $currentTariff = auth()->user()->currentTariff();
    @endphp
    <x-subscription-activated-modal
        :type="$currentTariff['css_class']"
        :tariff="$currentTariff['name']"
        :expires="$currentTariff['expires_at']" />

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show subscription activated modal
            $('#modal-subscription-activated').modal('show');
        });
    </script>
    @endif

    @include('partials.modals')
</body>

</html>