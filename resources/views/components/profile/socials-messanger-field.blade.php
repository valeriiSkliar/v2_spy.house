<div class="form-item mb-20">
    <label class="d-block mb-10">{{ __('profile.personal_info.messanger_label') }}</label>
    @php
        $telegram = $user->telegram ?? '';
        $viber_phone = $user->viber_phone ?? '';
        $whatsapp_phone = $user->whatsapp_phone ?? '';

        $initImage = '';
        if ($telegram) {
            $initImage = Vite::asset('resources/img/telegram.svg');
        } elseif ($viber_phone) {
            $initImage = Vite::asset('resources/img/viber.svg');
        } elseif ($whatsapp_phone) {
            $initImage = Vite::asset('resources/img/whatsapp.svg');
        } else {
            $initImage = Vite::asset('resources/img/telegram.svg');
        }

        $visibleValue = '';
        $visibleType = 'telegram';
        $placeholders = [
            'telegram' => '@username',
            'viber_phone' => '+1 (999) 999-99-99',
            'whatsapp_phone' => '+1 (999) 999-99-99'
        ];
        $currentPlaceholder = $placeholders['telegram'];

        if ($telegram) {
            $visibleValue = $telegram;
            $visibleType = 'telegram';
            $currentPlaceholder = $placeholders['telegram'];
        } elseif ($viber_phone) {
            $visibleValue = $viber_phone;
            $visibleType = 'viber_phone';
            $currentPlaceholder = $placeholders['viber_phone'];
        } elseif ($whatsapp_phone) {
            $visibleValue = $whatsapp_phone;
            $visibleType = 'whatsapp_phone';
            $currentPlaceholder = $placeholders['whatsapp_phone'];
        }
    @endphp
    <div class="form-phone">
        <input
            type="text"
            name="visible_value"
            class="input-h-57"
            value="{{ old('visible_value', $visibleValue) }}"
            data-type="{{ $visibleType }}"
            placeholder="{{ $currentPlaceholder }}"
        >
        <div id="profile-messanger-select" class="base-select">
            <div class="base-select__trigger">
                <span class="base-select__value"><span class="base-select__img">
                    <img
                        src="{{ $initImage }}"
                        alt="Telegram"
                    >
                </span>
                <span class="base-select__arrow"></span>
            </div>
            <ul class="base-select__dropdown" style="display: none;">
                <li
                    class="base-select__option is-selected"
                    data-phone="{{ $telegram }}"
                    data-value="telegram">
                        <span class="base-select__img">
                            <img src="{{ Vite::asset('resources/img/telegram.svg') }}" alt="Telegram">
                        </span>
                    </li>
                <li
                    class="base-select__option"
                    data-phone="{{ $viber_phone }}"
                    data-value="viber_phone">
                        <span class="base-select__img">
                            <img src="{{ Vite::asset('resources/img/viber.svg') }}" alt="Viber">
                        </span>
                    </li>
                <li
                    class="base-select__option"
                    data-phone="{{ $whatsapp_phone }}"
                    data-value="whatsapp_phone">
                        <span class="base-select__img">
                            <img src="{{ Vite::asset('resources/img/whatsapp.svg') }}" alt="WhatsApp">
                        </span>
                    </li>
            </ul>
            <input type="hidden" name="telegram" value="{{ old('telegram',  $user->telegram ?? '') }}">
            <input type="hidden" name="viber_phone" value="{{ old('viber_phone',  $user->viber_phone ?? '') }}">
            <input type="hidden" name="whatsapp_phone" value="{{ old('whatsapp_phone',  $user->whatsapp_phone ?? '') }}">
        </div>
    </div>
    @error('phone')
        <span class="text-danger">{{ $message }}</span>
    @enderror
    @error('messanger')
        <span class="text-danger">{{ $message }}</span>
    @enderror
</div>

<style>
.input-h-57.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
</style>
