import { logger, loggerError } from '@/helpers/logger';
import { checkNotifications } from '@/helpers/notification-checker';
import { createAndShowToast } from '@/utils/uiHelpers';
import $ from 'jquery';
import { config } from '../../config';
import { ajaxFetcher } from '../fetcher/ajax-fetcher';
import { hideInElement, showInElement } from '../loader';
import {
  handleChangePasswordValidationErrors,
  initChangePasswordValidation,
} from './change-password-validation';

/**
 * Cancel password update process
 */
const cancelPasswordUpdate = async () => {
  const $formContainer = $('#change-password-form-container');
  const $form = $formContainer.find('#change-password-form');
  let loader = null;

  logger('[DEBUG] Change Password - Cancel update requested');

  try {
    loader = showInElement($formContainer[0]);
    const response = await ajaxFetcher.get(config.apiProfilePasswordCancelEndpoint, null, {});

    logger('[DEBUG] Change Password - Cancel response:', response);

    if (response.success) {
      // Use the server-provided HTML form
      if (response.initialFormHtml) {
        $form.replaceWith(response.initialFormHtml);
        // Reinitialize form handlers
        changePassword();
      } else {
        // Fallback to reloading the page if we don't get the form HTML
        logger('[WARNING] Change Password - No initial form HTML received, reloading page');
        window.location.reload();
      }

      // Show success message
      if (response.message) {
        createAndShowToast(response.message, 'success');
      }
    } else {
      createAndShowToast(response.message || 'Error cancelling password update', 'error');
    }
  } catch (error) {
    loggerError('[ERROR] Change Password - Error cancelling update:', error);

    // Handle server error responses
    let errorMessage = 'Error cancelling password update. Please try again.';

    if (error.responseJSON && error.responseJSON.message) {
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
 * Confirm password update with verification code
 */
const confirmPasswordUpdate = async formData => {
  let loader = null;
  const $formContainer = $('#change-password-form-container');
  const $form = $formContainer.find('#change-password-form');

  logger('[DEBUG] Change Password - Confirm update started');

  try {
    loader = showInElement($formContainer[0]);

    const response = await ajaxFetcher.form(
      config.apiProfilePasswordUpdateConfirmEndpoint,
      formData
    );

    logger('[DEBUG] Change Password - Confirm response:', response);

    if (response.success) {
      createAndShowToast(response.message || 'Password updated successfully', 'success');

      // Replace form with success message or original form
      if (response.successFormHtml) {
        $form.replaceWith(response.successFormHtml);
      } else if (response.initialFormHtml) {
        $form.replaceWith(response.initialFormHtml);
      } else {
        // Fallback: reload page
        setTimeout(() => {
          window.location.reload();
        }, 1500);
      }

      checkNotifications();
      changePassword();
    } else {
      // Handle server validation errors
      handleChangePasswordValidationErrors(response, $form);
      createAndShowToast(response.message || 'Error confirming password update', 'error');

      // Focus verification code field if it exists
      const $verificationCodeField = $form.find('input[name="verification_code"]');
      if ($verificationCodeField.length) {
        $verificationCodeField.val('').focus();
      }
    }
  } catch (error) {
    loggerError('[ERROR] Change Password - Error confirming update:', error);

    // Handle validation errors from server
    if (error.status === 422) {
      const response = error.responseJSON || {};
      handleChangePasswordValidationErrors(response, $form);

      // Clear and focus verification code field
      const $verificationCodeField = $form.find('input[name="verification_code"]');
      if ($verificationCodeField.length) {
        $verificationCodeField.val('').focus();
      }

      createAndShowToast(response.message || 'Invalid verification code', 'error');
    } else {
      createAndShowToast('Error confirming password update. Please try again.', 'error');
    }
  } finally {
    if (loader) {
      hideInElement(loader);
    }
    checkNotifications();
  }
};

/**
 * Handle initial password update request
 */
const initiatePasswordUpdate = async (form, formData) => {
  let loader = null;
  const $formContainer = $('#change-password-form-container');
  const $form = $formContainer.find('#change-password-form');

  logger('[DEBUG] Change Password - Initiate update started');

  try {
    loader = showInElement($formContainer[0]);

    const response = await ajaxFetcher.form(
      config.apiProfilePasswordUpdateInitiateEndpoint,
      formData
    );

    logger('[DEBUG] Change Password - Initiate response:', response);

    if (response.success) {
      const confirmationFormHtml = response.confirmation_form_html;

      // Replace form with confirmation form
      if (confirmationFormHtml) {
        $form.replaceWith(confirmationFormHtml);
        // Reinitialize form handlers for the new confirmation form
        changePassword();
        // Reinitialize cancel button
        initCancelButton();
      } else {
        createAndShowToast('Error loading confirmation form', 'error');
      }
    } else {
      handleChangePasswordValidationErrors(response, $form);
      createAndShowToast(response.message || 'Error updating password. Please try again.', 'error');
    }
  } catch (error) {
    loggerError('[ERROR] Change Password - Error initiating update:', error);

    if (error.status === 422 && error.responseJSON) {
      handleChangePasswordValidationErrors(error.responseJSON, $form);
      createAndShowToast(error.responseJSON.message || 'Validation errors occurred', 'error');
    } else {
      createAndShowToast('Error updating password. Please try again.', 'error');
    }
  } finally {
    if (loader) {
      hideInElement(loader);
    }
    checkNotifications();
  }
};

/**
 * Initialize cancel button event listener
 */
const initCancelButton = () => {
  $('[data-action="cancel-password-update"]')
    .off('click')
    .on('click', function (e) {
      e.preventDefault();
      cancelPasswordUpdate();
    });
};

/**
 * Main form handler for password changes
 */
const changePassword = () => {
  let loader = null;
  const $formContainer = $('#change-password-form-container');
  const $form = $formContainer.find('#change-password-form');

  logger('[DEBUG] Change Password - Form found', { debug: true });

  if ($form.length) {
    // Remove previous event handlers
    $form.off('submit');

    // Determine current form step
    const isConfirmationStep = $form.find('input[name="verification_code"]').length > 0;

    // Initialize form validation
    let validator = null;
    try {
      validator = initChangePasswordValidation($form, isConfirmationStep);
      logger(
        '[DEBUG] Change Password - Validator initialized',
        {
          validatorExists: !!validator,
          rules: validator?.settings?.rules,
          isConfirmationStep,
        },
        { debug: true }
      );
    } catch (error) {
      logger(
        '[DEBUG] Change Password - Validator initialization failed',
        { error },
        { debug: true }
      );
      // Continue without validation, but with warning
      validator = null;
    }

    // Form submission handler
    $form.on('submit', async function (e) {
      logger('[DEBUG] Change Password - Form submit triggered', { debug: true });
      e.preventDefault();

      // Check if form is valid
      if (validator) {
        const isValid = validator.form();
        logger('[DEBUG] Change Password - Form validation result', { isValid }, { debug: true });

        if (!isValid) {
          return false;
        }
      }

      // Get form data
      const formData = new FormData(this);

      // Log form data for debugging
      logger(
        '[DEBUG] Change Password - Form data',
        {
          hasVerificationCode: formData.has('verification_code'),
          verificationCode: formData.get('verification_code'),
          formFields: Array.from(formData.entries()).map(([key, value]) => key),
          isConfirmationStep,
        },
        { debug: true }
      );

      if (isConfirmationStep) {
        // Handle confirmation submission
        await confirmPasswordUpdate(formData);
      } else {
        // Handle initial password update request
        await initiatePasswordUpdate(this, formData);
      }
    });
  }

  // Initialize cancel button
  initCancelButton();
};

/**
 * Initialize change password form handling
 */
const initChangePassword = () => {
  logger('[DEBUG] Change Password - Initializing', { debug: true });
  changePassword();
};

export { changePassword, initChangePassword };
