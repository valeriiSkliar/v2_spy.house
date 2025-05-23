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
  const form = $('#change-password-form');
  try {
    loader = showInElement('#change-password-form');
    const response = await ajaxFetcher.form(
      config.apiProfilePasswordUpdateConfirmEndpoint,
      formData
    );
    if (response.success) {
      // Replace form with success message or original form
      if (response.successFormHtml) {
        form.replaceWith(response.successFormHtml);

        // Add success message if available
        if (response.successMessage) {
          $('#change-password-form').prepend(response.successMessage);
        }
      } else if (response.initialFormHtml) {
        form.replaceWith(response.initialFormHtml);
      }
      changePassword();
    } else {
      // Show error message for invalid code
      createAndShowToast(response.message || 'Invalid confirmation code', 'error');

      // Optionally highlight the code input field
      $('input[name="verification_code"]').addClass('error').focus();

      // Handle server validation errors
      handleServerValidationErrors(response);
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

// Handle server validation errors
const handleServerValidationErrors = response => {
  if (response.errors) {
    // Clear previous errors
    $('input').removeClass('error');
    $('.error-message').remove();

    // Add errors for each field
    Object.keys(response.errors).forEach(field => {
      const input = $(`input[name="${field}"]`);
      input.addClass('error');

      // Add error message after the input
      const errorMessage = response.errors[field][0];
      const errorDiv = $('<div>').addClass('error-message text-danger mt-1').text(errorMessage);

      // Find the parent container and append the error
      if (field === 'current_password') {
        // errorDiv.insertAfter(input.closest('.form-password'));
      } else if (field === 'password' || field === 'password_confirmation') {
        // errorDiv.insertAfter(input.closest('.form-password'));
      } else {
        // errorDiv.insertAfter(input);
      }
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
      // Place error after the form-password container
      if (
        element.attr('name') === 'current_password' ||
        element.attr('name') === 'password' ||
        element.attr('name') === 'password_confirmation'
      ) {
        error.insertAfter(element.closest('.form-password'));
      } else {
        error.insertAfter(element);
      }
    },
    highlight: function (element) {
      $(element).addClass('error');
    },
    unhighlight: function (element) {
      $(element).removeClass('error');
    },
    rules: {
      current_password: {
        required: true,
      },
      password: {
        required: true,
        minlength: 8,
      },
      password_confirmation: {
        required: true,
        equalTo: 'input[name="password"]',
      },
      verification_code: {
        required: true,
        digits: true,
        minlength: 6,
        maxlength: 6,
      },
    },
    messages: {
      current_password: {
        required: '',
      },
      password: {
        required: '',
        minlength: '',
      },
      password_confirmation: {
        required: '',
        equalTo: '',
      },
      verification_code: {
        required: '',
        digits: '',
        minlength: '',
        maxlength: '',
      },
    },
  });
};

const changePassword = () => {
  const form = $('#change-password-form');
  let loader = null;
  if (form.length) {
    // Initialize form validation
    initFormValidation(form);

    form.on('submit', async function (e) {
      e.preventDefault();

      // Check if form is valid before proceeding
      if (!form.valid()) {
        return;
      }

      loader = showInElement('#change-password-form');
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
              // createAndShowToast(message, 'success');
            }

            return;
          } else {
            // Handle server validation errors
            handleServerValidationErrors(response);

            // Show error message
            createAndShowToast(
              response.message || 'Error updating password. Please try again.',
              'error'
            );
          }
        } catch (error) {
          loggerError('Error updating password:', error);

          // Handle validation errors (code 422)
          if (error.status === 422 && error.responseJSON) {
            handleServerValidationErrors(error.responseJSON);

            const errorData = error.responseJSON;
            if (errorData.message) {
              createAndShowToast(errorData.message, 'error');
            } else if (errorData.errors) {
              // If there are errors, form a message from the first error of each field
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
