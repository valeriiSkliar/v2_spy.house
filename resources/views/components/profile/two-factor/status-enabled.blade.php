@props(['user'])

<p>{{ __('profile.2fa.current_status_enabled') }}</p>
<a href="{{ route('profile.disable-2fa') }}" class="btn _flex _border-green _big min-200 w-mob-100">
    {{ __('profile.2fa.disable_button') }}
</a>