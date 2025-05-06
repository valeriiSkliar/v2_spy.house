<div class="user-info">
    <x-profile.user-photo :user="$user" />
    <div class="user-info__wrap">
        <div class="user-info__name">{{ $user->name }}</div>
        <div class="user-info__load-photo">
            <input id="photo" name="photo" type="file" class="d-none">
            <label for="photo" class="btn _flex _gray2 _medium">
                <span class="icon-blog mr-2 font-16"></span>{{ __('profile.personal_info.change_photo_button') }}
            </label>
            <span>{{ __('profile.personal_info.photo_hint') }}</span>
        </div>
    </div>
</div>