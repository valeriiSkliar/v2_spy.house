@extends('layouts.main-app')

@section('page-content')
<x-profile.two-factor.activation-info />

@if ($user->google_2fa_enabled)
<x-profile.two-factor.status-enabled :user="$user" />
@else
<div id="two-factor-container" class="section mb-20">
    <x-profile.two-factor.status-messages />
    <div class="step-2fa">
        <x-profile.two-factor.step-header number="1" title="Google Authenticator" />
        <div class="step-2fa__content">
            <p class="mb-30">Просканируйте QR-код в приложении Google Authenticator или скопируйте токен аккаунта туда
                вручную</p>
            <div class="row _offset20 pt-2 align-items-center">
                <div class="col-12 col-md-6 col-lg-4">
                    <x-profile.two-factor.qr-code :qrCodeInline="$qrCodeInline" />
                </div>
                <div class="col-12 col-md-6 col-lg-4">
                    <x-profile.two-factor.account-token :google_2fa_secret="$google_2fa_secret" />
                </div>
            </div>
            <div class="d-flex justify-content-start mt-4">
                <a href="{{ route('profile.connect-2fa-step2') }}"
                    class="btn _flex _green _big min-200 mt-15 w-mob-100">Далее</a>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<x-profile.scripts />
@endsection