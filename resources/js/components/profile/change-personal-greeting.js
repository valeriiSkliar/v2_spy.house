import { logger, loggerError } from '@/helpers/logger';
import { checkNotifications } from '@/helpers/notification-checker';
import { createAndShowToast } from '@/utils/uiHelpers';
import { config } from '../../config';
import { ajaxFetcher } from '../fetcher/ajax-fetcher';
import { hideInElement, showInElement } from '../loader';
import {
  handleChangePersonalGreetingValidationErrors,
  initChangePersonalGreetingValidation,
} from './change-personal-greeting-validation';

/**
 * Cancel personal greeting update process
 */
const cancelPersonalGreetingUpdate = async () => {
  const $formContainer = $('#personal-greeting-form-container');
  const $form = $formContainer.find('#personal-greeting-form');
  let loader = null;

  logger('[DEBUG] Personal Greeting - Cancel update requested');

  try {
    loader = showInElement($formContainer[0]);
    const response = await ajaxFetcher.get(
      config.apiProfilePersonalGreetingCancelEndpoint,
      null,
      {}
    );

    if (response.success) {
      // Use the server-provided HTML form
      if (response.initialFormHtml) {
        $form.replaceWith(response.initialFormHtml);
        // Reinitialize form handlers
        changePersonalGreeting();
      } else {
        // Fallback to reloading the page if we don't get the form HTML
        logger('[WARNING] Personal Greeting - No initial form HTML received, reloading page');
        window.location.reload();
      }
    } else {
      createAndShowToast(response.message || 'Error cancelling personal greeting update', 'error');
    }
  } catch (error) {
    loggerError('[ERROR] Personal Greeting - Error cancelling update:', error);

    // Handle server error responses
    let errorMessage = 'Error cancelling personal greeting update. Please try again.';

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
 * Confirm personal greeting update with verification code
 */
const confirmPersonalGreetingUpdate = async formData => {
  let loader = null;
  const $formContainer = $('#personal-greeting-form-container');
  const $form = $formContainer.find('#personal-greeting-form');

  logger('[DEBUG] Personal Greeting - Confirm update started');

  try {
    loader = showInElement($formContainer[0]);

    const response = await ajaxFetcher.form(
      config.apiProfilePersonalGreetingUpdateConfirmEndpoint,
      formData
    );

    if (response.success) {
      createAndShowToast(response.message || 'Personal greeting updated successfully', 'success');

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
      changePersonalGreeting();
    } else {
      // Handle server validation errors
      handleChangePersonalGreetingValidationErrors(response, $form);
      createAndShowToast(response.message || 'Error confirming personal greeting update', 'error');

      // Focus verification code field if it exists
      const $verificationCodeField = $form.find('input[name="verification_code"]');
      if ($verificationCodeField.length) {
        $verificationCodeField.val('').focus();
      }
    }
  } catch (error) {
    loggerError('[ERROR] Personal Greeting - Error confirming update:', error);

    // Handle validation errors from server
    if (error.status === 422) {
      const response = error.responseJSON || {};
      handleChangePersonalGreetingValidationErrors(response, $form);

      // Clear and focus verification code field
      const $verificationCodeField = $form.find('input[name="verification_code"]');
      if ($verificationCodeField.length) {
        $verificationCodeField.val('').focus();
      }

      createAndShowToast(response.message || 'Invalid verification code', 'error');
    } else {
      createAndShowToast('Error confirming personal greeting update. Please try again.', 'error');
    }
  } finally {
    if (loader) {
      hideInElement(loader);
    }
    checkNotifications();
  }
};

/**
 * Main form handler for personal greeting changes
 */
const changePersonalGreeting = () => {
  let loader = null;
  const $formContainer = $('#personal-greeting-form-container');
  const $form = $formContainer.find('#personal-greeting-form');

  logger('[DEBUG] Personal Greeting - Form found', { debug: true });

  if ($form.length) {
    // Remove previous event handlers
    $form.off('submit');

    // Determine current form step
    const isConfirmationStep = $form.find('input[name="verification_code"]').length > 0;

    // Initialize form validation
    let validator = null;
    try {
      validator = initChangePersonalGreetingValidation($form, isConfirmationStep);
      logger(
        '[DEBUG] Personal Greeting - Validator initialized',
        {
          validatorExists: !!validator,
          rules: validator?.settings?.rules,
          isConfirmationStep,
        },
        { debug: true }
      );
    } catch (error) {
      logger(
        '[DEBUG] Personal Greeting - Validator initialization failed',
        { error },
        { debug: true }
      );
      return;
    }

    // Form submission handler
    $form.on('submit', async function (e) {
      logger('[DEBUG] Personal Greeting - Form submit triggered', { debug: true });
      e.preventDefault();

      // Check if form is valid
      if (validator) {
        const isValid = validator.form();
        logger('[DEBUG] Personal Greeting - Form validation result', { isValid }, { debug: true });

        if (!isValid) {
          return false;
        }
      }

      // Get form data
      const formData = new FormData(this);

      // Log form data for debugging
      logger(
        '[DEBUG] Personal Greeting - Form data',
        {
          hasVerificationCode: formData.has('verification_code'),
          verificationCode: formData.get('verification_code'),
          personalGreeting: formData.get('personal_greeting'),
          formFields: Array.from(formData.entries()).map(([key, value]) => key),
          isConfirmationStep,
        },
        { debug: true }
      );

      if (isConfirmationStep) {
        // Handle confirmation submission
        await confirmPersonalGreetingUpdate(formData);
      } else {
        // Handle initial personal greeting update request
        loader = showInElement($formContainer[0]);

        try {
          const response = await ajaxFetcher.form(
            config.apiProfilePersonalGreetingUpdateInitiateEndpoint,
            formData
          );

          if (response.success) {
            const confirmationFormHtml = response.confirmation_form_html;

            // Replace form with confirmation form
            if (confirmationFormHtml) {
              $form.replaceWith(confirmationFormHtml);
              // Reinitialize form handlers for the new confirmation form
              changePersonalGreeting();
              // Add event listener for cancel button
              $('.btn._border-red._big').on('click', function (e) {
                e.preventDefault();
                cancelPersonalGreetingUpdate();
              });
            } else {
              createAndShowToast('Error loading confirmation form', 'error');
            }
          } else {
            handleChangePersonalGreetingValidationErrors(response, $form);
            createAndShowToast(
              response.message || 'Error updating personal greeting. Please try again.',
              'error'
            );
          }
        } catch (error) {
          loggerError('[ERROR] Personal Greeting - Error updating:', error);

          if (error.status === 422 && error.responseJSON) {
            handleChangePersonalGreetingValidationErrors(error.responseJSON, $form);
            createAndShowToast(error.responseJSON.message || 'Validation errors occurred', 'error');
          } else {
            createAndShowToast('Error updating personal greeting. Please try again.', 'error');
          }
        } finally {
          if (loader) {
            hideInElement(loader);
          }
          checkNotifications();
        }
      }
    });
  }

  // Add event listener for cancel button if it exists
  $('.btn._border-red._big').on('click', function (e) {
    e.preventDefault();
    cancelPersonalGreetingUpdate();
  });
};

/**
 * Initialize personal greeting form handling
 */
const initChangePersonalGreeting = () => {
  logger('[DEBUG] Personal Greeting - Initializing', { debug: true });
  changePersonalGreeting();
};

export { changePersonalGreeting, initChangePersonalGreeting };
