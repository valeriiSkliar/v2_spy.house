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

    // Проверяем наличие DOM-элемента перед показом лоадера
    if (
      profileFormElements.profileSettingsTabContent &&
      profileFormElements.profileSettingsTabContent.length > 0
    ) {
      loader = showInElement(profileFormElements.profileSettingsTabContent[0]);
    } else {
      // Альтернативный вариант - показываем лоадер в контейнере формы
      if (profileFormElements.form && profileFormElements.form.length > 0) {
        loader = showInElement(profileFormElements.form[0]);
      }
    }

    const formData = new FormData(this);

    showInButton(profileFormElements.submitButton);
    const response = await ajaxFetcher.form(config.apiProfileSettingsEndpoint, formData, 'PUT');
    if (response.success) {
      // Update form fields with new values from server
      const formContainer = profileFormElements.form;
      if (formContainer && response.user) {
        // Update messenger field values using unified state manager
        if (
          response.user.messenger_type !== undefined &&
          response.user.messenger_contact !== undefined
        ) {
          // Use the messenger manager to update from server response
          if (messengerManager) {
            messengerManager.updateFromServer(
              response.user.messenger_type,
              response.user.messenger_contact
            );
          } else {
            // Fallback if manager not available
            profileFormElements.messengerType.val(response.user.messenger_type);
            profileFormElements.messengerContact.val(response.user.messenger_contact);
          }

          // Trigger input event to validate the value
          profileFormElements.messengerContact.trigger('input');
        }

        // Update other form fields
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

        // If the server still returns HTML, use it as a fallback
        if (response.settingsFormHtml) {
          formContainer.html(response.settingsFormHtml);
          initProfileFormElements();
          updateProfileSettings();
        }
      }
      createAndShowToast(response.message, 'success');
    } else {
      createAndShowToast(response.message, 'error');
    }
  } catch (error) {
    loggerError('Error updating profile settings:', error);

    // Handle different types of errors
    if (error.status === 422 && error.responseJSON) {
      // Validation errors
      const errorData = error.responseJSON;
      if (errorData.errors) {
        // Display validation errors
        const errorMessages = Object.values(errorData.errors).flat().join(', ');
        createAndShowToast(errorMessages, 'error');
      } else if (errorData.message) {
        createAndShowToast(errorData.message, 'error');
      } else {
        createAndShowToast('Validation errors occurred', 'error');
      }
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
