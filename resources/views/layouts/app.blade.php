@extends('layouts.body')
@section('content')

<!-- Scripts -->
@vite(['resources/js/app.js', 'resources/scss/app.scss'])

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
        <div class="toast opacity-75 align-items-center border-0 toast-{{ $toast['type'] }}" role="alert"
            aria-live="assertive" aria-atomic="true" :data-bs-delay="5000">
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
    <x-subscription-activated-modal :type="$currentTariff['css_class']" :tariff="$currentTariff['name']"
        :expires="$currentTariff['expires_at']" />

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show subscription activated modal
            window.Modal.show('modal-subscription-activated');

        });
    </script>
    @endif

    <!-- Routes for ajax -->
    @if (Auth::check())
    <script>
        window.routes = {
            landingsAjaxList: '{{ route("landings.list.ajax") }}',
            landingsAjaxStore: '{{ route("landings.store.ajax") }}',
            landingsAjaxDestroyBase: '{{ route("landings.destroy.ajax", ["landing" => ":id"]) }}',
        };
    </script>
    @endif
    <!-- Routes -->
    @stack('scripts')
    @stack('modals')
    @endsection