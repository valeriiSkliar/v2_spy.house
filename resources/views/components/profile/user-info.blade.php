<div class="user-info">
    <div id="user-avatar-container" class="user-info__photo thumb">
        @if($user->user_avatar)
        <img src="{{ asset('storage/' . $user->user_avatar) }}" alt="{{ $user->name }}">
        @else
        {{ substr($user->name, 0, 2) }}
        @endif
    </div>
    <div class="user-info__wrap">
        <div class="user-info__name">{{ $user->name }}</div>
        <div class="user-info__load-photo">
            <input id="photo" name="avatar" type="file" class="d-none" accept="image/*">
            <label for="photo" id="upload-photo-button" class="btn _flex _gray2 _medium">
                <span class="icon-blog mr-2 font-16"></span>{{ __('profile.personal_info.change_photo_button') }}
            </label>
            <div class="user-info__photo-metadata">
                @if($user->user_avatar && isset($user->user_avatar_metadata))
                <span id="selected-file-name">PNG (600x600) 200kb
                    {{--
                    {{ strtoupper(pathinfo($user->user_avatar, PATHINFO_EXTENSION)) }}
                    ({{ $user->user_avatar_metadata['dimensions']['width'] ?? 0 }}x{{
                    $user->user_avatar_metadata['dimensions']['height'] ?? 0 }})
                    {{ $user->user_avatar_metadata['size'] ?? '0' }}kb --}}
                </span>
                @else
                <span id="selected-file-name">{{ __('profile.personal_info.photo_hint') }}</span>
                @endif
            </div>
        </div>
        <div id="avatar-upload-error" class="text-danger d-none"></div>
    </div>
</div>