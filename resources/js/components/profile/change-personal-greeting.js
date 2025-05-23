import { logger, loggerError } from '@/helpers/logger';
import { checkNotifications } from '@/helpers/notification-checker';
import { createAndShowToast } from '@/utils/uiHelpers';
import $ from 'jquery';
import 'jquery-validation';
import { config } from '../../config';
import { ajaxFetcher } from '../fetcher/ajax-fetcher';
import { hideInElement, showInElement } from '../loader';

/**
 * Cancel personal greeting update process
 */
const cancelPersonalGreetingUpdate = async cancelButton => {
  const form = $('#personal-greeting-form');
  let loader = null;

  // Disable the cancel button
  if (cancelButton) {
    $(cancelButton).prop('disabled', true);
    // Remove click handler immediately
    $(cancelButton).off('click');
  }

  logger('[DEBUG] Personal Greeting - Cancel update requested');

  try {
    loader = showInElement(form[0]);

    const response = await ajaxFetcher.get(
      config.apiProfilePersonalGreetingCancelEndpoint,
      null,
      {}
    );

    logger('[DEBUG] Personal Greeting - Cancel response:', response);

    if (response.success) {
      // Use the server-provided HTML form
      if (response.initialFormHtml) {
        form.replaceWith(response.initialFormHtml);
        // Reinitialize form handlers
        changePersonalGreeting();
      } else {
        // Fallback to reloading the page if we don't get the form HTML
        logger('[WARNING] Personal Greeting - No initial form HTML received, reloading page');
        window.location.reload();
      }
    } else {
      createAndShowToast(response.message || 'Error cancelling personal greeting update', 'error');
      // Re-enable the button on error
      if (cancelButton) {
        $(cancelButton).prop('disabled', false);
      }
    }
  } catch (error) {
    loggerError('[ERROR] Personal Greeting - Error cancelling update:', error);

    // Handle server error responses
    let errorMessage = 'Error cancelling personal greeting update. Please try again.';

    if (error.responseJSON && error.responseJSON.message) {
      errorMessage = error.responseJSON.message;
    }

    createAndShowToast(errorMessage, 'error');
    // Re-enable the button on error
    if (cancelButton) {
      $(cancelButton).prop('disabled', false);
    }
  } finally {
    if (loader) {
      hideInElement(loader);
    }
    checkNotifications();
  }
};

/**
 * Confirm personal greeting update with verification code
 */
