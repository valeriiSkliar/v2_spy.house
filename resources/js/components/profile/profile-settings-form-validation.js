import $ from 'jquery';
import 'jquery-validation';
import { debounce } from '../../helpers/custom-debounce';

/**
 * Enhanced validation for profile settings form using jQuery Validation
 */
$(document).ready(function () {
  const $personalSettingsForm = $('#personal-settings-form');

  if ($personalSettingsForm.length) {
    initProfileSettingsValidation($personalSettingsForm);
  }
});

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
        pattern: /^[a-zA-Z0-9_]+$/,
        remote: {
          url: '/api/validate-login-unique',
          type: 'POST',
          data: {
            login: function () {
              return $('input[name="login"]').val();
            },
            _token: function () {
              return $('meta[name="csrf-token"]').attr('content');
            },
          },
          dataFilter: function (data) {
            const json = JSON.parse(data);
            return json.valid ? 'true' : 'false';
          },
          beforeSend: debounce(function () {
            return true;
          }, 500),
        },
      },
      messenger_contact: {
        messengerContactValidation: true,
      },
    },
    messages: {
      login: {
        required: 'Login is required',
        maxlength: 'Login must not exceed 255 characters',
        pattern: 'Login can only contain letters, numbers and underscores',
        remote: 'This login is already taken',
      },
      messenger_contact: {
        messengerContactValidation: 'Please enter a valid contact for the selected messenger type',
      },
    },
    errorElement: 'div',
    errorClass: 'validation-error',
    validClass: 'valid',

    // Custom error placement
    errorPlacement: function (error, element) {
      error.addClass('validation-error');
      element.closest('.form-group, .input-group').append(error);
    },

    // Highlight invalid fields
    highlight: function (element, errorClass, validClass) {
      $(element).addClass('error').removeClass(validClass);
    },

    // Unhighlight valid fields
    unhighlight: function (element, errorClass, validClass) {
      $(element).removeClass('error').addClass(validClass);
    },

    // Handle form submission
    submitHandler: function (form) {
      const $submitButton = $form.find('button[type="submit"]');

      // Disable button to prevent double submission
      $submitButton.prop('disabled', true);

      // Re-enable after timeout in case of network issues
      setTimeout(() => {
        $submitButton.prop('disabled', false);
      }, 10000);

      // Allow form to submit
      return true;
    },

    // Invalid form handler
    invalidHandler: function (event, validator) {
      // Scroll to first error
      if (validator.numberOfInvalids() > 0) {
        const $firstError = $(validator.errorList[0].element);
        $('html, body').animate(
          {
            scrollTop: $firstError.offset().top - 100,
          },
          500
        );
      }
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

      switch (messengerType) {
        case 'telegram':
          return validationTelegramLogin(value);
        case 'viber':
          return validationViberIdentifier(value);
        case 'whatsapp':
          return validationWhatsappIdentifier(value);
        default:
          return true;
      }
    },
    'Please enter a valid contact for the selected messenger type.'
  );
}

/**
 * Update messenger contact placeholder based on type
 */
function updateMessengerPlaceholder($input, messengerType) {
  const placeholders = {
    telegram: '@username',
    viber: '+1 (999) 999-99-99',
    whatsapp: '+1 (999) 999-99-99',
  };

  $input.attr('placeholder', placeholders[messengerType] || placeholders['telegram']);
}

/**
 * Проверка формата логина Telegram
 * @param {string} value - Значение для проверки
 * @returns {boolean} - Валиден ли формат
 */
function validationTelegramLogin(value) {
  return /^@[a-zA-Z0-9_]{4,31}$/.test(value);
}

/**
 * Проверка формата идентификатора Viber
 * @param {string} value - Значение для проверки
 * @returns {boolean} - Валиден ли формат
 */
function validationViberIdentifier(value) {
  // Better phone number validation - matches digits only for now
  // In a production app, consider using libphonenumber-js for proper validation
  return /^\d{10,15}$/.test(value);
}

/**
 * Проверка формата идентификатора WhatsApp
 * @param {string} value - Значение для проверки
 * @returns {boolean} - Валиден ли формат
 */
function validationWhatsappIdentifier(value) {
  // Better phone number validation - matches digits only for now
  // In a production app, consider using libphonenumber-js for proper validation
  return /^\d{10,15}$/.test(value);
}

// Export for use in other modules
export {
  initProfileSettingsValidation,
  validationTelegramLogin,
  validationViberIdentifier,
  validationWhatsappIdentifier,
};
