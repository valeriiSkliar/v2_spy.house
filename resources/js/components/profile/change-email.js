import { createAndShowToast } from '@/utils';
import { config } from '../../config';
import { checkNotifications } from '../../helpers/notification-checker';
import { ajaxFetcher } from '../fetcher/ajax-fetcher';
import { hideInElement, showInElement } from '../loader';

const cancelEmailUpdate = async () => {
  const form = $('#change-email-form');
  let loader = null;
  try {
    loader = showInElement(form[0]);
    const response = await ajaxFetcher.get(config.apiProfileEmailCancelEndpoint, null, {});

    if (response.success) {
      // Show success message
      createAndShowToast(response.message || 'Email update cancelled successfully', 'success');

      // Use the server-provided HTML form
      if (response.initialFormHtml) {
        form.replaceWith(response.initialFormHtml);

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
    hideInElement(loader);
    checkNotifications();
  }
};

const confirmEmailUpdate = async formData => {
  let loader = null;
  const form = $('#change-email-form');
  try {
    loader = showInElement(form[0]);
    const response = await ajaxFetcher.form(config.apiProfileEmailUpdateConfirmEndpoint, formData);

    if (response.success) {
      // Show success message
      createAndShowToast(response.message, 'success');

      // Replace form with success message or original form
      if (response.successFormHtml) {
        form.replaceWith(response.successFormHtml);
        checkNotifications();
        // Add success message if available
        if (response.successMessage) {
          form.prepend(response.successMessage);
        }
      } else if (response.initialFormHtml) {
        form.replaceWith(response.initialFormHtml);
        checkNotifications();
      }
      changeEmail();
    } else {
      // Show error message for invalid code
      $('input[name="verification_code"]').addClass('error').focus();

      // Handle server validation errors
      handleServerValidationErrors(response);
    }
  } catch (error) {
    console.error('Error confirming email update:', error);
    createAndShowToast('Error confirming email update. Please try again.', 'error');
  } finally {
    hideInElement(loader);
    checkNotifications();
  }
};

// Handle server validation errors
const handleServerValidationErrors = response => {
  console.log(response);
  if (response.errors) {
    // Clear previous errors
    $('input').removeClass('error');

    // Add errors for each field
    Object.keys(response.errors).forEach(field => {
      const input = $(`input[name="${field}"]`);
      input.addClass('error');
    });

    // Show toast with the main error message
    if (response.message) {
      createAndShowToast(response.message, 'error');
    }
  }
};

const initFormValidation = form => {
  if (!form.length || !$.validator) return;

  form.validate({
    errorClass: 'error',
    errorElement: 'div',
    errorPlacement: function (error, element) {
      error.addClass('error-message text-danger mt-1');
      error.insertAfter(element);
    },
    highlight: function (element) {
      $(element).addClass('error');
    },
    unhighlight: function (element) {
      $(element).removeClass('error');
      // $(element).next('.error-message').remove();
    },
    rules: {
      new_email: {
        required: true,
        email: true,
        notEqualTo: 'input[name="current_email"]',
      },
      password: {
        required: true,
        minlength: 6,
      },
      verification_code: {
        required: true,
        digits: true,
        minlength: 6,
        maxlength: 6,
      },
    },
    messages: {
      new_email: {
        required: '',
        email: '',
        notEqualTo: '',
      },
      password: {
        required: '',
        minlength: '',
      },
      verification_code: {
        required: '',
        digits: '',
        minlength: '',
        maxlength: '',
      },
    },
  });

  // Add custom validation method for comparing emails
  $.validator.addMethod(
    'notEqualTo',
    function (value, element, param) {
      return this.optional(element) || value !== $(param).val();
    },
    'Новый email должен отличаться от текущего'
  );
};

const changeEmail = () => {
  let loader = null;
  const form = $('#change-email-form');
  if (form.length) {
    // Initialize form validation
    initFormValidation(form);

    form.on('submit', async function (e) {
      e.preventDefault();

      // Check if form is valid
      if (!form.valid()) {
        return;
      }

      loader = showInElement(form[0]);
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
            // Handle server validation errors
            handleServerValidationErrors(response);

            createAndShowToast(
              response.message || 'Error updating email. Please try again.',
              'error'
            );
          }
        } catch (error) {
          console.error('Error updating email:', error);

          // Обработка ошибки 422 (Unprocessable Content)
          if (error.status === 422 && error.responseJSON) {
            handleServerValidationErrors(error.responseJSON);
          } else {
            createAndShowToast('Error updating email. Please try again.', 'error');
          }
        } finally {
          checkNotifications();
          hideInElement(loader);
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