const confirmPersonalGreetingUpdate = async formData => {
  let loader = null;
  const submitButton = $('#personal-greeting-form button[type="submit"]');
  const personalGreetingForm = $('#personal-greeting-form');

  logger('[DEBUG] Personal Greeting - Confirm update started');

  // Check if form exists
  if (!personalGreetingForm.length) {
    loggerError('[ERROR] Personal Greeting - Form not found');
    createAndShowToast('Form not found. Please refresh the page.', 'error');
    return;
  }

  // Check if request is already in progress
  if (submitButton.prop('disabled')) {
    logger('[DEBUG] Personal Greeting - Request already in progress');
    return;
  }

  // Disable button
  submitButton.prop('disabled', true);

  try {
    loader = showInElement(personalGreetingForm[0]);

    const response = await ajaxFetcher.form(
      config.apiProfilePersonalGreetingUpdateConfirmEndpoint,
      formData
    );

    logger('[DEBUG] Personal Greeting - Confirm response:', response);

    if (response.success) {
      // Show success message
      createAndShowToast(response.message || 'Personal greeting updated successfully', 'success');

      // Replace form with success form or original form
      if (response.successFormHtml) {
        personalGreetingForm.replaceWith(response.successFormHtml);
        // Reinitialize form handlers
        changePersonalGreeting();
      } else if (response.initialFormHtml) {
        personalGreetingForm.replaceWith(response.initialFormHtml);
        changePersonalGreeting();
      } else {
        // Fallback: reload page
        setTimeout(() => {
          window.location.reload();
        }, 1500);
      }
    } else {
      // Handle error response
      let errorMessage = response.message || 'Invalid confirmation code';

      // Use specific error message if available
      if (response.errors && response.errors.verification_code) {
        errorMessage = Array.isArray(response.errors.verification_code)
          ? response.errors.verification_code[0]
          : response.errors.verification_code;
      }

      createAndShowToast(errorMessage, 'error');

      // Clear and focus verification code field
      const verificationField = $('input[name="verification_code"]');
      if (verificationField.length) {
        verificationField.addClass('error').focus().val('');
      }
    }
  } catch (error) {
    loggerError('[ERROR] Personal Greeting - Error confirming update:', error);

    // Handle different types of errors
    let errorMessage = 'Error confirming personal greeting update. Please try again.';

    if (error.status === 422 && error.responseJSON) {
      // Validation error
      const errorData = error.responseJSON;

      if (errorData.errors && errorData.errors.verification_code) {
        errorMessage = Array.isArray(errorData.errors.verification_code)
          ? errorData.errors.verification_code[0]
          : errorData.errors.verification_code;
      } else if (errorData.message) {
        errorMessage = errorData.message;
      }
    } else if (error.status === 500 && error.responseJSON && error.responseJSON.message) {
      // Server error
      errorMessage = error.responseJSON.message;
    } else if (error.responseJSON && error.responseJSON.message) {
      // Other error
      errorMessage = error.responseJSON.message;
    }

    createAndShowToast(errorMessage, 'error');

    // Clear and focus verification code field
    const verificationField = $('input[name="verification_code"]');
    if (verificationField.length) {
      verificationField.addClass('error').focus().val('');
    }
  } finally {
    if (loader) {
      hideInElement(loader);
    }

    // Re-enable button after delay
    setTimeout(() => {
      if (submitButton.length) {
        submitButton.prop('disabled', false);
      }
    }, 1000);

    checkNotifications();
  }
};

/**
 * Handle initial personal greeting update request
 */
const initiatePersonalGreetingUpdate = async (form, formData) => {
  let loader = null;

  logger('[DEBUG] Personal Greeting - Initiate update started');

  try {
    loader = showInElement(form[0]);

    const response = await ajaxFetcher.form(
      config.apiProfilePersonalGreetingUpdateInitiateEndpoint,
      formData
    );

    logger('[DEBUG] Personal Greeting - Initiate response:', response);

    if (response.success) {
      const confirmationFormHtml = response.confirmation_form_html;

      // Replace form with confirmation form
      if (confirmationFormHtml) {
        $(form).replaceWith(confirmationFormHtml);
        // Reinitialize form handlers
        changePersonalGreeting();

        // Add event listener for cancel button
        $('.btn._border-red._big, .btn._gray').on('click', function (e) {
          e.preventDefault();
          cancelPersonalGreetingUpdate(this);
        });
      } else {
        createAndShowToast('Error loading confirmation form', 'error');
      }
    } else {
      // Handle error response
      let errorMessage = response.message || 'Error updating personal greeting. Please try again.';

      // Handle validation errors
      if (response.errors) {
        const errorMessages = Object.values(response.errors)
          .flat()
          .filter(msg => msg && msg.trim() !== '')
          .join(', ');

        if (errorMessages) {
          errorMessage = errorMessages;
        }
      }

      createAndShowToast(errorMessage, 'error');
    }
  } catch (error) {
    loggerError('[ERROR] Personal Greeting - Error initiating update:', error);

    // Handle different types of errors
    let errorMessage = 'Error updating personal greeting. Please try again.';

    if (error.status === 422 && error.responseJSON) {
      // Validation error
      const errorData = error.responseJSON;

      if (errorData.errors) {
        const errorMessages = Object.values(errorData.errors)
          .flat()
          .filter(msg => msg && msg.trim() !== '')
          .join(', ');

        if (errorMessages) {
          errorMessage = errorMessages;
        }
      } else if (errorData.message) {
        errorMessage = errorData.message;
      }
    } else if (error.status === 500 && error.responseJSON && error.responseJSON.message) {
      // Server error
      errorMessage = error.responseJSON.message;
    } else if (error.responseJSON && error.responseJSON.message) {
      // Other error
      errorMessage = error.responseJSON.message;
    }

    createAndShowToast(errorMessage, 'error');
  } finally {
    if (loader) {
      hideInElement(loader);
    }
    checkNotifications();
  }
};

