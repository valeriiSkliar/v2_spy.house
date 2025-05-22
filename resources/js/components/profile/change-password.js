import { createAndShowToast } from '@/utils';
import { config } from '../../config';
import { loggerError } from '../../helpers/logger';
import { checkNotifications } from '../../helpers/notification-checker';
import { ajaxFetcher } from '../fetcher/ajax-fetcher';
import { hideInElement, showInElement } from '../loader';

const cancelPasswordUpdate = async () => {
  const form = $('#change-password-form');
  if (!form.length) {
    loggerError('Change password form not found');
    return;
  }
  let loader = null;
  try {
    loader = showInElement('#change-password-form');
    const response = await ajaxFetcher.get(config.apiProfilePasswordCancelEndpoint, null, {});

    if (response.success) {
      // Use the server-provided HTML form
      if (response.initialFormHtml) {
        form.replaceWith(response.initialFormHtml);

        // Reinitialize form handlers
        changePassword();
      } else {
        // Fallback to reloading the page if we don't get the form HTML
        window.location.reload();
      }
    } else {
      createAndShowToast(response.message || 'Error cancelling password update', 'error');
    }
  } catch (error) {
    loggerError('Error cancelling password update:', error);
    createAndShowToast('Error cancelling password update. Please try again.', 'error');
  } finally {
    hideInElement(loader);
    checkNotifications();
  }
};

const confirmPasswordUpdate = async formData => {
  let loader = null;
  try {
    loader = showInElement('#change-password-form');
    const response = await ajaxFetcher.form(
      config.apiProfilePasswordUpdateConfirmEndpoint,
      formData
    );
    if (response.success) {
      // Replace form with success message or original form
      if (response.successFormHtml) {
        $('#change-password-form').replaceWith(response.successFormHtml);

        // Add success message if available
        if (response.successMessage) {
          $('#change-password-form').prepend(response.successMessage);
        }
      } else if (response.initialFormHtml) {
        $('#change-password-form').replaceWith(response.initialFormHtml);
      }
      changePassword();
    } else {
      // Show error message for invalid code
      createAndShowToast(response.message || 'Invalid confirmation code', 'error');

      // Optionally highlight the code input field
      $('input[name="verification_code"]').addClass('error').focus();
    }
  } catch (error) {
    console.log(error);
    loggerError('Error confirming password update:', error);
    createAndShowToast('Error confirming password update. Please try again.', 'error');
  } finally {
    hideInElement(loader);
    checkNotifications();
  }
};

const changePassword = () => {
  const form = $('#change-password-form');
  let loader = null;
  if (form) {
    form.on('submit', async function (e) {
      loader = showInElement('#change-password-form');
      e.preventDefault();
      const formData = new FormData(this);

      // Determine if this is a confirmation form or initial form
      const isConfirmationForm =
        $(this).find('input[name="verification_code"]').length > 0 ||
        $(this).attr('action').includes('confirm');

      if (isConfirmationForm) {
        // Handle confirmation submission
        await confirmPasswordUpdate(formData);
      } else {
        // Handle initial password update request
        try {
          const response = await ajaxFetcher.form(
            config.apiProfilePasswordUpdateInitiateEndpoint,
            formData
          );

          if (response.success) {
            const message = response.message;
            const confirmationMethod = response.confirmation_method;
            const confirmationFormHtml = response.confirmation_form_html;

            // Replace form with confirmation form
            if (confirmationFormHtml) {
              $(this).replaceWith(confirmationFormHtml);
              // Reinitialize form handlers
              changePassword();
              // Add event listener for cancel button
              $('.btn._border-red._big').on('click', function (e) {
                e.preventDefault();
                cancelPasswordUpdate();
              });
              //   createAndShowToast(message, 'success');
            }

            return;
          }
        } catch (error) {
          loggerError('Error updating password:', error);

          // Проверяем ответ на наличие ошибок валидации (код 422)
          if (error.status === 422 && error.responseJSON) {
            const errorData = error.responseJSON;

            // Если есть сообщение от сервера, показываем его
            if (errorData.message) {
              createAndShowToast(errorData.message, 'error');
            } else if (errorData.errors) {
              // Если есть объект с ошибками, формируем сообщение из первых ошибок каждого поля
              const errorMessages = Object.values(errorData.errors)
                .map(fieldErrors => fieldErrors[0])
                .join(', ');

              createAndShowToast(errorMessages, 'error');
            } else {
              createAndShowToast('Error updating password. Please try again.', 'error');
            }
          } else {
            createAndShowToast('Error updating password. Please try again.', 'error');
          }
        } finally {
          hideInElement(loader);
          checkNotifications();
        }
      }
    });
  }

  // Add event listener for cancel button if it exists
  $('.btn._border-red._big').on('click', function (e) {
    e.preventDefault();
    cancelPasswordUpdate();
  });
};

const initChangePassword = () => {
  changePassword();
};

export { changePassword, initChangePassword };
