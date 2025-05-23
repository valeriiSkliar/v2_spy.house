import $ from 'jquery';
import 'jquery-validation';
import { VALIDATION_PATTERNS, ValidationMethods, EMAIL_CONFIG, VERIFICATION_CODE_CONFIG } from '../../validation/validation-constants.js';

/**
 * Initialize jQuery Validation for change email form
 * @param {jQuery} $form - The form element
 * @returns {object} - The validator instance
 */
function initChangeEmailValidation($form) {
    // Check if jQuery Validation is available
    if (!$.validator) {
        console.error('jQuery Validation is not available');
        return null;
    }

    // Add custom validation methods
    addCustomValidationMethods();

    // Configure jQuery Validation
    const validator = $form.validate({
        rules: {
            new_email: {
                required: true,
                email: true,
                maxlength: EMAIL_CONFIG.maxLength,
                notEqualTo: 'input[name="current_email"]',
            },
            password: {
                required: true,
                minlength: 6,
            },
            verification_code: {
                required: true,
                digits: true,
                exactLength: VERIFICATION_CODE_CONFIG.length,
            },
        },
        messages: {
            new_email: {
                required: '',
                email: '',
                maxlength: '',
                notEqualTo: '',
            },
            password: {
                required: '',
                minlength: '',
            },
            verification_code: {
                required: '',
                digits: '',
                exactLength: '',
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
 * Add custom validation methods for change email form
 */
function addCustomValidationMethods() {
    // Email not equal to current email validation
    $.validator.addMethod(
        'notEqualTo',
        function (value, element, param) {
            if (!value || !value.trim()) return false;
            const currentEmail = $(param).val();
            return ValidationMethods.validateEmailNotEqual(value, currentEmail);
        },
        'New email must be different from current email.'
    );

    // Exact length validation for verification code
    $.validator.addMethod(
        'exactLength',
        function (value, element, length) {
            return this.optional(element) || value.length === length;
        },
        `Please enter exactly ${VERIFICATION_CODE_CONFIG.length} digits.`
    );
}

/**
 * Handle server validation errors for change email form
 * @param {object} response - Server response with errors
 * @param {jQuery} $form - The form element
 */
function handleChangeEmailValidationErrors(response, $form) {
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
export { initChangeEmailValidation, handleChangeEmailValidationErrors };