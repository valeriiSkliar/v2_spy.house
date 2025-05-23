import { loggerError } from '@/helpers/logger';
import { config } from '../../config';
import { createAndShowToast } from '../../utils/uiHelpers';
import { ajaxFetcher } from '../fetcher/ajax-fetcher';
import { hideInButton, hideInElement, showInButton, showInElement } from '../loader';
import { initProfileFormElements, profileFormElements } from './profile-form-elements';
import { initProfileSettingsValidation } from './profile-settings-form-validation';
import { initSocialMessengerField, messengerManager } from './social-messenger-field';

async function submitFormHandler(e) {
  let loader = null;
  try {
    e.preventDefault();

    if (
      profileFormElements.profileSettingsTabContent &&
      profileFormElements.profileSettingsTabContent.length > 0
    ) {
      loader = showInElement(profileFormElements.profileSettingsTabContent[0]);
    } else {
      if (profileFormElements.form && profileFormElements.form.length > 0) {
        loader = showInElement(profileFormElements.form[0]);
      }
    }

    const formData = new FormData(this);

    showInButton(profileFormElements.submitButton);
    const response = await ajaxFetcher.form(config.apiProfileSettingsEndpoint, formData, 'PUT');

    if (response.success) {
      const formContainer = profileFormElements.form;
      if (formContainer && response.user) {
        if (
          response.user.messenger_type !== undefined &&
          response.user.messenger_contact !== undefined
        ) {
          if (messengerManager) {
            messengerManager.updateFromServer(
              response.user.messenger_type,
              response.user.messenger_contact
            );
          } else {
            profileFormElements.messengerType.val(response.user.messenger_type);
            profileFormElements.messengerContact.val(response.user.messenger_contact);
          }

          profileFormElements.messengerContact.trigger('input');
        }

        if (response.user.login !== undefined) {
          profileFormElements.login.val(response.user.login);
          if (profileFormElements.userPreviewName.length) {
            profileFormElements.userPreviewName.text(response.user.login);
          }
        }
        if (response.user.experience !== undefined) {
          profileFormElements.experience.val(response.user.experience);
        }
        if (response.user.scope_of_activity !== undefined) {
          profileFormElements.scopeOfActivity.val(response.user.scope_of_activity);
        }

        // Update field statuses - only visual highlighting, no error messages
        if (response.field_statuses) {
          Object.entries(response.field_statuses).forEach(([field, status]) => {
            const $field = $(`[name="${field}"]`);
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

        if (response.settingsFormHtml) {
          formContainer.html(response.settingsFormHtml);
          initProfileFormElements();
          updateProfileSettings();
        }
      }
      createAndShowToast(response.message, 'success');
    } else {
      // Handle validation errors using new response structure
      if (response.field_statuses) {
        // Clear previous error states
        $('input, select').removeClass('error valid');

        Object.entries(response.field_statuses).forEach(([field, status]) => {
          const $field = $(`[name="${field}"]`);
          if ($field.length) {
            // Apply visual highlighting only - no error messages under fields
            if (status.status === 'error') {
              $field.addClass('error');
            } else if (status.status === 'success') {
              $field.addClass('valid');
            }
          }
        });

        // Scroll to first error field
        const firstErrorField = Object.keys(response.field_statuses).find(
          field => response.field_statuses[field].status === 'error'
        );
        if (firstErrorField) {
          const $firstError = $(`[name="${firstErrorField}"]`);
          // if ($firstError.length) {
          //   $('html, body').animate(
          //     {
          //       scrollTop: $firstError.offset().top - 100,
          //     },
          //     500
          //   );
          // }
        }
      }

      // Show toast message for errors
      createAndShowToast(response.message, 'error');
    }
  } catch (error) {
    loggerError('Error updating profile settings:', error);

    if (error.status === 422 && error.responseJSON) {
      const errorData = error.responseJSON;

      // Clear previous errors
      $('input, select').removeClass('error valid');

      // Handle validation errors from both formats
      if (errorData.errors) {
        Object.keys(errorData.errors).forEach(field => {
          const $field = $(`[name="${field}"]`);
          if ($field.length) {
            $field.addClass('error');
          }
        });
      }

      // Handle field statuses from error response
      if (errorData.field_statuses) {
        Object.entries(errorData.field_statuses).forEach(([field, status]) => {
          const $field = $(`[name="${field}"]`);
          if ($field.length) {
            // Apply visual highlighting only - no error messages under fields
            if (status.status === 'error') {
              $field.addClass('error');
            } else if (status.status === 'success') {
              $field.addClass('valid');
            }
          }
        });
      }

      // Show toast message
      createAndShowToast(errorData.message || 'Validation errors occurred', 'error');
    } else if (error.status === 401) {
      createAndShowToast('Unauthorized. Please log in again.', 'error');
    } else if (error.status === 403) {
      createAndShowToast('Forbidden. You do not have permission to perform this action.', 'error');
    } else if (error.status >= 500) {
      createAndShowToast('Server error. Please try again later.', 'error');
    } else {
      createAndShowToast('Error updating profile settings. Please try again.', 'error');
    }
  } finally {
    hideInButton(profileFormElements.submitButton);
    hideInElement(loader);
  }
}

const updateProfileSettings = () => {
  if (profileFormElements.form.length) {
    // Remove previous event handlers to avoid duplicates
    profileFormElements.form.off('submit');

    // Destroy existing validator if present
    if (profileFormElements.form.data('validator')) {
      profileFormElements.form.removeData('validator');
    }

    // Initialize jQuery Validation with custom submitHandler
    const validator = initProfileSettingsValidation(profileFormElements.form);

    // Ensure the validator is created and override submitHandler
    if (validator) {
      // Remove the default form submission behavior
      profileFormElements.form.off('submit.validate');

      // Set our custom submit handler
      profileFormElements.form.on('submit', function (e) {
        e.preventDefault();

        // Validate the form first
        if (validator.form()) {
          // If validation passes, call our custom handler
          submitFormHandler.call(this, e);
        }

        return false;
      });
    } else {
      // Fallback for forms without validation
      profileFormElements.form.on('submit', submitFormHandler);
    }

    // Initialize social messenger field
    initSocialMessengerField();
  }
};

const initUpdateProfileSettings = () => {
  initProfileFormElements();
  if (profileFormElements.form.length) {
    updateProfileSettings();
  }
};

export { initUpdateProfileSettings };
