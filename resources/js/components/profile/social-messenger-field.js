/**
 * Social messenger field component for profile settings
 * Handles the selection and validation of different messenger types (Telegram, Viber, WhatsApp)
 */

const initSocialMessengerField = () => {
    // Define constants for elements
    const $telegramInput = $('input[name="telegram"]');
    const $viberPhoneInput = $('input[name="viber_phone"]');
    const $whatsappPhoneInput = $('input[name="whatsapp_phone"]');
    const $profileMessangerSelect = $('#profile-messanger-select');
    const $profileMessangerSelectTrigger = $('#profile-messanger-select .base-select__trigger');
    const $profileMessangerSelectDropdown = $('#profile-messanger-select .base-select__dropdown');
    const $profileMessangerSelectOptions = $('#profile-messanger-select .base-select__option');
    const $visibleValueInput = $('input[name="visible_value"]');

    // Object with placeholders for each messenger type
    const placeholders = {
        'telegram': '@username',
        'viber_phone': '+1 (999) 999-99-99',
        'whatsapp_phone': '+1 (999) 999-99-99'
    };

    // Function to validate messenger values
    function validateMessengerValue(type, value) {
        if (!value) return true;
        
        switch(type) {
            case 'telegram':
                return /^@[A-Za-z0-9_]{5,32}$/.test(value);
            case 'viber_phone':
            case 'whatsapp_phone':
                const cleanValue = value.replace(/[^0-9+]/g, '');
                return /^\+?[0-9]{10,15}$/.test(cleanValue);
            default:
                return false;
        }
    }

    // Function to update hidden field values
    function updateHiddenField() {
        const value = $visibleValueInput.val();
        const type = $visibleValueInput.data('type');
        
        // Clear all hidden fields
        $telegramInput.val('');
        $viberPhoneInput.val('');
        $whatsappPhoneInput.val('');
        
        // Update the value of the corresponding hidden field
        if (type === 'telegram') {
            $telegramInput.val(value);
        } else if (type === 'viber_phone') {
            $viberPhoneInput.val(value);
        } else if (type === 'whatsapp_phone') {
            $whatsappPhoneInput.val(value);
        }
    }

    // Handler for messenger selection in dropdown
    $profileMessangerSelectOptions.off('click').on('click', function() {
        const selectedValue = $(this).data('value');
        const selectedPhone = $(this).data('phone');
        
        // Update value in visible input field
        $visibleValueInput.val(selectedPhone);
        
        // Update data-type attribute on visible input
        $visibleValueInput.data('type', selectedValue);
        $visibleValueInput.attr('data-type', selectedValue);
        
        // Update placeholder based on selected messenger
        $visibleValueInput.attr('placeholder', placeholders[selectedValue]);
        
        // Update selected class in dropdown
        $profileMessangerSelectOptions.removeClass('is-selected');
        $(this).addClass('is-selected');
        
        // Update image in trigger
        const imgSrc = $(this).find('img').attr('src');
        const $trigger = $profileMessangerSelectTrigger;
        
        // Update trigger structure
        $trigger.html(`
            <span class="base-select__value">
                <span class="base-select__img">
                    <img src="${imgSrc}" alt="${selectedValue}">
                </span>
            </span>
            <span class="base-select__arrow"></span>
        `);
        
        // Close dropdown
        $profileMessangerSelectDropdown.hide();
        
        // Update hidden field value
        updateHiddenField();
    });

    // Handler for input in visible field
    $visibleValueInput.off('input').on('input', function() {
        const type = $(this).data('type');
        const value = $(this).val();
        
        if (!validateMessengerValue(type, value)) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
        
        updateHiddenField();
    });

    // Toggle dropdown on trigger click
    $profileMessangerSelectTrigger.off('click').on('click', function() {
        $profileMessangerSelectDropdown.toggle();
    });

    // Close dropdown when clicking outside
    $(document).off('click.messengerDropdown').on('click.messengerDropdown', function(e) {
        if (!$(e.target).closest('#profile-messanger-select').length) {
            $profileMessangerSelectDropdown.hide();
        }
    });

    // Initialize on page load
    updateHiddenField();
};

export { initSocialMessengerField };