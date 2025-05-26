import $ from 'jquery';
import 'jquery-validation';
import { debounce } from '../../helpers/custom-debounce';
import { VALIDATION_PATTERNS, ValidationMethods } from '../../validation/validation-constants.js';

/**
 * Enhanced validation for profile settings form using jQuery Validation
 */
// ... existing code ...

/**
 * Initialize jQuery Validation for profile settings form
 */
function initProfileSettingsValidation($form) {
  // Add custom validation methods
  addCustomValidationMethods();

  // Configure jQuery Validation
  const validator = $form.validate({
    rules: {
      login: {
        required: true,
        maxlength: 255,
        pattern: VALIDATION_PATTERNS.login,
      },
      messenger_contact: {
        messengerContactValidation: true,
      },
    },
    messages: {
      login: {
        required: '',
        maxlength: '',
        pattern: '',
        remote: '',
      },
      messenger_contact: {
        messengerContactValidation: '',
      },
    },
    errorElement: 'div',
    errorClass: 'validation-error',
    validClass: 'valid',

    // No error placement - we only want visual highlighting
    errorPlacement: function (error, element) {
      // Don't display error messages under fields
      return false;
    },

    // Highlight invalid fields
    highlight: function (element, errorClass, validClass) {
      $(element).addClass('error').removeClass(validClass);
    },

    // Unhighlight valid fields
    unhighlight: function (element, errorClass, validClass) {
      $(element).removeClass('error').addClass(validClass);
    },

    // Invalid form handler
    invalidHandler: function (event, validator) {
      // Scroll to first error
      // if (validator.numberOfInvalids() > 0) {
      //   const $firstError = $(validator.errorList[0].element);
      //   $('html, body').animate(
      //     {
      //       scrollTop: $firstError.offset().top - 100,
      //     },
      //     500
      //   );
      // }
    },
  });

  // Handle messenger type changes
  const $messengerTypeSelect = $('#profile-messanger-select');
  if ($messengerTypeSelect.length) {
    $messengerTypeSelect.on('baseSelect:change', function (e) {
      const $messengerContactInput = $('input[name="messenger_contact"]');
      if ($messengerContactInput.length) {
        // Update placeholder
        updateMessengerPlaceholder($messengerContactInput, e.detail.value);

        // Revalidate if there's a value
        if ($messengerContactInput.val().trim()) {
          validator.element($messengerContactInput[0]);
        }
      }
    });
  }

  // Применение debounce к событию изменения поля логина
  const $loginInput = $('input[name="login"]');
  const debouncedValidation = debounce(function () {
    if ($loginInput.val().length > 0) {
      validator.element($loginInput);
    }
  }, 500);

  $loginInput.on('input', debouncedValidation);

  return validator;
}

/**
 * Add custom validation methods
 */
function addCustomValidationMethods() {
  // Pattern validation method
  $.validator.addMethod(
    'pattern',
    function (value, element, regexp) {
      return this.optional(element) || regexp.test(value);
    },
    'Please enter a valid format.'
  );

  // Messenger contact validation
  $.validator.addMethod(
    'messengerContactValidation',
    function (value, element) {
      if (!value || !value.trim()) {
        return true; // Optional field
      }

      const messengerType = $('input[name="messenger_type"]').val();
      if (!messengerType) {
        return true; // No messenger type selected
      }

      return ValidationMethods.validateMessengerContact(messengerType, value);
    },
    'Please enter a valid contact for the selected messenger type.'
  );
}

/**
 * Update messenger contact placeholder based on type
 */
function updateMessengerPlaceholder($input, messengerType) {
  const placeholder = ValidationMethods.getMessengerPlaceholder(messengerType);
  $input.attr('placeholder', placeholder);
}

// Export for use in other modules
export { initProfileSettingsValidation };
