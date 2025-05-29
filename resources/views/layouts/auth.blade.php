@extends('layouts.guest')

@section('content')
<div class="wrapper login-page">
    <!-- Toast container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>

    <x-auth.header />

    <div class="login-page__content">
        <div class="login-page__right">
            <div class="login-form">
                <div class="login-form__content">
                    @yield('form-content')
                </div>
            </div>
        </div>

        <x-auth.sidebar />
    </div>
</div>
@endsection

@push('scripts')
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
@stack('page-scripts')
@endpush