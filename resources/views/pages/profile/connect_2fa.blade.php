@extends('layouts.main-app')

@section('page-content')
<script>
    window.location.href = "{{ route('profile.connect-2fa-step1') }}";
</script>
<div class="text-center">
    <p>Перенаправление на первый шаг подключения 2FA...</p>
    <p>Если перенаправление не происходит автоматически, <a href="{{ route('profile.connect-2fa-step1') }}">нажмите
            здесь</a>.</p>
</div>
@endsection