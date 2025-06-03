import { logger, loggerError } from '@/helpers/logger';
import { checkNotifications } from '@/helpers/notification-checker';
import { createAndShowToast } from '@/utils';
import { config } from '../../config';
import { ajaxFetcher } from '../fetcher/ajax-fetcher';
import { hideInElement, showInElement } from '../loader';
import {
  handleIpRestrictionValidationErrors,
  initIpRestrictionValidation,
} from './update-ip-restriction-validation';

/**
 * Initialize cancel button event listener
 */
const initCancelButton = () => {
  $('[data-action="cancel-ip-restriction"]')
    .off('click')
    .on('click', function (e) {
      e.preventDefault();
      cancelIpRestrictionUpdate();
    });
};

/**
 * Cancel IP restriction update process
 */
const cancelIpRestrictionUpdate = async () => {
  const $formContainer = $('#ip-restriction-form-container');
  const $form = $formContainer.find('#ip-restriction-form');
  let loader = null;

  logger('[DEBUG] IP Restriction - Cancel update requested');

  try {
    loader = showInElement($formContainer[0]);

    // For now, just reset the form since we don't have a two-step process yet
    $form[0].reset();
    $form.find('input, textarea').removeClass('error valid');

    createAndShowToast('IP restriction update cancelled', 'success');
  } catch (error) {
    loggerError('[ERROR] IP Restriction - Error cancelling update:', error);
    createAndShowToast('Error cancelling IP restriction update. Please try again.', 'error');
  } finally {
    if (loader) {
      hideInElement(loader);
    }
    checkNotifications();
  }
};

/**
 * Main form handler for IP restriction updates
 */
const updateIpRestriction = () => {
  let loader = null;
  const $formContainer = $('#ip-restriction-form-container');
  const $form = $formContainer.find('#ip-restriction-form');

  logger('[DEBUG] IP Restriction - Form found');

  if ($form.length) {
    // Remove previous event handlers
    $form.off('submit');

    // Initialize form validation
    let validator = null;
    try {
      validator = initIpRestrictionValidation($form, false);
      logger('[DEBUG] IP Restriction - Validator initialized', {
        validatorExists: !!validator,
        rules: validator?.settings?.rules,
      });
    } catch (error) {
      logger('[DEBUG] IP Restriction - Validator initialization failed', { error });
      // Continue without validation, but with warning
      validator = null;
    }

    // Ensure textareas auto-resize
    const adjustHeight = element => {
      element.style.height = 'auto';
      element.style.height = element.scrollHeight + 'px';
    };

    const textareas = $form.find('.auto-resize');
    textareas.each(function () {
      this.addEventListener('input', function () {
        adjustHeight(this);
      });
    });

    // Form submission handler
    $form.on('submit', async function (e) {
      logger('[DEBUG] IP Restriction - Form submit triggered');
      e.preventDefault();

      // Check if form is valid
      if (validator) {
        const isValid = validator.form();
        logger('[DEBUG] IP Restriction - Form validation result', { isValid });

        if (!isValid) {
          return false;
        }
      }

      // Get form data
      const formData = new FormData(this);

      // Log form data for debugging
      logger('[DEBUG] IP Restriction - Form data', {
        ipRestrictions: formData.get('ip_restrictions'),
        formFields: Array.from(formData.entries()).map(([key, value]) => key),
      });

      // Handle form submission
      await submitIpRestrictionUpdate(formData);
    });
  }

  // Initialize cancel button
  initCancelButton();
};

/**
 * Handle IP restriction form submission
 */
const submitIpRestrictionUpdate = async formData => {
  let loader = null;
  const $formContainer = $('#ip-restriction-form-container');
  const $form = $formContainer.find('#ip-restriction-form');

  logger('[DEBUG] IP Restriction - Submit update started');

  try {
    loader = showInElement($formContainer[0]);

    const response = await ajaxFetcher.form(config.apiProfileIpRestrictionUpdateEndpoint, formData);

    logger('[DEBUG] IP Restriction - Submit response:', response);

    if (response.success) {
      createAndShowToast(response.message || 'IP restrictions updated successfully', 'success');

      // Replace form with success form or refresh form
      if (response.successFormHtml) {
        $form.replaceWith(response.successFormHtml);
        updateIpRestriction();
      }

      // Clear password field
      $form.find('input[name="password"]').val('');

      checkNotifications();
    } else {
      // Handle server validation errors
      handleIpRestrictionValidationErrors(response, $form);
      createAndShowToast(response.message || 'Error updating IP restrictions', 'error');
    }
  } catch (error) {
    loggerError('[ERROR] IP Restriction - Error updating:', error);

    if (error.status === 422 && error.responseJSON) {
      handleIpRestrictionValidationErrors(error.responseJSON, $form);
      createAndShowToast(error.responseJSON.message || 'Validation errors occurred', 'error');
    } else {
      createAndShowToast('Error updating IP restrictions. Please try again.', 'error');
    }
  } finally {
    if (loader) {
      hideInElement(loader);
    }
    checkNotifications();
  }
};

/**
 * Initialize IP restriction form handling
 */
const initUpdateIpRestriction = () => {
  logger('[DEBUG] IP Restriction - Initializing');
  updateIpRestriction();
};

export { initUpdateIpRestriction, updateIpRestriction };