/**
 * Main form handler initialization
 */
const changePersonalGreeting = () => {
  const form = $('#personal-greeting-form');

  if (!form.length) {
    logger('[DEBUG] Personal Greeting - Form not found, skipping initialization');
    return;
  }

  logger('[DEBUG] Personal Greeting - Initializing form handlers');

  // Remove all existing event handlers
  $('.btn._border-red._big, .btn._gray').off();

  // Add single event handler for cancel buttons
  $('.btn._border-red._big, .btn._gray').on('click', function (e) {
    e.preventDefault();
    e.stopPropagation();
    // Pass the button element to the cancel function
    cancelPersonalGreetingUpdate(this);
  });

  // Track touched fields for validation
  const touchedFields = new Set();

  // Initialize jQuery validation
  form.validate({
    rules: {
      personal_greeting: {
        required: true,
        minlength: 3,
        maxlength: 100,
      },
      verification_code: {
        required: true,
        digits: true,
        minlength: 6,
        maxlength: 6,
      },
    },
    messages: {
      personal_greeting: {
        required: '',
        minlength: '',
        maxlength: '',
      },
      verification_code: {
        required: '',
        digits: '',
        minlength: '',
        maxlength: '',
      },
    },
    // Disable automatic validation
    onfocusout: false,
    onkeyup: false,
    onclick: false,
    // Run validation only on form submission
    invalidHandler: function (event, validator) {
      // Mark all fields as touched when form submission is attempted
      form.find('input, select, textarea').each(function () {
        const fieldName = $(this).attr('name');
        if (fieldName) {
          touchedFields.add(fieldName);
        }
      });
      validateAndToggleButton();
    },
  });

  // Function to validate and toggle submit button
  const validateAndToggleButton = () => {
    let isValid = true;

    // Check each field with validation rules
    $.each(form.validate().settings.rules, function (fieldName, _) {
      const field = form.find(`[name="${fieldName}"]`);

      // Only validate touched fields
      if (touchedFields.has(fieldName)) {
        if (!field.valid()) {
          isValid = false;
        }
      }
    });

    const submitButton = form.find('button[type="submit"]');
    if (submitButton.length) {
      submitButton.prop('disabled', !isValid);
    }
  };

  // Track field focus for touched state
  form.find('input, select, textarea').on('focus', function () {
    const fieldName = $(this).attr('name');
    if (fieldName) {
      touchedFields.add(fieldName);
    }
  });

  // Validate touched fields on input/change
  form.find('input, select, textarea').on('input change blur', function () {
    const fieldName = $(this).attr('name');
    if (fieldName && touchedFields.has(fieldName)) {
      validateAndToggleButton();
    }
  });

  // Handle form submission
  form.on('submit', async function (e) {
    e.preventDefault();

    logger('[DEBUG] Personal Greeting - Form submitted');

    // Validate form before submission
    if (!form.valid()) {
      logger('[DEBUG] Personal Greeting - Form validation failed');
      return false;
    }

    const formData = new FormData(this);

    // Determine if this is a confirmation form or initial form
    const isConfirmationForm =
      $(this).find('input[name="verification_code"]').length > 0 ||
      $(this).attr('action').includes('confirm');

    if (isConfirmationForm) {
      logger('[DEBUG] Personal Greeting - Handling confirmation form');
      await confirmPersonalGreetingUpdate(formData);
    } else {
      logger('[DEBUG] Personal Greeting - Handling initiation form');
      await initiatePersonalGreetingUpdate(this, formData);
    }
  });

  logger('[DEBUG] Personal Greeting - Form handlers initialized successfully');
};

/**
 * Initialize personal greeting form handling
 */
const initChangePersonalGreeting = () => {
  logger('[DEBUG] Personal Greeting - Initializing');
  changePersonalGreeting();
};

export { changePersonalGreeting, initChangePersonalGreeting };
