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

{{-- TODO: move js to js file according to the best practices and project structure --}}

<script type="module">
// Определение констант для элементов
const $telegramInput = $('input[name="telegram"]');
const $viberPhoneInput = $('input[name="viber_phone"]');
const $whatsappPhoneInput = $('input[name="whatsapp_phone"]');
const $profileMessangerSelect = $('#profile-messanger-select');
const $profileMessangerSelectTrigger = $('#profile-messanger-select .base-select__trigger');
const $profileMessangerSelectDropdown = $('#profile-messanger-select .base-select__dropdown');
const $profileMessangerSelectOptions = $('#profile-messanger-select .base-select__option');
const $visibleValueInput = $('input[name="visible_value"]');

// Объект с плейсхолдерами для каждого типа мессенджера
const placeholders = {
    'telegram': '@username',
    'viber_phone': '+1 (999) 999-99-99',
    'whatsapp_phone': '+1 (999) 999-99-99'
};

// Обработка выбора мессенджера в выпадающем списке
$profileMessangerSelectOptions.on('click', function() {
    const selectedValue = $(this).data('value');
    const selectedPhone = $(this).data('phone');
    
    // Обновляем значение в видимом поле ввода
    $visibleValueInput.val(selectedPhone);
    
    // Обновляем data-type у видимого поля ввода
    $visibleValueInput.data('type', selectedValue);
    $visibleValueInput.attr('data-type', selectedValue);
    
    // Обновляем placeholder в зависимости от выбранного мессенджера
    $visibleValueInput.attr('placeholder', placeholders[selectedValue]);
    
    // Обновляем выбранный класс в выпадающем списке
    $profileMessangerSelectOptions.removeClass('is-selected');
    $(this).addClass('is-selected');
    
    // Обновляем отображение изображения в триггере
    const imgSrc = $(this).find('img').attr('src');
    const $trigger = $profileMessangerSelectTrigger;
    
    // Обновляем структуру триггера
    $trigger.html(`
        <span class="base-select__value">
            <span class="base-select__img">
                <img src="${imgSrc}" alt="${selectedValue}">
            </span>
        </span>
        <span class="base-select__arrow"></span>
    `);
    
    // Закрываем выпадающий список
    $profileMessangerSelectDropdown.hide();
    
    // Переносим значение из поля ввода в соответствующее скрытое поле
    updateHiddenField();
    
    console.log(`Изменен тип мессенджера на: ${selectedValue}`);
});

// Обработка ввода в поле
$visibleValueInput.on('input', function() {
    updateHiddenField();
});

// Функция для обновления значения в скрытом поле
function updateHiddenField() {
    const value = $visibleValueInput.val();
    const type = $visibleValueInput.data('type');
    
    // Обновляем значение соответствующего скрытого поля
    if (type === 'telegram') {
        $telegramInput.val(value);
    } else if (type === 'viber_phone') {
        $viberPhoneInput.val(value);
    } else if (type === 'whatsapp_phone') {
        $whatsappPhoneInput.val(value);
    }
    
    console.log(`Обновлено поле ${type} со значением ${value}`);
}

// Обработка открытия/закрытия выпадающего списка
$profileMessangerSelectTrigger.on('click', function() {
    $profileMessangerSelectDropdown.toggle();
});

// Закрывать выпадающий список при клике вне его
$(document).on('click', function(e) {
    if (!$(e.target).closest('#profile-messanger-select').length) {
        $profileMessangerSelectDropdown.hide();
    }
});

// Инициализация при загрузке страницы
$(document).ready(function() {
    // Установка начального значения в соответствующее скрытое поле
    updateHiddenField();
});
</script>
