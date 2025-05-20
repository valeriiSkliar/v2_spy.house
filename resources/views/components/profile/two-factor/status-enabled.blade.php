@props(['user'])

<p>{{ __('profile.2fa.current_status_enabled') }}</p>
<form method="POST" action="{{ route('profile.disable-2fa') }}">
    @csrf
    <button type="submit" class="btn _flex _border-green _big min-200 w-mob-100">
        {{ __('profile.2fa.disable_button') }}
    </button>
</form>
