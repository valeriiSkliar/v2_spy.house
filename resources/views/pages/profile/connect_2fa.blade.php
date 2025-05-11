@extends('layouts.main')

@section('page-content')
    <div class="container">
        <h2>{{ __('profile.2fa.setup_title') }}</h2>

        @if (session('status') == '2fa-enabled')
            <div class="alert alert-success">
                {{ __('profile.2fa.status_enabled') }}
            </div>
        @endif
        @if (session('status') == '2fa-disabled')
            <div class="alert alert-success">
                {{ __('profile.2fa.status_disabled') }}
            </div>
        @endif

        @if ($user->google_2fa_enabled)
            <p>{{ __('profile.2fa.current_status_enabled') }}</p>
            <form method="POST" action="{{ route('profile.disable-2fa') }}">
                @csrf
                <button type="submit" class="btn btn-danger">{{ __('profile.2fa.disable_button') }}</button>
            </form>
        @else
            <p>{{ __('profile.2fa.setup_instructions_1') }}</p>
            
            <div>
                <img class="img-fluid img-thumbnail w-25" src="{{ $qrCodeInline }}" alt="{{ __('profile.2fa.qr_code_alt') }}">
            </div>

            <p class="mt-3">{{ __('profile.2fa.setup_instructions_2') }} <code>{{ $google_2fa_secret }}</code></p>

            <form method="POST" action="{{ route('profile.store-2fa') }}" class="mt-3">
                @csrf
                <div class="form-group">
                    <label for="one_time_password">{{ __('profile.2fa.otp_label') }}</label>
                    <input id="one_time_password" type="text" class="form-control @error('one_time_password') is-invalid @enderror" name="one_time_password" required autofocus>
                    @error('one_time_password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary mt-2">{{ __('profile.2fa.enable_button') }}</button>
            </form>
        @endif
    </div>
@endsection 