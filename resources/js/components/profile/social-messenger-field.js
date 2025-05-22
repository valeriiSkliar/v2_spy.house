/**
 * Social messenger field component for profile settings
 * Handles the selection and validation of different messenger types (Telegram, Viber, WhatsApp)
 */

import { profileFormElements } from './profile-form-elements';

const initSocialMessengerField = () => {
  // Object with placeholders for each messenger type
  const placeholders = {
    telegram: '@username',
    viber: '+1 (999) 999-99-99',
    whatsapp: '+1 (999) 999-99-99',
  };

  // Store saved values for each messenger type
  const savedValues = {
    telegram: '',
    viber: '',
    whatsapp: '',
  };

  // Initialize the saved value for the current type
  const initialType = profileFormElements.messengerType.val();
  const initialValue = profileFormElements.messengerContact.val();
  if (initialType && initialValue) {
    savedValues[initialType] = initialValue;
  }

  // Function to validate messenger values
  function validateMessengerValue(type, value) {
    if (!value) return true;

    switch (type) {
      case 'telegram':
        return /^@[A-Za-z0-9_]{5,32}$/.test(value);
      case 'viber':
      case 'whatsapp':
        const cleanValue = value.replace(/[^0-9+]/g, '');
        return /^\+?[0-9]{10,15}$/.test(cleanValue);
      default:
        return false;
    }
  }

  if (!profileFormElements.profileMessangerSelect.length) {
    return;
  }

  // Toggle dropdown on trigger click
  profileFormElements.profileMessangerSelectTrigger.off('click').on('click', function () {
    profileFormElements.profileMessangerSelect.find('.base-select__dropdown').slideToggle(200);
  });

  // Handle option selection
  profileFormElements.profileMessangerSelectOptions.off('click').on('click', function () {
    const selectedOption = $(this);
    const selectedType = selectedOption.data('value');
    const currentType = profileFormElements.messengerType.val();
    const currentValue = profileFormElements.messengerContact.val();

    // If changing type, save current value for current type
    if (currentType && currentType !== selectedType) {
      savedValues[currentType] = currentValue;
    }

    // Update selected class
    profileFormElements.profileMessangerSelectOptions.removeClass('is-selected');
    selectedOption.addClass('is-selected');

    // Update trigger image
    const imgSrc = selectedOption.find('img').attr('src');
    profileFormElements.profileMessangerSelectTrigger.html(`
            <span class="base-select__value">
                <span class="base-select__img">
                    <img src="${imgSrc}" alt="${selectedType}">
                </span>
            </span>
            <span class="base-select__arrow"></span>
        `);

    // Update messenger type hidden input
    profileFormElements.messengerType.val(selectedType);

    // Update placeholder based on selected type
    profileFormElements.messengerContact.attr(
      'placeholder',
      placeholders[selectedType] || '@username'
    );

    // Restore saved value for selected type or clear field
    profileFormElements.messengerContact.val(savedValues[selectedType] || '');

    // Trigger input event to validate the restored/cleared value
    profileFormElements.messengerContact.trigger('input');

    // Dispatch custom event for validation
    const event = new CustomEvent('baseSelect:change', {
      detail: { value: selectedType },
    });
    profileFormElements.profileMessangerSelect[0].dispatchEvent(event);

    // Close dropdown
    profileFormElements.profileMessangerSelect.find('.base-select__dropdown').slideUp(200);
  });

  // Handle input validation
  profileFormElements.messengerContact.off('input').on('input', function () {
    const value = $(this).val();
    const type = profileFormElements.messengerType.val();

    if (!validateMessengerValue(type, value)) {
      $(this).addClass('error');
    } else {
      $(this).removeClass('error');
    }
  });

  // Close dropdown when clicking outside
  $(document)
    .off('click.messengerDropdown')
    .on('click.messengerDropdown', function (e) {
      if (
        !profileFormElements.profileMessangerSelect.is(e.target) &&
        profileFormElements.profileMessangerSelect.has(e.target).length === 0
      ) {
        profileFormElements.profileMessangerSelect.find('.base-select__dropdown').slideUp(200);
      }
    });
};

export { initSocialMessengerField };
