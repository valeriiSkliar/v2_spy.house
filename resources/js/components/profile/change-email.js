import { createAndShowToast } from '@/utils';
import { config } from '../../config';
import { checkNotifications } from '../../helpers/notification-checker';
import { ajaxFetcher } from '../fetcher/ajax-fetcher';
import loader from '../loader';

const cancelEmailUpdate = async () => {
  try {
    loader.show();
    const response = await ajaxFetcher.get(config.apiProfileEmailCancelEndpoint, null, {});

    if (response.success) {
      // Show success message
      createAndShowToast(response.message || 'Email update cancelled successfully', 'success');

      // Use the server-provided HTML form
      if (response.initialFormHtml) {
        $('#change-email-form').replaceWith(response.initialFormHtml);

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
    loader.hide();
    checkNotifications();
  }
};

const confirmEmailUpdate = async formData => {
  try {
    loader.show();
    const response = await ajaxFetcher.form(config.apiProfileEmailUpdateConfirmEndpoint, formData);

    if (response.success) {
      // Show success message
      createAndShowToast(response.message, 'success');

      // Replace form with success message or original form
      if (response.successFormHtml) {
        $('#change-email-form').replaceWith(response.successFormHtml);
        checkNotifications();
        // Add success message if available
        if (response.successMessage) {
          $('#change-email-form').prepend(response.successMessage);
        }
      } else if (response.initialFormHtml) {
        $('#change-email-form').replaceWith(response.initialFormHtml);
        checkNotifications();
      }
      changeEmail();
    } else {
      // Show error message for invalid code
      $('input[name="verification_code"]').addClass('error').focus();
    }
  } catch (error) {
    console.error('Error confirming email update:', error);
    createAndShowToast('Error confirming email update. Please try again.', 'error');
  } finally {
    loader.hide();
    checkNotifications();
  }
};

const changeEmail = () => {
  const form = $('#change-email-form');
  if (form.length) {
    form.on('submit', async function (e) {
      loader.show();
      e.preventDefault();
      const formData = new FormData(this);

      // Determine if this is a confirmation form or initial form
      const isConfirmationForm =
        $(this).find('input[name="verification_code"]').length > 0 ||
        $(this).attr('action').includes('confirm');

      if (isConfirmationForm) {
        // Handle confirmation submission
        await confirmEmailUpdate(formData);
      } else {
        // Handle initial email update request
        try {
          const response = await ajaxFetcher.form(
            config.apiProfileEmailUpdateInitiateEndpoint,
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
              changeEmail();
              // Add event listener for cancel button
              $('.btn._border-red._big').on('click', function (e) {
                e.preventDefault();
                cancelEmailUpdate();
              });
            }
            createAndShowToast(message, 'success');

            return;
          } else {
            createAndShowToast(
              response.message || 'Error updating email. Please try again.',
              'error'
            );
          }
        } catch (error) {
          console.error('Error updating email:', error);
          createAndShowToast('Error updating email. Please try again.', 'error');
        } finally {
          checkNotifications();
          loader.hide();
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
  changeEmail();
};

export { changeEmail, initChangeEmail };
