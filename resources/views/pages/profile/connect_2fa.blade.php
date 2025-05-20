@extends('layouts.main')

@section('page-content')
    <x-profile.two-factor.activation-info />

        @if ($user->google_2fa_enabled)
        <x-profile.two-factor.status-enabled :user="$user" />
    @else
        <div class="section mb-20">
            <x-profile.two-factor.status-messages />
            <div class="step-2fa">
                <x-profile.two-factor.step-header number="1" title="Google Authenticator" />
                <div class="step-2fa__content">
                    <p class="mb-30">Просканируйте QR-код в приложении Google Authenticator или скопируйте токен аккаунта туда вручную</p>
                    <div class="row _offset20 pt-2 align-items-center">
                        <div class="col-12 col-md-6 col-lg-4">
                            <x-profile.two-factor.qr-code :qrCodeInline="$qrCodeInline" />
                        </div>
                        <div class="col-12 col-md-6 col-lg-4">
                            <x-profile.two-factor.account-token :google_2fa_secret="$google_2fa_secret" />
                        </div>
                    </div>
                    <div class="row _offset20 pt-2">
                        <div class="col-12 col-md-6 col-lg-4">
                            <x-profile.two-factor.confirmation-method />
                        </div>
                    </div>
                    <x-profile.two-factor.action-button text="Next" />
                </div>
            </div>
        </div>

        @endif
    @endsection
