<div class="user-info__photo thumb">
    @if($user->photo)
        <img src="{{ asset('storage/avatars/' . $user->photo) }}" alt="{{ $user->name }}">
    @else
        {{ substr($user->name, 0, 2) }}
    @endif
</div>