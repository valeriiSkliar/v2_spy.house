<div class="user-info__photo thumb">
    @if($user->user_avatar)
        <img src="{{ asset('storage/' . $user->user_avatar) }}" alt="{{ $user->name }}">
    @else
        {{ substr($user->name, 0, 2) }}
    @endif
</div>