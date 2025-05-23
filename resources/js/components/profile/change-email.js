import { logger } from '@/helpers/logger';
import { createAndShowToast } from '@/utils';
import { config } from '../../config';
import { checkNotifications } from '../../helpers/notification-checker';
import { ajaxFetcher } from '../fetcher/ajax-fetcher';
import { hideInElement, showInElement } from '../loader';
import {
  handleChangeEmailValidationErrors,
  initChangeEmailValidation,
} from './change-email-validation';

const cancelEmailUpdate = async () => {
  const $formContainer = $('#change-email-form-container');
  const $form = $formContainer.find('#change-email-form');
  let loader = null;
  try {
    loader = showInElement($formContainer[0]);
    const response = await ajaxFetcher.get(config.apiProfileEmailCancelEndpoint, null, {});

    if (response.success) {
      // Use the server-provided HTML form
      if (response.initialFormHtml) {
        $form.replaceWith(response.initialFormHtml);

        // Reinitialize form handlers
        changeEmail();
      }
    } else {
      createAndShowToast(response.message || 'Error cancelling email update', 'error');
    }
  } catch (error) {
    console.error('Error cancelling email update:', error);
    createAndShowToast('Error cancelling email update. Please try again.', 'error');
  } finally {
    // Hide loader immediately
    if (loader) {
      hideInElement(loader);
    }
    checkNotifications();
  }
};

const confirmEmailUpdate = async formData => {
  let loader = null;
  const $formContainer = $('#change-email-form-container');
  const $form = $formContainer.find('#change-email-form');

  try {
    loader = showInElement($formContainer[0]);
    const response = await ajaxFetcher.form(config.apiProfileEmailUpdateConfirmEndpoint, formData);

    if (response.success) {
      createAndShowToast(response.message, 'success');

      // Replace form with success message or original form
      if (response.successFormHtml) {
        $form.replaceWith(response.successFormHtml);
      } else if (response.initialFormHtml) {
        $form.replaceWith(response.initialFormHtml);
      }
      checkNotifications();
      changeEmail();
    } else {
      // Handle server validation errors
      handleChangeEmailValidationErrors(response, $form);
      createAndShowToast(response.message || 'Error confirming email update', 'error');

      // Focus verification code field if it exists
      const $verificationCodeField = $form.find('input[name="verification_code"]');
      if ($verificationCodeField.length) {
        $verificationCodeField.val('').focus();
      }
    }
  } catch (error) {
    console.error('Error confirming email update:', error);

    // Handle validation errors from server
    if (error.status === 422) {
      const response = error.responseJSON || {};
      handleChangeEmailValidationErrors(response, $form);

      // Clear and focus verification code field
      const $verificationCodeField = $form.find('input[name="verification_code"]');
      if ($verificationCodeField.length) {
        $verificationCodeField.val('').focus();
      }

      createAndShowToast(response.message || 'Invalid verification code', 'error');
    } else {
      createAndShowToast('Error confirming email update. Please try again.', 'error');
    }
  } finally {
    // Hide loader immediately
    if (loader) {
      hideInElement(loader);
    }
    checkNotifications();
  }
};

const changeEmail = () => {
  let loader = null;
  const $formContainer = $('#change-email-form-container');
  const $form = $formContainer.find('#change-email-form');
  logger('[DEBUG] Change Email - Form found', { debug: true });

  if ($form.length) {
    // Remove previous event handlers
    $form.off('submit');

    // Determine current form step
    const isConfirmationStep = $form.find('input[name="verification_code"]').length > 0;

    // Initialize form validation
    let validator = null;
    try {
      validator = initChangeEmailValidation($form, isConfirmationStep);
      logger(
        '[DEBUG] Change Email - Validator initialized',
        {
          validatorExists: !!validator,
          rules: validator?.settings?.rules,
          isConfirmationStep,
        },
        { debug: true }
      );
    } catch (error) {
      logger('[DEBUG] Change Email - Validator initialization failed', { error }, { debug: true });
      return;
    }

    // Form submission handler
    $form.on('submit', async function (e) {
      logger('[DEBUG] Change Email - Form submit triggered', { debug: true });
      e.preventDefault();

      // Check if form is valid
      if (validator) {
        const isValid = validator.form();
        logger('[DEBUG] Change Email - Form validation result', { isValid }, { debug: true });

        if (!isValid) {
          return false;
        }
      }

      // Get form data
      const formData = new FormData(this);

      // Log form data for debugging
      logger(
        '[DEBUG] Change Email - Form data',
        {
          hasVerificationCode: formData.has('verification_code'),
          verificationCode: formData.get('verification_code'),
          formFields: Array.from(formData.entries()).map(([key, value]) => key),
          isConfirmationStep,
        },
        { debug: true }
      );

      // Validation should have already caught missing verification code
      // If we reach here, the form data should be valid

      if (isConfirmationStep) {
        // Handle confirmation submission
        await confirmEmailUpdate(formData);
      } else {
        // Handle initial email update request
        loader = showInElement($formContainer[0]);

        try {
          const response = await ajaxFetcher.form(
            config.apiProfileEmailUpdateInitiateEndpoint,
            formData
          );

          if (response.success) {
            const message = response.message;
            const confirmationFormHtml = response.confirmation_form_html;

            // // Show success message if provided
            // if (message) {
            //   createAndShowToast(message, 'success');
            // }

            // Replace form with confirmation form
            if (confirmationFormHtml) {
              $form.replaceWith(confirmationFormHtml);
              // Reinitialize form handlers for the new confirmation form
              changeEmail();
              // Add event listener for cancel button
              $('.btn._border-red._big').on('click', function (e) {
                e.preventDefault();
                cancelEmailUpdate();
              });
            }
          } else {
            handleChangeEmailValidationErrors(response, $form);
            createAndShowToast(
              response.message || 'Error updating email. Please try again.',
              'error'
            );
          }
        } catch (error) {
          console.error('Error updating email:', error);

          if (error.status === 422 && error.responseJSON) {
            handleChangeEmailValidationErrors(error.responseJSON, $form);
            createAndShowToast(error.responseJSON.message || 'Validation errors occurred', 'error');
          } else {
            createAndShowToast('Error updating email. Please try again.', 'error');
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
    cancelEmailUpdate();
  });
};

const initChangeEmail = () => {
  logger('[DEBUG] Change Email - Initializing', { debug: true });
  changeEmail();
};

export { changeEmail, initChangeEmail };
