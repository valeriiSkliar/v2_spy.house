/**
 * Social messenger field component for profile settings
 * Handles the selection and validation of different messenger types (Telegram, Viber, WhatsApp)
 * Refactored to use unified validation and state management
 */

import { profileFormElements } from './profile-form-elements';
import { MessengerStateManager } from './messenger-state-manager.js';
import { ValidationMethods } from '../../validation/validation-constants.js';

let messengerManager = null;

const initSocialMessengerField = () => {
  if (!profileFormElements.profileMessangerSelect?.length) {
    return;
  }

  // Initialize the messenger state manager
  messengerManager = new MessengerStateManager(profileFormElements);

  // Toggle dropdown on trigger click
  profileFormElements.profileMessangerSelectTrigger.off('click').on('click', function () {
    profileFormElements.profileMessangerSelect.find('.base-select__dropdown').slideToggle(200);
  });

  // Handle option selection
  profileFormElements.profileMessangerSelectOptions.off('click').on('click', function () {
    const selectedOption = $(this);
    const selectedType = selectedOption.data('value');

    // Use the unified state manager to handle the selection
    messengerManager.updateSelectedMessenger(selectedType, this);

    // Close dropdown
    messengerManager.closeDropdown();
  });

  // Handle input validation using unified validation methods
  profileFormElements.messengerContact.off('input').on('input', function () {
    const value = $(this).val();
    const type = profileFormElements.messengerType.val();

    if (ValidationMethods.validateMessengerContact(type, value)) {
      $(this).removeClass('error').addClass('valid');
    } else {
      $(this).removeClass('valid').addClass('error');
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
        messengerManager.closeDropdown();
      }
    });
};

export { initSocialMessengerField, messengerManager };
