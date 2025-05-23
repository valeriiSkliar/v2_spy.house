import { checkNotifications } from '@/helpers/notification-checker';
import { createAndShowToast } from '@/utils';
import $ from 'jquery';
import 'jquery-validation';
import { config } from '../../config';
import { ajaxFetcher } from '../fetcher/ajax-fetcher';
import { hideInElement, showInElement } from '../loader';

const cancelPersonalGreetingUpdate = async () => {
  const form = $('#personal-greeting-form');
  let loader = null;
  try {
    loader = showInElement(form[0]);
    const response = await ajaxFetcher.get(
      config.apiProfilePersonalGreetingCancelEndpoint,
      null,
      {}
    );

    if (response.success) {
      // Use the server-provided HTML form
      if (response.initialFormHtml) {
        form.replaceWith(response.initialFormHtml);

        // Reinitialize form handlers
        changePersonalGreeting();
      } else {
        // Fallback to reloading the page if we don't get the form HTML
        window.location.reload();
      }
    } else {
      createAndShowToast(response.message || 'Error cancelling personal greeting update', 'error');
    }
  } catch (error) {
    console.error('Error cancelling personal greeting update:', error);
    createAndShowToast('Error cancelling personal greeting update. Please try again.', 'error');
  } finally {
    hideInElement(loader);
    checkNotifications();
  }
};

const confirmPersonalGreetingUpdate = async formData => {
  let loader = null;
  try {
    loader = showInElement('#personal-greeting-form');
    const response = await ajaxFetcher.form(
      config.apiProfilePersonalGreetingUpdateConfirmEndpoint,
      formData
    );

    if (response.success) {
      // Show success message
      createAndShowToast(response.message, 'success');

      // Replace form with success message or original form
      if (response.successFormHtml) {
        $('#personal-greeting-form').replaceWith(response.successFormHtml);

        // Add success message if available
        if (response.successMessage) {
          $('#personal-greeting-form').prepend(response.successMessage);
        }
      } else if (response.initialFormHtml) {
        $('#personal-greeting-form').replaceWith(response.initialFormHtml);
      }
      changePersonalGreeting();
    } else {
      // Show error message for invalid code
      createAndShowToast(response.message || 'Invalid confirmation code', 'error');

      // Optionally highlight the code input field
      $('input[name="verification_code"]').addClass('error').focus();
    }
  } catch (error) {
    console.error('Error confirming personal greeting update:', error);
    createAndShowToast('Error confirming personal greeting update. Please try again.', 'error');
  } finally {
    hideInElement(loader);
    checkNotifications();
  }
};

const changePersonalGreeting = () => {
  const form = $('#personal-greeting-form');

  // Отслеживание состояния touched для полей
  const touchedFields = new Set();

  form.validate({
    rules: {
      personal_greeting: {
        required: true,
        minlength: 3,
        maxlength: 100,
      },
    },
    messages: {
      personal_greeting: {
        required: '',
        minlength: '',
        maxlength: '',
      },
    },
    // Отключаем автоматическую валидацию при потере фокуса
    onfocusout: false,
    onkeyup: false,
    onclick: false,
    // Запускаем валидацию только при отправке формы
    invalidHandler: function (event, validator) {
      // Помечаем все поля как touched при попытке отправки формы
      form.find('input, select, textarea').each(function () {
        touchedFields.add($(this).attr('name'));
      });
      validateAndToggleButton();
    },
  });

  // Функция для проверки валидности и управления кнопкой
  const validateAndToggleButton = () => {
    // Валидируем только touched поля
    let isValid = true;

    // Проверяем каждое поле с правилами валидации
    $.each(form.validate().settings.rules, function (fieldName, _) {
      const field = form.find(`[name="${fieldName}"]`);

      // Проверяем только если поле имеет статус touched
      if (touchedFields.has(fieldName)) {
        // Запускаем валидацию для конкретного поля
        if (!field.valid()) {
          isValid = false;
        }
      }
    });

    const submitButton = form.find('button[type="submit"]');
    submitButton.prop('disabled', !isValid);
  };

  // Обработчики для отслеживания touched состояния
  form.find('input, select, textarea').on('focus', function () {
    const fieldName = $(this).attr('name');
    if (fieldName) {
      touchedFields.add(fieldName);
    }
  });

  // Проверка при изменении полей, но только для touched полей
  form.find('input, select, textarea').on('input change blur', function () {
    const fieldName = $(this).attr('name');
    if (fieldName && touchedFields.has(fieldName)) {
      validateAndToggleButton();
    }
  });

  let loader = null;
  if (form.length) {
    form.on('submit', async function (e) {
      e.preventDefault();

      // Проверяем валидность формы перед отправкой
      if (!form.valid()) {
        return false;
      }

      loader = showInElement(form[0]);
      const formData = new FormData(this);

      // Determine if this is a confirmation form or initial form
      const isConfirmationForm =
        $(this).find('input[name="verification_code"]').length > 0 ||
        $(this).attr('action').includes('confirm');

      if (isConfirmationForm) {
        // Handle confirmation submission
        await confirmPersonalGreetingUpdate(formData);
      } else {
        // Handle initial personal greeting update request
        try {
          const response = await ajaxFetcher.form(
            config.apiProfilePersonalGreetingUpdateInitiateEndpoint,
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
              changePersonalGreeting();
              // Add event listener for cancel button
              $('.btn._border-red._big').on('click', function (e) {
                e.preventDefault();
                cancelPersonalGreetingUpdate();
              });
            }
            // createAndShowToast(message, 'success');

            return false;
          } else {
            createAndShowToast(
              response.message || 'Error updating personal greeting. Please try again.',
              'error'
            );
          }
        } catch (error) {
          console.error('Error updating personal greeting:', error);

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
              createAndShowToast('Error updating personal greeting. Please try again.', 'error');
            }
          } else {
            createAndShowToast('Error updating personal greeting. Please try again.', 'error');
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
    cancelPersonalGreetingUpdate();
  });
};

const initChangePersonalGreeting = () => {
  changePersonalGreeting();
};

export { changePersonalGreeting, initChangePersonalGreeting };
