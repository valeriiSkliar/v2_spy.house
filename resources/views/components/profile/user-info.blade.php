<div class="user-info">
    <x-profile.user-photo :user="$user" />
    <div class="user-info__wrap">
        <div class="user-info__name">{{ $user->name }}</div>
        <div class="user-info__load-photo">
            <input id="photo" name="user_avatar" type="file" class="d-none" accept="image/*">
            <label for="photo" class="btn _flex _gray2 _medium">
                <span class="icon-blog mr-2 font-16"></span>{{ __('profile.personal_info.change_photo_button') }}
            </label>
            <div class="user-info__photo-metadata">
                @if($user->user_avatar && isset($user->user_avatar_metadata))
                    <span id="selected-file-name">
                        {{ strtoupper(pathinfo($user->user_avatar, PATHINFO_EXTENSION)) }}
                        ({{ $user->user_avatar_metadata['dimensions']['width'] ?? 0 }}x{{ $user->user_avatar_metadata['dimensions']['height'] ?? 0 }})
                        {{ $user->user_avatar_metadata['size'] ?? '0' }}kb
                    </span>
                @else
                    <span id="selected-file-name">{{ __('profile.personal_info.photo_hint') }}</span>
                @endif
            </div>
        </div>
        @error('user_avatar')
        <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
</div>

<script>
document.getElementById('photo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const img = new Image();
        img.onload = function() {
            const fileType = file.name.split('.').pop().toUpperCase();
            const fileSizeInKB = Math.round(file.size / 1024);
            document.getElementById('selected-file-name').textContent = 
                `${fileType} (${img.width}x${img.height}) ${fileSizeInKB}kb`;
        };
        img.src = URL.createObjectURL(file);
    } else {
        document.getElementById('selected-file-name').textContent = '{{ __("profile.personal_info.photo_hint") }}';
    }
});
</script>