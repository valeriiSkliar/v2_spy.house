<div class="form-item mb-20">
    <label class="d-block mb-10">{{ __('profile.personal_info.phone_label') }}</label>
    <div class="form-phone">
        <input type="text" name="phone" class="input-h-57" value="{{ old('phone', $user->phone ?? '') }}">
        <div class="base-select">
            <div class="base-select__trigger">
                <span class="base-select__value"><span class="base-select__img"><img src="/img/flags/UA.svg" alt="">UA</span></span>
                <span class="base-select__arrow"></span>
            </div>
            <ul class="base-select__dropdown" style="display: none;">
                <li class="base-select__option is-selected"><span class="base-select__img"><img src="/img/flags/UA.svg" alt="">UA</span></li>
                <li class="base-select__option"><span class="base-select__img"><img src="/img/flags/KZ.svg" alt="">KZ</span></li>
                <li class="base-select__option"><span class="base-select__img"><img src="/img/flags/ES.svg" alt="">ES</span></li>
            </ul>
        </div>
    </div>
    @error('phone')
        <span class="text-danger">{{ $message }}</span>
    @enderror
</div>