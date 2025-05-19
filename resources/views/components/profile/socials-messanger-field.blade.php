<div class="form-item mb-20">
    <label class="d-block mb-10">{{ __('profile.personal_info.messanger_label') }}</label>
    @php
        $messenger_type = $user->messenger_type ?? 'telegram';
        $messenger_contact = $user->messenger_contact ?? '';

        $initImage = '';
        $visibleValue = $messenger_contact;
        $visibleType = $messenger_type;
        
        $placeholders = [
            'telegram' => '@username',
            'viber' => '+1 (999) 999-99-99',
            'whatsapp' => '+1 (999) 999-99-99'
        ];
        
        $currentPlaceholder = $placeholders[$messenger_type] ?? $placeholders['telegram'];
        
        switch ($messenger_type) {
            case 'telegram':
                $initImage = Vite::asset('resources/img/telegram.svg');
                break;
            case 'viber':
                $initImage = Vite::asset('resources/img/viber.svg');
                break;
            case 'whatsapp':
                $initImage = Vite::asset('resources/img/whatsapp.svg');
                break;
            default:
                $initImage = Vite::asset('resources/img/telegram.svg');
                break;
        }
    @endphp
    <div class="form-phone">
        <input
            type="text"
            name="messenger_contact"
            class="input-h-57"
            value="{{ old('messenger_contact', $visibleValue) }}"
            placeholder="{{ $currentPlaceholder }}"
        >
        <div id="profile-messanger-select" class="base-select">
            <div class="base-select__trigger">
                <span class="base-select__value"><span class="base-select__img">
                    <img
                        src="{{ $initImage }}"
                        alt="{{ ucfirst($messenger_type) }}"
                    >
                </span>
                <span class="base-select__arrow"></span>
            </div>
            <ul class="base-select__dropdown" style="display: none;">
                <li
                    class="base-select__option {{ $messenger_type == 'telegram' ? 'is-selected' : '' }}"
                    data-value="telegram">
                        <span class="base-select__img">
                            <img src="{{ Vite::asset('resources/img/telegram.svg') }}" alt="Telegram">
                        </span>
                    </li>
                <li
                    class="base-select__option {{ $messenger_type == 'viber' ? 'is-selected' : '' }}"
                    data-value="viber">
                        <span class="base-select__img">
                            <img src="{{ Vite::asset('resources/img/viber.svg') }}" alt="Viber">
                        </span>
                    </li>
                <li
                    class="base-select__option {{ $messenger_type == 'whatsapp' ? 'is-selected' : '' }}"
                    data-value="whatsapp">
                        <span class="base-select__img">
                            <img src="{{ Vite::asset('resources/img/whatsapp.svg') }}" alt="WhatsApp">
                        </span>
                    </li>
            </ul>
            <input type="hidden" name="messenger_type" value="{{ old('messenger_type', $messenger_type) }}">
        </div>
    </div>
    @error('messenger_type')
        <span class="text-danger">{{ $message }}</span>
    @enderror
    @error('messenger_contact')
        <span class="text-danger">{{ $message }}</span>
    @enderror
</div>

<style>
.input-h-57.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
</style>
