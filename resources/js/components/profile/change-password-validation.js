import $ from 'jquery';
import 'jquery-validation';
import {
  PASSWORD_CONFIG,
  VERIFICATION_CODE_CONFIG,
  ValidationMethods,
} from '../../validation/validation-constants.js';

/**
 * Initialize jQuery Validation for change password form
 * @param {jQuery} $form - The form element
 * @param {boolean} isConfirmationStep - Whether the form is in confirmation step
 * @returns {object} - The validator instance
 */
function initChangePasswordValidation($form, isConfirmationStep) {
  // Check if jQuery Validation is available
  if (!$.validator) {
    console.error('jQuery Validation is not available');
    return null;
  }

  // Add custom validation methods
  addCustomValidationMethods();

  // Configure validation rules based on current step
  const rules = {
    current_password: {
      required: true,
    },
    password: {
      required: true,
      minlength: PASSWORD_CONFIG.minLength,
    },
    password_confirmation: {
      required: true,
      equalTo: 'input[name="password"]',
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
      current_password: {
        required: '',
      },
      password: {
        required: '',
        minlength: '',
      },
      password_confirmation: {
        required: '',
        equalTo: '',
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
 * Add custom validation methods for change password form
 */
function addCustomValidationMethods() {
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
 * Handle server validation errors for change password form
 * @param {object} response - Server response with errors
 * @param {jQuery} $form - The form element
 */
function handleChangePasswordValidationErrors(response, $form) {
  if (response.errors) {
    // Clear previous errors
    $form.find('input').removeClass('error valid');

    // Add errors for each field
    Object.keys(response.errors).forEach(field => {
      const $input = $form.find(`input[name="${field}"]`);
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
export { handleChangePasswordValidationErrors, initChangePasswordValidation };