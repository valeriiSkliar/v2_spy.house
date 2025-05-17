@php
use Illuminate\Support\Facades\Auth;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @if(Auth::check() && isset($api_token))
    <meta name="api-token" content="{{ $api_token }}">
    @if(isset($api_token_expires_at))
    <meta name="api-token-expires-at" content="{{ $api_token_expires_at }}">
    @endif
    @endif

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Custom Styles -->
    <link href="{{ asset('css/profile-avatar-upload.css') }}" rel="stylesheet">

    <!-- Scripts -->
    @vite([ 'resources/js/app.js', 'resources/scss/app.scss'])
</head>

<body class="">
    <div class="navigation-bg"></div>
    

    <!-- Page Content -->
    @yield('content')
    @include('partials.footer')

    <!-- Global Modal Container -->
    <div id="global-modal-container"></div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1050;">
        @if (session('toasts'))
        @foreach (session('toasts') as $toast)
        <div 
            class="toast opacity-75 align-items-center border-0 toast-{{ $toast['type'] }}" 
            role="alert" 
            aria-live="assertive"
            aria-atomic="true"
            :data-bs-delay="5000"
        >
            <div class="d-flex align-items-center p-3">
                <div class="toast-icon me-3">
                </div>
                <div class="toast-body">
                    {{ __($toast['message']) }}
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
        @endforeach
        @endif
    </div>

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
            window.Modal.show('modal-subscription-activated');

        });
    </script>
    @endif

    <script>
        window.routes = {
            landingsAjaxList: '{{ route("landings.list.ajax") }}',
            landingsAjaxStore: '{{ route("landings.store.ajax") }}',
            landingsAjaxDestroyBase: '{{ route("landings.destroy.ajax", ["landing" => ":id"]) }}',
        };
    </script>
    @vite(['resources/js/app.js'])
    @stack('scripts')
</body>

</html>