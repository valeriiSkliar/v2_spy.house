import $ from 'jquery';
import 'jquery-validation';
import {
  PERSONAL_GREETING_CONFIG,
  ValidationMethods,
  VERIFICATION_CODE_CONFIG,
} from '../../validation/validation-constants.js';

/**
 * Initialize jQuery Validation for change personal greeting form
 * @param {jQuery} $form - The form element
 * @param {boolean} isConfirmationStep - Whether the form is in confirmation step
 * @returns {object} - The validator instance
 */
function initChangePersonalGreetingValidation($form, isConfirmationStep) {
  // Check if jQuery Validation is available
  if (!$.validator) {
    console.error('jQuery Validation is not available');
    return null;
  }

  // Add custom validation methods
  addCustomValidationMethods();

  // Configure validation rules based on current step
  const rules = {
    personal_greeting: {
      required: true,
      minlength: PERSONAL_GREETING_CONFIG.minLength,
      maxlength: PERSONAL_GREETING_CONFIG.maxLength,
      personalGreetingFormat: true,
    },
  };

  // Add verification code rules only for confirmation step
  if (isConfirmationStep) {
    rules.verification_code = {
      required: true,
      digits: true,
      exactLength: VERIFICATION_CODE_CONFIG.length,
    };
  }

  // Configure jQuery Validation
  const validator = $form.validate({
    rules,
    messages: {
      personal_greeting: {
        required: '',
        minlength: '',
        maxlength: '',
        personalGreetingFormat: '',
      },
      ...(isConfirmationStep && {
        verification_code: {
          required: '',
          digits: '',
          exactLength: '',
        },
      }),
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
      // Focus first error field
      if (validator.numberOfInvalids() > 0) {
        const $firstError = $(validator.errorList[0].element);
        $firstError.focus();
      }
    },
  });

  return validator;
}

/**
 * Add custom validation methods for change personal greeting form
 */
function addCustomValidationMethods() {
  // Personal greeting format validation
  $.validator.addMethod(
    'personalGreetingFormat',
    function (value, element) {
      if (!value || !value.trim()) return false;
      return ValidationMethods.validatePersonalGreeting(value);
    },
    PERSONAL_GREETING_CONFIG.errorMessage
  );

  // Exact length validation for verification code
  $.validator.addMethod(
    'exactLength',
    function (value, element, length) {
      return this.optional(element) || value.length === length;
    },
    function (params, element) {
      return `Please enter exactly ${params} digits.`;
    }
  );
}

/**
 * Handle server validation errors for change personal greeting form
 * @param {object} response - Server response with errors
 * @param {jQuery} $form - The form element
 */
function handleChangePersonalGreetingValidationErrors(response, $form) {
  if (response.errors) {
    // Clear previous errors
    $form.find('input, textarea').removeClass('error valid');

    // Add errors for each field
    Object.keys(response.errors).forEach(field => {
      const $input = $form.find(`input[name="${field}"], textarea[name="${field}"]`);
      if ($input.length) {
        $input.addClass('error');
      }
    });
  }

  // Handle field statuses if available
  if (response.field_statuses) {
    Object.entries(response.field_statuses).forEach(([field, status]) => {
      const $field = $form.find(`[name="${field}"]`);
      if ($field.length) {
        // Clear previous status classes
        $field.removeClass('error valid');

        // Apply new status class
        if (status.status === 'error') {
          $field.addClass('error');
        } else if (status.status === 'success') {
          $field.addClass('valid');
        }
      }
    });
  }
}

// Export for use in other modules
export { handleChangePersonalGreetingValidationErrors, initChangePersonalGreetingValidation };
