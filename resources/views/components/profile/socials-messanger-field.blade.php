<div class="form-item mb-20">
    <label class="d-block mb-10">{{ __('profile.personal_info.messanger_label') }}</label>
    <div class="form-phone">
        <input type="text" name="phone" class="input-h-57" value="{{ old('phone', $user->phone ?? '') }}">
        <div class="base-select">
            <div class="base-select__trigger">
                <span class="base-select__value"><span class="base-select__img"><img src="{{ Vite::asset('resources/img/telegram.svg') }}" alt="Telegram"></span></span>
                <span class="base-select__arrow"></span>
            </div>
            <ul class="base-select__dropdown" style="display: none;">
                <li class="base-select__option is-selected" data-value="telegram"><span class="base-select__img"><img src="{{ Vite::asset('resources/img/telegram.svg') }}" alt="Telegram"></span></li>
                <li class="base-select__option" data-value="viber"><span class="base-select__img"><img src="{{ Vite::asset('resources/img/viber.svg') }}" alt="Viber"></span></li>
                <li class="base-select__option" data-value="whatsapp"><span class="base-select__img"><img src="{{ Vite::asset('resources/img/whatsapp.svg') }}" alt="WhatsApp"></span></li>
            </ul>
            <input type="hidden" name="messanger" value="{{ old('messanger', $user->messanger ?? 'telegram') }}">
        </div>
    </div>
    @error('phone')
        <span class="text-danger">{{ $message }}</span>
    @enderror
    @error('messanger')
        <span class="text-danger">{{ $message }}</span>
    @enderror
</div>