@extends('layouts.main')

@section('page-content')
<h1 class="mb-25">{{ __('profile.2fa.setup_title') }} - Шаг 1</h1>

<div class="section profile-settings">
    @if (session('status') == '2fa-enabled')
    <div class="message _bg _with-border font-weight-500">
        <span class="icon-warning font-18"></span>
        <div class="message__txt">
            {{ __('profile.2fa.status_enabled') }}
        </div>
    </div>
    @endif
    @if (session('status') == '2fa-disabled')
    <div class="message _bg _with-border font-weight-500">
        <span class="icon-warning font-18"></span>
        <div class="message__txt">
            {{ __('profile.2fa.status_disabled') }}
        </div>
    </div>
    @endif

    @if ($user->google_2fa_enabled)
    <p>{{ __('profile.2fa.current_status_enabled') }}</p>
    <form method="POST" action="{{ route('profile.disable-2fa') }}">
        @csrf
        <button type="submit" class="btn btn-danger">{{ __('profile.2fa.disable_button') }}</button>
    </form>
    @else
    <p class="mb-15 text-center">{{ __('profile.2fa.setup_instructions_1') }}</p>

    <div class="mt-3 d-flex justify-content-center">
        <img class="img-fluid img-thumbnail w-25" src="{{ $qrCodeInline }}" alt="{{ __('profile.2fa.qr_code_alt') }}">
    </div>

    <p class="mt-3 text-center">{{ __('profile.2fa.setup_instructions_2') }} <code
            class="text-center">{{ $google_2fa_secret }}</code></p>

    <div class="d-flex justify-content-center mt-4">
        <a href="{{ route('profile.connect-2fa-step2') }}"
            class="btn _flex _green _big min-200 mt-15 w-mob-100">Далее</a>
    </div>
    @endif
</div>
@endsection

@section('scripts')
<x-profile.scripts />
@endsection